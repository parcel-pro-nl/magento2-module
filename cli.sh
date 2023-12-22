#!/usr/bin/env bash
set -euo pipefail

docker compose exec --user daemon:root -w '/bitnami/magento' -it magento bash
