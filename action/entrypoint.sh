#!/bin/sh -l

if [ -z $INPUT_MEMORY_LIMIT ]; then
  INPUT_MEMORY_LIMIT="128M"
fi

php -d memory_limit=$INPUT_MEMORY_LIMIT /action/phpinsights-app.phar $GITHUB_WORKSPACE -vvv $*
