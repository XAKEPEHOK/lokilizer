#!/bin/sh
set -e

crond -b -l 8

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php "$@"
fi

exec "$@"