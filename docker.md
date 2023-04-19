# UDB3 with Docker

## Prerequisite
- Install Docker Desktop
- appconfig: you'll have to clone [appconfig](https://github.com/cultuurnet/appconfig) in the same folder as where you will clone [udb3-backend](https://github.com/cultuurnet/udb3-backend)

## Configure

### Local host file
To use `udb3-backend` & `udb3-search-service` together, you'll have to add `127.0.0.1 host.docker.internal` to your `/etc/hosts` file.

### RabbitMQ

Login to the management console on http://host.docker.internal:15672/ with username `vagrant` and password `vagrant` 

### Acceptance tests

To make the acceptance tests work with Docker, you'll need to change the `base_url`, `search_api_base_url` and `online_location_url` inside `config.yml` of the acceptance test repository.

Give them the same value as the `url` from the modified `config.php` from `udb3-backend`, in this example `http://host.docker.internal:8000`

For search we need to use port `9000`

```
base_url: 'http://host.docker.internal:8000'
online_location_url: 'http://host.docker.internal:8000/place/00000000-0000-0000-0000-000000000000'
search_api_base_url: 'http://host.docker.internal:9000'
```

## Start

### Configuration setup
To get or update the configuration files, run the following command in the root of the project
```
$ make config
```

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

## Config changes
To avoid duplicate out-of-sync development config files, 
all changes/additions of the config files should be put in the udb3-vagrant repo.
Only settings that need a different value for the docker setup should be put in docker-config.php!
