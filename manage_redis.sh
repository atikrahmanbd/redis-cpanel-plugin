#!/bin/bash

ACTION=$1
USERNAME=$2
CONFIG_FILE="/etc/redis/$USERNAME.conf"
PASSWORD_FILE="/etc/redis/$USERNAME.pass"
LOG_DIR="/var/log/redis"
LOG_FILE="$LOG_DIR/$USERNAME.log"
SYSTEMD_SERVICE_DIR="/etc/systemd/system"
REDIS_CLI=$(which redis-cli)  # Update this path based on your redis-cli location

# Ensure log directory exists and set permissions
mkdir -p $LOG_DIR
touch $LOG_FILE  # Create an empty log file if it doesn't exist
chown $USERNAME:$USERNAME $LOG_DIR $LOG_FILE
chmod 755 $LOG_DIR
chmod 644 $LOG_FILE

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# Function to create systemd service unit
create_systemd_service() {
    local username=$1
    local port=$2
    local config_file="/etc/redis/$username.conf"
    local service_file="$SYSTEMD_SERVICE_DIR/redis-$username.service"

    cat > $service_file <<EOF
[Unit]
Description=Redis instance for $username
After=network.target
After=network-online.target
Wants=network-online.target

[Service]
ExecStart=/usr/bin/redis-server $config_file --daemonize yes --supervised systemd
ExecStop=/usr/libexec/redis-shutdown
Type=notify
User=$username
Group=$username
RuntimeDirectory=redis
RuntimeDirectoryMode=0755

[Install]
WantedBy=multi-user.target
EOF

    chmod 644 $service_file
    systemctl daemon-reload
    systemctl enable redis-$username
    systemctl start redis-$username
}

# Function to check if a port is available
is_port_available() {
    local port=$1
    if ! lsof -i:$port &>/dev/null; then
        return 0  # Port is available
    else
        return 1  # Port is in use
    fi
}

# Find an available port
find_available_port() {
    local port
    while true; do
        port=$(shuf -i 55000-60000 -n 1)
        if is_port_available $port; then
            echo $port
            return
        fi
    done
}

log "Action: $ACTION, Username: $USERNAME"

case $ACTION in
    start)
        if [ ! -f $CONFIG_FILE ]; then
            log "Creating New Redis Config For $USERNAME"
            PASSWORD=$(openssl rand -base64 16)
            echo $PASSWORD > $PASSWORD_FILE
            cp /etc/redis/redis-template.conf $CONFIG_FILE
            sed -i "s/%h/$USERNAME/g" $CONFIG_FILE
            PORT=$(find_available_port)
            echo "" >> $CONFIG_FILE
            echo "port $PORT" >> $CONFIG_FILE
            echo "requirepass $PASSWORD" >> $CONFIG_FILE
            echo "maxmemory 256mb" >> $CONFIG_FILE
            echo "databases 16" >> $CONFIG_FILE
            mkdir -p /var/lib/redis/$USERNAME
            mkdir -p $LOG_DIR  # Ensure log directory is created
            chown -R $USERNAME:$USERNAME /var/lib/redis/$USERNAME
            chown -R $USERNAME:$USERNAME $LOG_DIR
            chmod 755 /var/lib/redis/$USERNAME
            chmod 755 $LOG_DIR
            chown $USERNAME:$USERNAME $CONFIG_FILE $PASSWORD_FILE
            chmod 644 $CONFIG_FILE $PASSWORD_FILE
        else
            PASSWORD=$(cat $PASSWORD_FILE)
            # Retrieve the existing port number from the config file
            PORT=$(grep '^port' $CONFIG_FILE | awk '{print $2}')
        fi

        log "Starting Redis For $USERNAME With Config $CONFIG_FILE"

        # Check if the port is already in use
        if is_port_available $PORT; then
            create_systemd_service $USERNAME $PORT
        else
            echo "Port $PORT is already in use. Cannot start Redis."
            log "Port $PORT is already in use. Cannot start Redis."
            exit 1
        fi
        ;;
    stop)
        log "Stopping Redis For $USERNAME"
        sudo systemctl stop redis-$USERNAME
        if [ $? -eq 0 ]; then
            echo "Redis Stopped for $USERNAME"
            log "Redis Stopped Successfully For $USERNAME"
            systemctl disable redis-$USERNAME
            rm -f $SYSTEMD_SERVICE_DIR/redis-$USERNAME.service
            systemctl daemon-reload
        else
            echo "Failed To Stop Redis For $USERNAME"
            log "Failed To Stop Redis For $USERNAME"
        fi
        ;;
    status)
        if systemctl is-active --quiet redis-$USERNAME; then
            PASSWORD=$(cat $PASSWORD_FILE)
            PORT=$(grep '^port' $CONFIG_FILE | awk '{print $2}')
            echo "Running $PORT $PASSWORD"
            log "Redis is Running For $USERNAME on Port $PORT"
        else
            echo "inactive"
            log "Redis is Inactive For $USERNAME"
        fi
        ;;
    *)
        echo "Usage: $0 {start|stop|status} username"
        log "Invalid action: $ACTION"
        exit 1
        ;;
esac