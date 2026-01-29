# SAPI3 Acceptance Tests Improvement

## Overview

This document tracks the improvement of acceptance tests for the SAPI3 (Search API 3) integration.

## Current State

### Test Location
- `features/search/` directory
- Tagged with `@sapi3`

### Current Test Files
- `search-proxy.feature` - Search endpoint proxy tests
- `facets.feature` - Facet response tests
- `auth.feature` - Authentication tests
- `contributors.feature` - Contributor filtering
- `default-queries-*.feature` - Default query filtering

### How Tests Work
1. Create entity (event/place/organizer) via API
2. Poll SAPI3 endpoint waiting for indexing (max 5 seconds)
3. Assert search results

### Test Infrastructure
- `features/bootstrap/FeatureContext.php` - Main Behat context
- `features/Steps/RequestSteps.php` - HTTP requests and indexing wait logic

## Adding New Tests

1. **Verify the actual API response** using curl against the local SAPI3 URL
2. **Write the scenario** in the appropriate feature file under `features/search/`
3. **Run the test** using `make feature-filter path=<feature-file>`

## Goals

<!-- TODO: Define specific goals for test improvements -->

- [ ] Identify gaps in current test coverage
- [ ] Improve test reliability (reduce flakiness)
- [ ] Add missing test scenarios
- [ ] Document SAPI3 container setup

## Progress

<!-- Track progress here as work is done -->

## Open Questions

- What is the SAPI3 container setup? (not in current docker-compose.yml)
- Are there known flaky tests?
- What scenarios are missing coverage?
