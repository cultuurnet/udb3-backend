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
We created some dummy users that can be used in tests:

| E-mail               | UserId                               | Notes                                     |
| -------------------- | ------------------------------------ |-------------------------------------------| 
| dummyuser1@publiq.be | 02566c96-8fd3-4b7e-aa35-cbebe6663b2d |                                           |
| dummyuser2@publiq.be | 79dd2821-3b89-4dbb-9143-920ff2edfa34 |                                           |
| dummyuser3@publiq.be | 92650bd8-037f-4722-a40e-7e0a0bf39a8e |                                           |
| dummyuser4@publiq.be | c9f2a19f-3dd7-401c-ad4d-73db7a9d1748 |                                           |
| dummyuser5@publiq.be | edf305f8-69b6-4553-914e-9ecedcba418e |                                           |
| dummyuser6@publiq.be | 67aebd9b-3033-459c-818e-ca684b3a27b3 | Cannot be used to create extra Ownerships |
