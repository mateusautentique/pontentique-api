#!/bin/bash

set -e

host="$1"
shift
cmd="$@"

until mysql -h"$host" -P3306 -uroot -p${DB_PASSWORD} &> /dev/null
do
  echo "MySQL is unavailable - sleeping"
  sleep 1
done

>&2 echo "MySQL is up - executing"
exec $cmd