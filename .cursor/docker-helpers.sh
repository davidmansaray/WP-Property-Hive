#!/usr/bin/env bash
#
# Shared helpers for bringing up the Docker daemon and fixing container
# networking inside the nested (unprivileged) Cloud Agent VM. Sourced by both
# the install and start phase scripts.

# Start the Docker daemon if it is not already responding. The daemon is fully
# detached (new session, no controlling terminal, stdio redirected) so it
# survives the boot-time `start` phase exiting. Returns non-zero if the daemon
# does not become ready.
start_docker_daemon() {
    if sudo docker info >/dev/null 2>&1; then
        echo "Docker daemon already running"
        return 0
    fi

    echo "Starting Docker daemon"
    # setsid + </dev/null detaches dockerd into its own session so it is not
    # reaped when this script (the `start` phase) returns.
    sudo bash -c 'setsid dockerd >>/tmp/dockerd.log 2>&1 </dev/null &'

    local i
    for i in $(seq 1 60); do
        if sudo docker info >/dev/null 2>&1; then
            echo "Docker daemon is ready"
            return 0
        fi
        sleep 1
    done

    echo "Docker daemon failed to start; see /tmp/dockerd.log" >&2
    tail -n 20 /tmp/dockerd.log 2>/dev/null || true
    return 1
}

# Make node / npx available regardless of how this script is invoked. On boot
# the `start` phase runs in a non-login shell that may not have nvm's node on
# PATH, so add the common node install locations.
ensure_node_on_path() {
    command -v npx >/dev/null 2>&1 && return 0
    # nvm-managed node (pick the newest installed version).
    if [ -d "$HOME/.nvm/versions/node" ]; then
        local latest
        latest="$(ls -1 "$HOME/.nvm/versions/node" 2>/dev/null | sort -V | tail -n 1)"
        [ -n "$latest" ] && export PATH="$HOME/.nvm/versions/node/$latest/bin:$PATH"
    fi
    # nvm may also expose a default alias via its script.
    if ! command -v npx >/dev/null 2>&1 && [ -s "$HOME/.nvm/nvm.sh" ]; then
        # shellcheck source=/dev/null
        . "$HOME/.nvm/nvm.sh" >/dev/null 2>&1 || true
    fi
    command -v npx >/dev/null 2>&1
}

# Fix container-to-container networking. In this nested environment, bridged
# frames are pushed through the (broken) nftables FORWARD chain, which drops
# same-bridge traffic. Disabling bridge netfilter lets Docker's user-defined
# bridge networks work (outbound NAT is unaffected). Also make the Docker
# socket usable without sudo so `npx @wordpress/env` can reach the daemon.
prepare_docker_networking() {
    sudo modprobe br_netfilter 2>/dev/null || true
    sudo sysctl -w net.bridge.bridge-nf-call-iptables=0 >/dev/null 2>&1 || true
    sudo sysctl -w net.bridge.bridge-nf-call-ip6tables=0 >/dev/null 2>&1 || true
    if [ -S /var/run/docker.sock ]; then
        sudo chmod 666 /var/run/docker.sock || true
    fi
    return 0
}
