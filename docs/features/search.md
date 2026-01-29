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
