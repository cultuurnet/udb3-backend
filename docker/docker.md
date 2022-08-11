In your config.yml file, you have to change some of the hosts to work with Docker instead of Vagrant.
You'll need to change the following lines to work with docker hosts:
- url
- database.host
- cache.redis.host

A rabbitmq container is not yet available for the Apple M1 chip. A temporary solution is to use a cloud provider. You'll
have to update your config.yml file accordingly.
