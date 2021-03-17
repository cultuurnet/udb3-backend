# UDB3 backend

udb3-silex is the app that provides most of the backend of UiTdatabank v3, aka UDB3.

## Database migrations

UDB3 Silex uses [Doctrine Migrations](http://doctrine-migrations.readthedocs.org/en/latest/index.html)
to manage database schema updates.

To run the migrations, you can use the following composer script:
```
composer migrate
```
