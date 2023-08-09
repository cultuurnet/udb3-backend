# UDB3 with Docker

## Prerequisite
- Install Docker Desktop
- appconfig: you'll have to clone [appconfig](https://github.com/cultuurnet/appconfig) in the same folder as where you will clone [udb3-backend](https://github.com/cultuurnet/udb3-backend)

## Configure

### Configuration setup
To get or update the configuration files, run the following command in the root of the project
```
$ make config
```

### Local host file
To use `udb3-backend` & `udb3-search-service` together, you'll have to add `127.0.0.1 host.docker.internal` to your `/etc/hosts` file.

### RabbitMQ

Login to the management console on http://host.docker.internal:15672/ with username `vagrant` and password `vagrant` 

### Acceptance tests

To run the acceptance tests, you should first setup the data.
This can be done with:
```
$ make feature-init
```
You can run the actual acceptance tests with:
```
$ make feature
```

## Start

### Docker

Start the docker containers with the following command. Make sure to execute this inside the root of the project so the `.env` can be used.
```
$ make up
```

### Migrations & Composer packages

To install all composer packages & migrate the database, run the following command:
```
$ make init
```

### CI

To execute all CI tasks, run the following command:
```
$ make ci
```

### Frontend (WIP)

You can connect the docker backend with the [udb3-frontend](https://github.com/cultuurnet/udb3-frontend), 
by using the `.env` file from https://github.com/cultuurnet/appconfig/blob/main/files/udb3/docker/udb3-frontend/.env.

More info about starting can be found in the [README](https://github.com/cultuurnet/udb3-frontend/blob/main/README.md) of [udb3-frontend](https://github.com/cultuurnet/udb3-frontend).
