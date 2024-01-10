#!/usr/bin/env bash
set -o errexit

# Create the Magento install directory then go into it:
mkdir -p ./Magento

DOMAIN=${1:-magento.test}
VERSION=${2:-2.4.6-p3}
EDITION=${3:-community}

git init -qqq
git remote add origin https://github.com/parcel-pro-nl/docker-magento
git fetch origin -qqq
git checkout origin/master -- compose
mv compose/* ./
mv compose/.gitignore ./
mv compose/.vscode ./
rm -rf compose .git
git init

# Ensure these are created so Docker doesn't create them as root
mkdir -p ~/.composer ~/.ssh

# &&'s are used below otherwise onelinesetup script fails/errors after bin/download
bin/download "${VERSION}" "${EDITION}" \
  && bin/setup "${DOMAIN}"
