#!/usr/bin/env bash
#
# Cloud Agent install phase for the Property Hive wp-env development
# environment. Installs the Docker engine (required by @wordpress/env) and
# pre-warms the wp-env images/volumes so that a later boot starts quickly.
#
# This script is idempotent: it can run repeatedly against cached state.
set -Eeuo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

log() { printf '\n=== %s ===\n' "$1"; }

install_docker() {
    if command -v docker >/dev/null 2>&1; then
        log "Docker already installed ($(docker --version))"
        return
    fi

    log "Installing Docker engine"
    export DEBIAN_FRONTEND=noninteractive

    # fuse-overlayfs is required as the storage driver inside the nested
    # (unprivileged) Cloud Agent container. iptables provides the firewall
    # backend Docker uses for container networking.
    sudo apt-get update -qq
    sudo apt-get install -y -qq ca-certificates curl gnupg iptables uidmap fuse-overlayfs
    # A pending fuse3 conffile prompt can leave dpkg half-configured; finish
    # it non-interactively before continuing.
    sudo dpkg --configure -a --force-confold || true

    sudo install -m 0755 -d /etc/apt/keyrings
    if [ ! -f /etc/apt/keyrings/docker.asc ]; then
        sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
        sudo chmod a+r /etc/apt/keyrings/docker.asc
    fi

    local codename
    codename="$(. /etc/os-release && echo "$VERSION_CODENAME")"
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${codename} stable" \
        | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    sudo apt-get update -qq
    sudo apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    log "Docker installed ($(docker --version))"
}

configure_docker() {
    log "Configuring Docker daemon"
    # fuse-overlayfs works without kernel overlay support inside the nested
    # Cloud Agent container.
    sudo mkdir -p /etc/docker
    printf '{\n  "storage-driver": "fuse-overlayfs",\n  "iptables": true\n}\n' \
        | sudo tee /etc/docker/daemon.json > /dev/null
    sudo usermod -aG docker "$(id -un)" || true
}

# shellcheck source=/dev/null
source "${REPO_DIR}/.cursor/docker-helpers.sh"

install_docker
configure_docker

# Pre-warm the wp-env stack so images and the WordPress volume are baked into
# the environment snapshot. Best-effort: if the daemon cannot run during the
# build phase, the boot-time start script will pull the images instead.
if start_docker_daemon && prepare_docker_networking; then
    log "Pre-warming wp-env images and WordPress volume"
    ( cd "$REPO_DIR" && npx --yes @wordpress/env start ) || \
        echo "wp-env pre-warm skipped (will run at boot instead)"
else
    echo "Docker daemon unavailable during install; skipping pre-warm."
fi

log "Install phase complete"
