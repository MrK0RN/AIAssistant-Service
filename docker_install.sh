#!/bin/bash
set -e
install_docker() {
    echo "Installing Docker on $PRETTY_NAME..."

    apt-get update
    apt-get install -y apt-transport-https ca-certificates curl software-properties-common gnupg
    curl -fsSL https://download.docker.com/linux/$ID/gpg | apt-key add -
    add-apt-repository "deb [arch=$(dpkg --print-architecture)] https://download.docker.com/linux/$ID $(lsb_release -cs) stable"
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io
    systemctl start docker
    systemctl enable docker

    echo "Docker installed and started successfully."
}

install_docker_compose() {
    echo "Installing Docker Compose..."
    COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep '"tag_name":' | sed -E 's/.*"([^"]+)".*/\1/')
    curl -L "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    echo "Docker Compose version $COMPOSE_VERSION installed successfully."
}

# Run installation functions
install_docker
install_docker_compose

echo "Installation complete. You can verify by running 'docker --version' and 'docker-compose --version'."
