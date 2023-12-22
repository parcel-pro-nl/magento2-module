# Development Guide

A Docker Compose setup for Magento 2, based around the [bitnami/magento](https://hub.docker.com/r/bitnami/magento) image.

## Starting Up

Start everything:

```shell
docker compose up -d
```

You can now view Magento on http://localhost, and the admin portal can be found on http://localhost/admin.

Default credentials:

- Username: `admin`
- Password: `parcelpro1`

## Shutting Down

Stop the containers, but keep their data:

```shell
docker compose stop
```

Stop and remove the containers and volumes:

```shell
docker compose down -v
```

## Shell

To get a shell in the running Magento container, run `./cli.sh`.
