# Commands

> **Important**: Always use `make` commands, not direct `composer` or `vendor/bin` calls. The project runs in Docker.

## Environment Management

```bash
make up          # Start containers
make down        # Stop containers
make bash        # Shell into PHP container
make install     # Install composer dependencies
make migrate     # Run database migrations
make init        # Install + migrate (fresh setup)
```

## Quality Assurance

```bash
make ci          # Run ALL checks (PHPStan + tests + code style)
make stan        # PHPStan static analysis only
make test        # PHPUnit tests only
make cs          # Code style check (dry-run)
make cs-fix      # Auto-fix code style issues
```

## Unit Testing

```bash
make test                                    # All unit tests
make test-filter filter=EventBusForwarding   # Tests matching filter
make test-group group=integration            # Tests in specific group
```

## Acceptance Testing (Behat)

```bash
make feature                                           # All feature tests
make feature-tag tag=@events                           # Feature tests by tag
make feature-tag tag=@sapi3                            # SAPI3 search integration tests
make feature-filter path=features/search/auth.feature  # All scenarios in a file
make feature-filter path=features/search/auth.feature:25  # Single scenario by line number
```
