#!/bin/sh
set -e
mysql_ready () {
	>&2 echo "Checking database..."
	mysql -u $MYSQL_USERNAME -p$MYSQL_ROOT_PASSWORD -h $MYSQL_HOSTNAME -e "quit" $MYSQL_DATABASE
}
until mysql_ready; do
    >&2 echo "Can't reach database, waiting..."
    sleep 2
done
>&2 echo "Database accessible, continuing..."