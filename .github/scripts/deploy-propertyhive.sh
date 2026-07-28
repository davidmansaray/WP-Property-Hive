#!/usr/bin/env bash

set -Eeuo pipefail

required_environment=(
    DEPLOY_HOST
    DEPLOY_USER
    DEPLOY_PATH
    DEPLOY_SSH_KEY_FILE
    GITHUB_SHA
    GITHUB_REF_NAME
    GITHUB_RUN_ID
    GITHUB_SERVER_URL
    GITHUB_REPOSITORY
    PACKAGE_DIR
)

for name in "${required_environment[@]}"; do
    if [[ -z "${!name:-}" ]]; then
        echo "Required environment variable is empty: $name" >&2
        exit 64
    fi
done

if [[ ! "$DEPLOY_HOST" =~ ^[A-Za-z0-9.-]+$ ]]; then
    echo "Deployment host contains unsupported characters." >&2
    exit 65
fi

if [[ ! "$DEPLOY_USER" =~ ^[A-Za-z_][A-Za-z0-9_-]*$ ]]; then
    echo "Deployment user contains unsupported characters." >&2
    exit 65
fi

if [[ ! "$DEPLOY_PATH" =~ ^/[A-Za-z0-9._/-]+$ || "$DEPLOY_PATH" == "/" ]]; then
    echo "Deployment path must be a specific absolute path." >&2
    exit 65
fi

if [[ ! "$GITHUB_SHA" =~ ^[0-9a-f]{40}$ ]]; then
    echo "GITHUB_SHA is not a full Git commit SHA." >&2
    exit 65
fi

if [[ ! -d "$PACKAGE_DIR" || -L "$PACKAGE_DIR" ]]; then
    echo "Package directory is missing or unsafe: $PACKAGE_DIR" >&2
    exit 66
fi

if [[ ! -f "$PACKAGE_DIR/propertyhive.php" ]]; then
    echo "Package directory does not contain propertyhive.php." >&2
    exit 66
fi

if [[ ! -f "$DEPLOY_SSH_KEY_FILE" ]]; then
    echo "Deployment SSH key file does not exist." >&2
    exit 66
fi

remote="${DEPLOY_USER}@${DEPLOY_HOST}"
backup_root="/var/backups/propertyhive"
short_sha="${GITHUB_SHA:0:12}"
run_url="${GITHUB_SERVER_URL}/${GITHUB_REPOSITORY}/actions/runs/${GITHUB_RUN_ID}"

ssh_options=(
    -i "$DEPLOY_SSH_KEY_FILE"
    -o BatchMode=yes
    -o ConnectTimeout=15
    -o PreferredAuthentications=publickey
    -o StrictHostKeyChecking=yes
)

rsync_ssh="ssh -i $DEPLOY_SSH_KEY_FILE -o BatchMode=yes -o ConnectTimeout=15 -o PreferredAuthentications=publickey -o StrictHostKeyChecking=yes"
backup_path=""
deployment_started=0

append_summary() {
    if [[ -n "${GITHUB_STEP_SUMMARY:-}" ]]; then
        printf '%s\n' "$1" >> "$GITHUB_STEP_SUMMARY"
    fi
}

rollback() {
    local exit_code=$?

    if [[ "$deployment_started" -eq 1 && -n "$backup_path" ]]; then
        echo "::warning::Deployment verification failed. Restoring $backup_path."

        if ssh "${ssh_options[@]}" "$remote" bash -s -- "$backup_path" "$DEPLOY_PATH" <<'REMOTE_ROLLBACK'
set -Eeuo pipefail

backup_path="$1"
deploy_path="$2"

test -d "$backup_path"
install -d -m 755 "$deploy_path"
rsync -a --delete-delay -- "$backup_path/" "$deploy_path/"
chown -R www-data:www-data "$deploy_path"
find "$deploy_path" -type d -exec chmod 755 {} +
find "$deploy_path" -type f -exec chmod 644 {} +
REMOTE_ROLLBACK
        then
            append_summary "### Deployment failed and was rolled back"
            append_summary ""
            append_summary "- Attempted commit: \`$GITHUB_SHA\`"
            append_summary "- Restored backup: \`$backup_path\`"
            append_summary "- Failed run: $run_url"
        else
            echo "::error::Automatic rollback also failed. Manual recovery is required from $backup_path."
            append_summary "### Deployment and automatic rollback failed"
            append_summary ""
            append_summary "- Attempted commit: \`$GITHUB_SHA\`"
            append_summary "- Manual rollback source: \`$backup_path\`"
            append_summary "- Failed run: $run_url"
        fi
    fi

    exit "$exit_code"
}

trap rollback ERR

echo "Creating a rollback copy before deployment."
backup_path="$(
    ssh "${ssh_options[@]}" "$remote" bash -s -- "$DEPLOY_PATH" "$backup_root" "$short_sha" <<'REMOTE_BACKUP'
set -Eeuo pipefail

deploy_path="$1"
backup_root="$2"
short_sha="$3"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_path="$backup_root/propertyhive-$timestamp-$short_sha"

install -d -m 755 "$backup_root"

if [[ -d "$deploy_path" ]]; then
    cp -a -- "$deploy_path" "$backup_path"
else
    install -d -m 755 "$backup_path"
fi

install -d -m 755 "$deploy_path"
printf '%s\n' "$backup_path"
REMOTE_BACKUP
)"

if [[ ! "$backup_path" =~ ^/var/backups/propertyhive/propertyhive-[0-9TZ]+-[0-9a-f]{12}$ ]]; then
    echo "Remote host returned an unexpected backup path." >&2
    exit 67
fi

deployment_started=1

echo "Synchronising the packaged commit."
RSYNC_RSH="$rsync_ssh" rsync \
    --archive \
    --compress \
    --delete-delay \
    --human-readable \
    --itemize-changes \
    -- "$PACKAGE_DIR/" "$remote:$DEPLOY_PATH/"

echo "Restoring permissions and verifying WordPress."
verification="$(
    ssh "${ssh_options[@]}" "$remote" bash -s -- "$DEPLOY_PATH" <<'REMOTE_VERIFY'
set -Eeuo pipefail

deploy_path="$1"
wordpress_path="$(dirname "$(dirname "$(dirname "$deploy_path")")")"

chown -R www-data:www-data "$deploy_path"
find "$deploy_path" -type d -exec chmod 755 {} +
find "$deploy_path" -type f -exec chmod 644 {} +

php -l "$deploy_path/propertyhive.php" >/dev/null
wp --allow-root --path="$wordpress_path" plugin is-active propertyhive

site_url="$(wp --allow-root --path="$wordpress_path" option get home --skip-plugins --skip-themes)"
property_id="$(
    wp --allow-root --path="$wordpress_path" post list \
        --post_type=property \
        --post_status=publish \
        --posts_per_page=1 \
        --field=ID |
        head -n 1
)"

if [[ -z "$property_id" ]]; then
    echo "No published property is available for the deployment smoke test." >&2
    exit 1
fi

property_url="$(wp --allow-root --path="$wordpress_path" post url "$property_id")"

printf 'site_url=%s\n' "$site_url"
printf 'property_url=%s\n' "$property_url"
REMOTE_VERIFY
)"

site_url="$(printf '%s\n' "$verification" | sed -n 's/^site_url=//p' | tail -n 1)"
property_url="$(printf '%s\n' "$verification" | sed -n 's/^property_url=//p' | tail -n 1)"
site_url="${site_url%/}"

if [[ ! "$site_url" =~ ^https:// || ! "$property_url" =~ ^https:// ]]; then
    echo "Smoke-test URLs must use HTTPS." >&2
    exit 68
fi

echo "Requesting the staging site root."
site_status="$(
    curl \
        --fail \
        --silent \
        --show-error \
        --location \
        --retry 3 \
        --retry-all-errors \
        --connect-timeout 10 \
        --max-time 45 \
        --output /dev/null \
        --write-out "%{http_code}" \
        "$site_url"
)"

echo "Requesting a published property."
property_status="$(
    curl \
        --fail \
        --silent \
        --show-error \
        --location \
        --retry 3 \
        --retry-all-errors \
        --connect-timeout 10 \
        --max-time 45 \
        --output /dev/null \
        --write-out "%{http_code}" \
        "$property_url"
)"

trap - ERR
deployment_started=0

if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    {
        printf 'site_url=%s\n' "$site_url"
        printf 'property_url=%s\n' "$property_url"
        printf 'backup_path=%s\n' "$backup_path"
    } >> "$GITHUB_OUTPUT"
fi

append_summary "### Staging deployment succeeded"
append_summary ""
append_summary "- Branch: \`$GITHUB_REF_NAME\`"
append_summary "- Commit: \`$GITHUB_SHA\`"
append_summary "- Rollback backup: \`$backup_path\`"
append_summary "- Site check: $site_url ($site_status)"
append_summary "- Property check: $property_url ($property_status)"
append_summary "- Actions run: $run_url"

printf 'Deployment succeeded: %s (%s), %s (%s)\n' \
    "$site_url" \
    "$site_status" \
    "$property_url" \
    "$property_status"
