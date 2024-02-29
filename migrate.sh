#!/usr/bin/env bash

MYSQL=${MYSQL:-mariadb}
DATABASE=${DATABASE:-seemus}

$MYSQL <<< "DROP SCHEMA IF EXISTS \`$DATABASE\`;"
$MYSQL <<< "CREATE SCHEMA \`$DATABASE\`;"
for migration in $(ls ./database/ | sort); do
    echo "RUN database/$migration"
    $MYSQL $DATABASE < ./database/$migration
done