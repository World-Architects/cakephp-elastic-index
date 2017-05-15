#!/bin/bash
export CODECOV_TOKEN="f9ac10d4-f3d2-4ccf-8000-5b05664d211b"
#./bin/phpunit --coverage-clover=coverage.xml
curl -s https://codecov.io/bash | bash
