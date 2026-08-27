#!/usr/bin/env bash
#
# Shared helpers for bringing up the Docker daemon and fixing container
# networking inside the nested (unprivileged) Cloud Agent VM. Sourced by both
# the install and start phase scripts.

# Start the Docker daemon in the background if it is not already responding.
# Returns non-zero if the daemon does not become ready.
start_docker_daemon() {
    if sudo docker info >/dev/null 2>&1; then
        echo "Docker daemon already running"
        return 0
    fi

    echo "Starting Docker daemon"
    sudo bash -c 'nohup dockerd >/tmp/dockerd.log 2>&1 &'

    local i
    for i in $(seq 1 30); do
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
