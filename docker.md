# UDB3 with Docker

## Prerequisite
- Install Docker Desktop
- appconfig: you'll have to clone [appconfig](https://github.com/cultuurnet/appconfig) in the same folder as where you will clone [udb3-backend](https://github.com/cultuurnet/udb3-backend)

## Configure

### Configuration setup
To get or update the configuration files, run the following command in the root of the project.
You will also need sudo privileges on the first run to add `127.0.0.1 io.uitdatabank.local` to your `/etc/hosts` file.

```
$ make config
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

### RabbitMQ

Login to the management console on http://io.uitdatabank.local:15672/ with username `vagrant` and password `vagrant` 

### Mailpit

You can check send mails of UiTdatabank on http://mailpit.uitdatabank.local:8025/
The API of Mailpit can be found on http://mailpit.uitdatabank.local:8025/api/v1/

### Acceptance tests

To run the acceptance tests for the very first time you need to initialize test data. This required test data contains several fixed labels and roles which are used by various acceptance tests.
This can be done with:
```
$ make feature-init
```
You can run the actual acceptance tests with:
```
$ make feature
```
