#!/bin/sh
set -eu

attempts=0
max_attempts=20

until php artisan migrate --force; do
    attempts=$((attempts + 1))

    if [ "$attempts" -ge "$max_attempts" ]; then
        echo "Laravel migrations failed after $max_attempts attempts." >&2
        exit 1
    fi

    sleep 3
done

