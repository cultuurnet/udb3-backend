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
