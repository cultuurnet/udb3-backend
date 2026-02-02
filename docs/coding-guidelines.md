# Coding Guidelines

## General

- Use `final` classes by default
- Follow PSR-4 autoloading under `CultuurNet\UDB3\` namespace
- Keep classes small and focused (Single Responsibility)

## PHP 8.1 Features

Use **readonly properties** with constructor promotion for immutable class properties.

See `src/Doctrine/DBALDatabaseConnectionChecker.php` for an example.

> **Note**: Do NOT use PHP 8.2 `readonly class` syntax - we're on PHP 8.1.

## Comments & Self-Documenting Code

Avoid comments in favor of self-documenting code:

- **Type hints**: Use typed parameters, typed properties and return types instead of docblock descriptions
- **Method names**: Use descriptive, self-explaining method and function names
- **Variable names**: Choose clear, meaningful variable names that convey purpose
- **Class names**: Use descriptive class names that explain their responsibility
- **File names**: Mirror class names for easy discoverability

Comments are acceptable when explaining **why** something is done (not **what**), such as workarounds for external limitations or non-obvious business rules.

## Naming

- Interfaces: Descriptive name without `Interface` suffix (e.g., `DatabaseConnectionChecker`)
- Implementations: Prefix with technology (e.g., `DBALDatabaseConnectionChecker`)

## Testing

- Use `@test` annotation style for test methods
- Mock objects use intersection types: `Connection&MockObject`
- Prefer separate test methods over data providers for clarity
- Test file location mirrors source: `src/Foo/Bar.php` → `tests/Foo/BarTest.php`

## Gotchas & Pitfalls

| Issue | Solution |
|-------|----------|
| Commands fail outside Docker | Use `make` commands, not direct `composer` calls |
| `withConsecutive` deprecation | Avoid in new tests - use separate test methods |
| Uppercase `String` type hint | Legacy code - don't "fix" these |
| Tests fail after changes | Run `make ci` before committing |

## Restrictions

- **Never** modify files in `vendor/`
- **Never** commit `.env` or credentials files
- **Avoid** creating new service providers - extend existing ones in `app/`
- **Avoid** adding dependencies without team discussion
- **Always** run `make ci` before considering work complete

## AI Restrictions

- **Never** create commits - only humans commit code
- **Plan** larger changes but implement step by step, waiting for human review between steps

## Dependency Injection

- Service providers in `app/` wire all dependencies
- Use **constructor injection**, not service locators
- Extract testable logic into separate classes with **interfaces**

See `src/Doctrine/DatabaseConnectionChecker.php` and `src/Doctrine/DBALDatabaseConnectionChecker.php` for an example of interface + implementation pattern.

## Workflow

- **Small commits**: One logical change per commit
- **Small pull requests**: Easier to review, faster to merge
- **Feature flags**: Deploy often, enable features when ready

## Preferences

When working on this codebase, prefer:

- Extracting logic into testable classes with interfaces over inline implementation
- Constructor injection over service locator patterns
- Explicit code over clever abstractions
- Integration with existing patterns over introducing new ones

## Security

### Never Commit

- `.env` files or environment-specific configurations
- API keys, tokens, or credentials
- Private keys or certificates
- Database connection strings with passwords
- Any hardcoded secrets

### Configuration

- Secrets belong in environment variables, not in code
- Use `.env.example` or `.env.dist` for documenting required env vars (without actual values)
- Configuration files with secrets should be in `.gitignore`

### Secure Coding

- No hardcoded credentials or secrets in code
- No sensitive data in log statements
- Validate input on user-provided data
- SQL queries use parameterized statements (handled by Doctrine DBAL)
- No sensitive data exposure in error messages
