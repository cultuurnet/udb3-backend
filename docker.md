# UDB3 with Docker

## Prerequisite
- Install Docker Desktop

## Configure

### .env file
Copy `env.dist` to the root folder and rename it to `.env`

### config.php file

Copy the latest `config.php` from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/udb3-backend to the root

In your `config.php` file, you have to change some of the hosts to work with Docker instead of Vagrant.

You'll need to change the following lines to work with docker hosts:
- url
  - `http://host.docker.internal:8000`
- search.v3.base_url
  - `http://host.docker.internal:9000`
- database.host
  - `mysql`
- cache.redis.host
  - `redis`

### pem files

Copy `public.pem` and `public-auth0.pem` from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/keys to the root

### RabbitMQ

A rabbitmq container is not yet available for the Apple M1 chip. A temporary solution is to use a cloud provider. For example https://www.cloudamqp.com/

You'll have to update your `config.php` file accordingly with the values of your provider:
- amqp.host
- amqp.port
- amqp.user
- amqp.password
- amqp.vhost

Create an exchange `udb3.x.domain-events` in your RabbitMQ provider

### Local host file
To use `udb3-backend` & `udb3-search-service` together, you'll have to add `127.0.0.1 host.docker.internal` to your `/etc/hosts` file.

### Acceptance tests

To make the acceptance tests work with Docker, you'll need to change the `base_url` and `online_location_url` inside `config.yml` of the acceptance test repository.
Give it the same value as the `url` from the modified `config.php` from `udb3-backend`, in this example `http://host.docker.internal:8000`

## Start

### Docker

Start the docker containers with the following command. Make sure to execute this inside the root of the project so the `.env` can be used.
```
$ make up
```

### Composer install

To install all composer packages, run the:
```
$ make install
```

### Migrations

To migrate the database, run the following command:
```
$ make migrate
```

### CI

To execute all CI tasks, run the following command:
```
$ make ci
```