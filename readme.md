# UDB3 backend

udb3-silex is the app that provides most of the backend of UiTdatabank v3, aka UDB3.

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

## Logs

Logs are located in the `./logs` directory.

### General logs

- `web.log` contains unforeseen errors/exceptions that occurred in HTTP requests
- `cli.log` contains CLI errors that did not get caught and logged to the other logs listed below
  
### Worker logs

The following logs contain info about CLI commands that run continuously as supervisor scripts.

- `command_bus.log` contains logs about the commands handled by resque workers (e.g. bulk labelling and exports)
- `amqp.curators.log` contains logs about the [uit-curatoren](https://github.com/cultuurnet/uit-curatoren/) events that get processed through the `amqp-listen-curators` CLI command
- `amqp.json-imports.log` contains logs about JSON-LD imports that get processed through the `amqp-listen-imports` CLI command
- `amqp.xml-imports.log` contains logs about XML imports that get processed through the `amqp-listen` CLI command
- `amqp.uitpas.log` contains logs about UiTPAS events that get processed through the `amqp-listen-uitpas` CLI command
- `resque.bulk-labelling.log` contains logs about the resque worker for bulk labelling
  
### Service logs

The following logs contain info about specific services that can be part of HTTP requests, CLI commands, or both.

- `service.xml-conversion.log` contains logs about parsing/projection of previously imported XML
- `service.json-imports.log` contains logs about JSON-LD imports
- `service.labels.log` contains logs about label (aggregates)
- `service.media.log` contains logs about the media manager, i.e. about uploads and edits of images and media objects
- `service.geo-coordinates.log` contains logs about the geocoding of organizers and places

### Adding a new logger

Use the `LoggerFactory::create()` method to quickly create a new logger. This way it gets stored in the right directory, correct formatting of exceptions, automatic Sentry integration, etc.
