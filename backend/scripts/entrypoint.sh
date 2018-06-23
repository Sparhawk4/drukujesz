#!/bin/sh
sh /scripts/is_db_ready.sh 0
cat > /scripts/drop_db.sql <<-EOM
DROP DATABASE IF EXISTS ${MYSQL_DATABASE};
CREATE DATABASE ${MYSQL_DATABASE};
GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO ${MYSQL_USERNAME}@'%' WITH GRANT OPTION;
EOM
mysql -u $MYSQL_USERNAME -p$MYSQL_ROOT_PASSWORD -h $MYSQL_HOSTNAME < /scripts/drop_db.sql
echo "Previous dump was dropped"
dump_url="localhost"
if [ "${LOCAL_ENV}" = "true" ]; then
  echo "Using local env, seding db dump with 'localhost' instead of '$dump_url'"
  sed -i "s/$dump_url/localhost/g" /scripts/dump.sql
else
  echo "Using local env, seding db dump with 'localhost' instead of '$dump_url'"
  sed -i "s-$dump_url-195.242.117.177:7000-g" /scripts/dump.sql
fi
mysql -u $MYSQL_USERNAME -p$MYSQL_ROOT_PASSWORD -h $MYSQL_HOSTNAME $MYSQL_DATABASE < /scripts/dump.sql
echo "New dump was applied"
(
    cd $WORKDIR
    php-fpm
)