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

## Documentation

- [Architecture](docs/architecture.md) - System design, tech stack, message flow
- [Coding Guidelines](docs/coding-guidelines.md) - Code style, conventions, security
- [Commands](docs/commands.md) - All available make commands
- [Review Guidelines](docs/review-guidelines.md) - PR review process and focus areas

## Refactoring Plans

- [SAPI3 Acceptance Tests](docs/refactor/sapi3-acceptance-tests.md) - Search integration test improvements
