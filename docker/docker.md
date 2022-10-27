# UDB3 with Docker

## Prerequisite
- Install Docker Desktop

## Setup

### .env file
Copy `env.dist` to the root folder and rename it to `.env`

### docker-compose file
Copy `docker-compose.yml.dist` to the root folder and rename it to `docker-compose.yml`

### config.php file

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
