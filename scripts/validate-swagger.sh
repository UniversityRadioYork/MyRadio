#!/usr/bin/env bash

wget http://localhost/api/v2/swagger.json

validation_output=$(curl -X POST -d @swagger.json -H 'Content-Type:application/json' http://validator.swagger.io/validator/debug | jq '.')

if [ "$validation_output" != "{}" ]; then
  echo "!!! SWAGGER INVALID !!!"
  echo "$validation_output"
  exit 1
fi
