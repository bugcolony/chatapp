#!/bin/sh
set -eu

file_env() {
    var="$1"
    file_var="${var}_FILE"

    eval "value=\${$var:-}"
    eval "file_value=\${$file_var:-}"

    if [ -n "$value" ] && [ -n "$file_value" ]; then
        echo "Both $var and $file_var are set; only one is allowed." >&2
        exit 1
    fi

    if [ -n "$file_value" ]; then
        if [ ! -f "$file_value" ]; then
            echo "Secret file $file_value for $var does not exist." >&2
            exit 1
        fi

        value="$(cat "$file_value")"
    fi

    export "$var=$value"
    unset "$file_var"
}

for file_var in $(env | cut -d= -f1 | grep '_FILE$' || true); do
    file_env "${file_var%_FILE}"
done

mkdir -p /tmp/laravel/views
chmod -R u+rwX,g+rwX,o-rwx /tmp/laravel

exec "$@"
