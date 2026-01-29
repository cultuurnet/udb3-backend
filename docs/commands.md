# Commands

> **Important**: Always use `make` commands, not direct `composer` or `vendor/bin` calls. The project runs in Docker.

## Quick Reference

| Task | Command |
|------|---------|
| Verify all changes | `make ci` |
| Run unit tests | `make test` |
| Run specific test | `make test-filter filter=ClassName` |
| Fix code style | `make cs-fix` |
| Start environment | `make up` |
| Shell access | `make bash` |

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
make feature                                 # All feature tests
make feature-tag tag=@events                 # Feature tests by tag
make feature-tag tag=@sapi3                  # SAPI3 search integration tests
```

## AMQP Consumer

```bash
# Run message consumer (inside container)
php bin/console consume [consumer_name]
```

## Resources

- [Broadway Documentation](https://github.com/broadway/broadway)
- [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html)
- [PHPStan](https://phpstan.org/)
