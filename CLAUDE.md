# UDB3 Backend

UiTdatabank 3 core application - an event-sourced backend built with Broadway.

## Documentation

Note for AI: Make sure you read the documentation in the `docs/` directory for a comprehensive understanding of the system. Key documents include:

- [Domain](docs/domain.md) - Core domain concepts (Event, Place, Organizer, Offer)
- [Architecture](docs/architecture.md) - System design, tech stack, message flow
- [Coding Guidelines](docs/coding-guidelines.md) - Code style, conventions, security
- [Commands](docs/commands.md) - All available make commands
- [Review Guidelines](docs/review-guidelines.md) - PR review process and focus areas

## Acceptance Tests

- [General](docs/features/general.md) - Acceptance test overview

## How to run tests?
You cannot directly run tests in this repository. Instead, you need to run the `make test` command from the root of the project. This will execute all tests across the entire codebase, including those in this repository. You can also run `make ci` to run phpstan and php-cs-fixer in addition to the tests.

## Code Coverage

The CI pipeline generates a Clover XML coverage report (`coverage.xml`) and makes it available in the workspace. If `coverage.xml` is present, read it and include coverage insights in your review:

- Which classes or methods in `src/` have zero coverage?
- Which files have line coverage below 80%?
- What are the uncovered line numbers in a specific file (e.g. `src/Event/Event.php`)?
- Given the uncovered lines in a file, what test cases are missing?
- Which namespaces have the lowest overall coverage?
