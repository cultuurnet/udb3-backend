# Acceptance Tests

## Overview

Acceptance tests are written using Behat and located in the `features/` directory.

## Test Infrastructure

- `features/bootstrap/FeatureContext.php` - Main Behat context
- `features/Steps/` - Reusable step definitions

## Running Tests

See [Commands](../commands.md) for all available test commands.

## Adding New Tests

1. **Verify the actual API response** using curl against the local SAPI3 URL
2. **Write the scenario** in the appropriate feature file under `features/search/`
3. **Run the test** using `make feature-filter path=<feature-file>`

## Features

- [Search](search.md) - SAPI3 search integration tests
