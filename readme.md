# UDB3 backend

This repository contains the PHP app that provides most of the backend of UiTdatabank v3, aka UDB3.

## Setup
You can find a full guide on how to setup the project [here](docker.md)

## Contributing

Several CI checks have been provided to make sure any changes are compliant with our coding standards and to detect potential bugs.

You can run all CI checks combined using the following composer script:
```
composer ci
```

Or run them individually:

- `composer test` for tests
- `composer phpstan` for static analysis
- `composer cs` for detecting coding standards violations
- `composer cs-fix` for fixing coding standards violations (where possible)

These checks will also run automatically for every PR.

## Database migrations

We use [Doctrine Migrations](http://doctrine-migrations.readthedocs.org/en/latest/index.html) to manage database schema updates.

To run the migrations, you can use the following composer script:
```
composer migrate
```

## Docker with Xdebug

The docker file is provided with an optional profile to enable Xdebug.

The first time you have to build both versions:

### Install without Xdebug

```
docker-compose up -d
```

### Install profile with Xdebug

```
docker-compose --profile xdebug up -d
```

You don't have to rebuild to switch, *you can just switch between versions in your docker engine*.
Both version are running at the same time, the image without xdebug at port 8000 and the image with xdebug at port 8001.

### Using xdebug
To bash inside the xdebug enable container, use 
```
make bash-xdebug
```

Xdebug is configured to run with trigger mode, meaning you have to modify the request to enable xdebug: 

- *API*: ADD XDEBUG_TRIGGER as a GET or POST variable, for example in Postman.
- *Browser*: install a [browser debugging extension](https://www.jetbrains.com/help/phpstorm/browser-debugging-extensions.html#xdebug-helper-extension)
- *CLI*: (useful with unit tests):
```
make bash-xdebug
source ./bin/start-xdebug.sh
```

## Logs

Logs are located in the `./logs` directory.

### General logs

- `web.log` contains unforeseen errors/exceptions that occurred in HTTP requests
- `cli.log` contains CLI errors that did not get caught and logged to the other logs listed below
  
### Worker logs

The following logs contain info about CLI commands that run continuously.

- `amqp.uitpas.log` contains logs about UiTPAS events that get processed through the `amqp-listen-uitpas` CLI command
- `resque.bulk-label-offer.log` contains logs about the resque worker for the `bulk_label_offer` queue
- `resque.event-export.log` contains logs about the resque worker for the `event_export` queue
  
### Service logs

The following logs contain info about specific services that can be part of HTTP requests, CLI commands, or both.

- `service.xml-conversion.log` contains logs about parsing/projection of previously imported XML
- `service.json-imports.log` contains logs about JSON-LD imports
- `service.labels.log` contains logs about label (aggregates)
- `service.media.log` contains logs about the media manager, i.e. about uploads and edits of images and media objects
- `service.geo-coordinates.log` contains logs about the geocoding of organizers and places
- `service.uitpas.log` contains logs about general calls _to_ UiTPAS, e.g. to check for ticket sales

### Adding a new logger

Use the `LoggerFactory::create()` method to quickly create a new logger. This way it gets stored in the right directory, correct formatting of exceptions, automatic Sentry integration, etc.
