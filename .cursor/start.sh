#!/usr/bin/env bash
#
# Cloud Agent start phase for the Property Hive wp-env development
# environment. Runs on every boot: brings up the Docker daemon, fixes nested
# container networking, and starts the wp-env WordPress stack. Cached images
# and the WordPress volume from the install phase make this fast.
#
# This script is idempotent and tolerates restarts.
set -Eeuo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# shellcheck source=/dev/null
source "${REPO_DIR}/.cursor/docker-helpers.sh"

if ! start_docker_daemon; then
    echo "Could not start the Docker daemon; wp-env cannot start." >&2
    exit 1
fi

prepare_docker_networking

echo "Starting wp-env WordPress stack"
cd "$REPO_DIR"
npx --yes @wordpress/env start

echo "wp-env is up: development http://localhost:8888  tests http://localhost:8889"
