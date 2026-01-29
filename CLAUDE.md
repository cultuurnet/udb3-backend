# UDB3 Backend

UiTdatabank 3 core application - an event-sourced backend built with Broadway.

## Quick Reference

| Task | Command |
|------|---------|
| Verify all changes | `make ci` |
| Run unit tests | `make test` |
| Run specific test | `make test-filter filter=ClassName` |
| Fix code style | `make cs-fix` |
| Start environment | `make up` |
| Shell access | `make bash` |

---

## 1. Tech Stack

- **Language**: PHP 8.1+
- **Architecture**: Event Sourcing with [Broadway](https://github.com/broadway/broadway)
- **Database**: Doctrine DBAL
- **Testing**: PHPUnit (unit), Behat (feature)
- **Quality**: PHPStan (static analysis), PHP-CS-Fixer (code style)
- **Environment**: Docker with docker-compose

---

## 2. Project Structure

```
├── app/                 # Service providers and application wiring
├── src/                 # Core domain code
├── tests/               # PHPUnit tests (mirrors src/ structure)
├── features/            # Behat feature tests
├── docker/              # Docker configuration
└── vendor/              # Dependencies (never modify)
```

---

## 3. Development Commands

### Environment Management

```bash
make up          # Start containers
make down        # Stop containers
make bash        # Shell into PHP container
make install     # Install composer dependencies
make migrate     # Run database migrations
make init        # Install + migrate (fresh setup)
```

### Quality Assurance

```bash
make ci          # Run ALL checks (PHPStan + tests + code style)
make stan        # PHPStan static analysis only
make test        # PHPUnit tests only
make cs          # Code style check (dry-run)
make cs-fix      # Auto-fix code style issues
```

### Testing

```bash
make test                                    # All unit tests
make test-filter filter=EventBusForwarding   # Tests matching filter
make test-group group=integration            # Tests in specific group
make feature                                 # Behat feature tests
make feature-tag tag=@events                 # Feature tests by tag
```

> **Important**: Always use `make` commands, not direct `composer` or `vendor/bin` calls. The project runs in Docker.

---

## 4. Architecture Patterns

### Event Sourcing with Broadway

- **Aggregates**: Domain objects that emit events (in `src/`)
- **Projectors**: Build read models from events
- **Event Bus**: Distributes events to handlers

### Dependency Injection

- Service providers in `app/` wire all dependencies
- Use **constructor injection**, not service locators
- Extract testable logic into separate classes with **interfaces**

### Example: Extracting Testable Logic

```php
// Instead of embedding logic in a class, extract to interface + implementation:
interface DatabaseConnectionChecker
{
    public function ensureConnection(): void;
}

final class DBALDatabaseConnectionChecker implements DatabaseConnectionChecker
{
    // Implementation here - can be tested independently
}
```

---

## 5. Code Conventions

### General

- Use `final` classes by default
- Follow PSR-4 autoloading under `CultuurNet\UDB3\` namespace
- Keep classes small and focused (Single Responsibility)

### PHP 8.1 Features

Use **readonly properties** for immutable class properties:

```php
// Preferred: readonly properties
final class EventCreated
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $title,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }
}
```

> **Note**: Do NOT use PHP 8.2 `readonly class` syntax - we're on PHP 8.1.

### Testing

- Use `@test` annotation style for test methods
- Mock objects use intersection types: `Connection&MockObject`
- Prefer separate test methods over data providers for clarity
- Test file location mirrors source: `src/Foo/Bar.php` → `tests/Foo/BarTest.php`

### Naming

- Interfaces: Descriptive name without `Interface` suffix (e.g., `DatabaseConnectionChecker`)
- Implementations: Prefix with technology (e.g., `DBALDatabaseConnectionChecker`)

---

## 6. Gotchas & Pitfalls

| Issue | Solution |
|-------|----------|
| Commands fail outside Docker | Use `make` commands, not direct `composer` calls |
| `withConsecutive` deprecation | Avoid in new tests - use separate test methods |
| Uppercase `String` type hint | Legacy code - don't "fix" these |
| Tests fail after changes | Run `make ci` before committing |

---

## 7. Restrictions

- **Never** modify files in `vendor/`
- **Never** commit `.env` or credentials files
- **Avoid** creating new service providers - extend existing ones in `app/`
- **Avoid** adding dependencies without team discussion
- **Always** run `make ci` before considering work complete

---

## 8. Preferences

When working on this codebase, prefer:

- Extracting logic into testable classes with interfaces over inline implementation
- Constructor injection over service locator patterns
- Small, focused commits over large changes
- Explicit code over clever abstractions
- Integration with existing patterns over introducing new ones

---

## 9. Resources

- [Broadway Documentation](https://github.com/broadway/broadway)
- [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html)
- [PHPStan](https://phpstan.org/)
