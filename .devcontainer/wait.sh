#!/bin/bash
set -ex

# Wait for Docker to be ready
wait_for_docker() {
  while true; do
    docker ps > /dev/null 2>&1 && break
    sleep 1
  done
  echo "Docker is ready."
}

echo "Waiting for Docker ..."
wait_for_docker
