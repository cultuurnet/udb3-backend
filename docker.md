# UDB3 with Docker

## Prerequisite
- Install Docker Desktop

## Configure

### .env file
Copy `env.dist` to the root folder and rename it to `.env`

### config.php file

Get the latest `config.php` from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/udb3-backend

In your `config.php` file, you have to change some of the hosts to work with Docker instead of Vagrant.

You'll need to change the following lines to work with docker hosts:
- url
  - `http://localhost:8000`
- database.host
  - `mysql`
- cache.redis.host
  - `redis`

### RabbitMQ

A rabbitmq container is not yet available for the Apple M1 chip. A temporary solution is to use a cloud provider. For example https://www.cloudamqp.com/

You'll have to update your `config.php` file accordingly with the values of your provider:
- amqp.host
- amqp.port
- amqp.user
- amqp.password
- amqp.vhost


### Acceptance tests

To make the acceptance tests work with Docker, you'll need to change the `base_url` inside `config.yml` of the acceptance test repository.


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