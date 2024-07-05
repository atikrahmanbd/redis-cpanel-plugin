#!/bin/bash

# Following Bash Script Is Not Being Used Anymore. Moved To RedisManager.php Class.

echo 'Functionality Was Moved To RedisManager.php Class'

# Exit the script
exit

# Define Variables
ACTION=$1
USERNAME=$2
CONFIG_DIR="/home/$USERNAME/.cpplugin/redis"
CONFIG_FILE="$CONFIG_DIR/redis.conf"
LOG_DIR="/home/$USERNAME/.cpplugin/redis/log"
LOG_FILE="$LOG_DIR/$USERNAME.log"
REDIS_CLI=$(which redis-cli)  # Update this path based on your redis-cli location
USER_REDIS_DIR="/home/$USERNAME/.cpplugin/redis/data"

# Ensure log directory exists and set permissions
mkdir -p $LOG_DIR
touch $LOG_FILE  # Create an empty log file if it doesn't exist
chown -R $USERNAME:$USERNAME $LOG_DIR $LOG_FILE
chmod 755 $LOG_DIR
chmod 644 $LOG_FILE

# Logging Function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# Function To Find an Available Port
find_available_port() {
    local port
    while true; do
        port=$(shuf -i 55000-60000 -n 1)
        if ! lsof -i:$port &>/dev/null; then
            echo $port
            return
        fi
    done
}

# Function To Create Redis Config If It Doesn't Exist
create_redis_config() {
    local username=$1
    local config_dir=$CONFIG_DIR
    local config_file=$CONFIG_FILE

    if [ ! -f $config_file ]; then
        log "Creating New Redis Config For $username"
        PASSWORD=$(openssl rand -base64 8)
        
        mkdir -p $config_dir
        touch $config_file  # Create empty files if they don't exist
        chown -R $username:$username $config_dir $config_file
        chmod 755 $config_dir
        chmod 644 $config_file

        if [ -f $config_file ]; then
            PORT=$(find_available_port)
            {
                echo "bind 127.0.0.1"
                echo "port $PORT"
                echo "requirepass $PASSWORD"
                echo "dir $USER_REDIS_DIR"
                echo "maxmemory 256mb"
                echo "databases 16"
            } >> $config_file
        else
            log "Failed to create the config file $config_file"
            echo "Failed to create the config file $config_file" >&2
            exit 1
        fi

        mkdir -p $USER_REDIS_DIR
        chown -R $username:$username $USER_REDIS_DIR
        chmod 755 $USER_REDIS_DIR
    fi
}

# Function To Start Redis
start_redis() {
    local username=$1
    local config_file=$CONFIG_FILE

    if [ ! -f $config_file ]; then
        create_redis_config($username)
    fi

    # Start Redis server and capture the output
    local output=$(cd $USER_REDIS_DIR && redis-server $config_file --daemonize yes 2>&1)
    local status=$?

    log "Redis start command: redis-server $config_file --daemonize yes"
    log "Redis start command output: $output"

    if [ $status -eq 0 ]; then
        log "Started Redis for $username"
        echo "Redis started successfully: $output"
    else
        log "Failed to start Redis for $username: $output"
        exit 1
    fi
}

# Function To Stop Redis
stop_redis() {
    local username=$1

    $REDIS_CLI -h 127.0.0.1 -p $(grep '^port' $CONFIG_FILE | awk '{print $2}') -a $(grep 'requirepass' $CONFIG_FILE | awk '{print $2}') shutdown
    if [ $? -eq 0 ]; then
        echo "Stopped Redis for $username"
        log "Stopped Redis for $username"
    else
        echo "Failed to stop Redis for $username"
        log "Failed to stop Redis for $username"
        exit 1
    fi
}

# Function To Check Redis Status
check_redis_status() {
    local username=$1
    local config_file=$CONFIG_FILE

    # Check if config file exists
    if [ ! -f $config_file ]; then
        echo "inactive"
        echo "Redis was never started for $username before. Please click on Start Redis Button."
        log "Redis never started for $username because config file is missing"
        return
    fi

    # Retrieve Port Number From The Config File
    local port=$(grep '^port' $config_file | awk '{print $2}')

    # Check If a Redis Process Is Running with the Specified Port
    if ps aux | grep -q "[r]edis.*127.0.0.1:$port"; then
        echo "Running $port $(grep 'requirepass' $config_file | awk '{print $2}')"
        echo "Running - Redis Started Successfully."
        log "Redis is running for $username on port $port"
    else
        echo "inactive"
        log "Redis is inactive for $username"
    fi
}

log "Action: $ACTION, Username: $USERNAME"

case $ACTION in
    start)
        start_redis $USERNAME
        ;;
    stop)
        stop_redis $USERNAME
        ;;
    status)
        check_redis_status $USERNAME
        ;;
    *)
        echo "Usage: $0 {start|stop|status} username"
        log "Invalid action: $ACTION"
        exit 1
        ;;
esac