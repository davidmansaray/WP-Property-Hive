#!/usr/bin/env bash

set -Eeuo pipefail

commit="${1:-}"
package_dir="${2:-}"

if [[ -z "$commit" || -z "$package_dir" ]]; then
    echo "Usage: $0 <commit> <empty-package-directory>" >&2
    exit 64
fi

if ! git cat-file -e "${commit}^{commit}" 2>/dev/null; then
    echo "Commit does not exist in this checkout: $commit" >&2
    exit 65
fi

if [[ -e "$package_dir" ]]; then
    echo "Package directory already exists: $package_dir" >&2
    exit 73
fi

mkdir -p -- "$package_dir"

git archive --format=tar "$commit" |
    tar \
        --extract \
        --directory="$package_dir" \
        --exclude=".github" \
        --exclude="dev-tools" \
        --exclude="output" \
        --exclude="plans" \
        --exclude="includes/divi-extensions/node_modules"

if [[ ! -f "$package_dir/propertyhive.php" ]]; then
    echo "Package does not contain propertyhive.php." >&2
    exit 66
fi

for excluded_path in \
    ".github" \
    "dev-tools" \
    "output" \
    "plans" \
    "includes/divi-extensions/node_modules"
do
    if [[ -e "$package_dir/$excluded_path" ]]; then
        echo "Excluded path is present in package: $excluded_path" >&2
        exit 66
    fi
done

printf 'Packaged %s tracked files from %s.\n' \
    "$(find "$package_dir" -type f | wc -l | tr -d ' ')" \
    "$commit"
