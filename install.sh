#!/usr/bin/env bash
set -euxo pipefail

# A simple function to run commands in the Magento Docker container, with the right user.
function dx {
    docker compose exec -w '/bitnami/magento' -u 'daemon:root' magento "$@"
}

# Enable developer mode, so the logs will contain more info.
dx php bin/magento deploy:mode:set developer

# Remove the old module.
dx mkdir -p app/code/Parcelpro
dx rm -rf app/code/Parcelpro/Shipment

# Copy the module files.
docker compose cp ../magento2-module magento:/bitnami/magento/app/code/Parcelpro
dx mv 'app/code/Parcelpro/magento2-module' 'app/code/Parcelpro/Shipment'
docker compose exec magento chown -R 'daemon:root' '/bitnami/magento/app/code/Parcelpro'
dx rm -rf 'app/code/Parcelpro/Shipment/vendor'

# Enable the module.
dx php bin/magento module:enable --clear-static-content Parcelpro_Shipment

# Remove the old generated code.
dx rm -r generated

# Update the Magento setup and flush the cache.
dx php bin/magento setup:upgrade
dx php bin/magento setup:di:compile
dx php bin/magento setup:static-content:deploy -f
dx php bin/magento cache:clean
dx php bin/magento cache:flush
