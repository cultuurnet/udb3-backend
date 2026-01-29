# Search Acceptance Tests

## Overview

Acceptance tests for the SAPI3 (Search API 3) integration.

## Test Location

- `features/search/` directory
- Tagged with `@sapi3`

## Test Files

- `auth.feature` - Authentication tests
- `search-proxy.feature` - Search endpoint proxy tests
- `facets.feature` - Facet response tests
- `contributors.feature` - Contributor filtering
- `default-queries-*.feature` - Default query filtering

## How Tests Work

1. Create entity (event/place/organizer) via API
2. Poll SAPI3 endpoint waiting for indexing (max 5 seconds)
3. Assert search results

## Adding New Tests

1. **Verify the actual API response** using curl against the local SAPI3 URL
2. **Write the scenario** in the appropriate feature file under `features/search/`
3. **Run the test** using `make feature-filter path=<feature-file>`