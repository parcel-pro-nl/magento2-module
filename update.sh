#cp -r /Users/martijn/Documents/parcelpro/magento2-module /Users/martijn/Documents/parcelpro/magento/src/app/code/Parcelpro_Shipment
mkdir -p /Users/martijn/Documents/parcelpro/magento/src/app/code/Parcelpro/Shipment
rsync -av --progress /Users/martijn/Documents/parcelpro/magento2-module/* /Users/martijn/Documents/parcelpro/magento/src/app/code/Parcelpro/Shipment --exclude '.*/' --exclude vendor
./bin/copytocontainer --all
./bin/magento setup:upgrade
./bin/magento setup:di:compile
./bin/magento setup:static-content:deploy -f
./bin/magento indexer:reindex
./bin/magento cache:clean
./bin/magento module:enable Parcelpro_Shipment