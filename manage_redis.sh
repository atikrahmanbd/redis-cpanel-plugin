#!/bin/bash

ACTION=$1
USERNAME=$2
PORT=$((6379 + $(id -u $USERNAME)))
CONFIG_FILE="/etc/redis/$USERNAME.conf"
PASSWORD_FILE="/etc/redis/$USERNAME.pass"

case $ACTION in
    start)
        if [ ! -f $CONFIG_FILE ]; then
            PASSWORD=$(openssl rand -base64 32)
            echo $PASSWORD > $PASSWORD_FILE
            cp /etc/redis/redis-template.conf $CONFIG_FILE
            echo "port $PORT" >> $CONFIG_FILE
            echo "requirepass $PASSWORD" >> $CONFIG_FILE
            echo "maxmemory 256mb" >> $CONFIG_FILE
            echo "databases 16" >> $CONFIG_FILE
            mkdir -p /var/lib/redis/$USERNAME
        else
            PASSWORD=$(cat $PASSWORD_FILE)
        fi
        redis-server $CONFIG_FILE
        echo "Redis started for $USERNAME on port $PORT with password $PASSWORD"
        ;;
    stop)
        pkill -f "redis-server.*$USERNAME"
        echo "Redis stopped for $USERNAME"
        ;;
    status)
        if pgrep -f "redis-server.*$USERNAME" > /dev/null; then
            PASSWORD=$(cat $PASSWORD_FILE)
            echo "running $PORT $PASSWORD"
        else
            echo "inactive"
        fi
        ;;
    *)
        echo "Usage: $0 {start|stop|status} username"
        exit 1
        ;;
esac