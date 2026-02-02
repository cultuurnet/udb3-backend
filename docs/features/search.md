# Search Acceptance Tests

## Overview

Acceptance tests for the SAPI3 (Search API 3) integration.

## Test Location

- `features/search/` directory
- Tagged with `@sapi3`

## Test Files

- `auth.feature` - Authentication tests
- `pagination.feature` - Pagination and sorting tests
- `search-proxy.feature` - Search endpoint proxy tests
- `facets.feature` - Facet response tests
- `contributors.feature` - Contributor filtering
- `default-queries-*.feature` - Default query filtering

## How Tests Work

1. Create entity (event/place/organizer) via API
2. Poll SAPI3 endpoint waiting for indexing (max 5 seconds)
3. Assert search results

## Query Parameters

SAPI3 supports two types of query parameters:

- **URL parameters**: Direct query string parameters (e.g., `labels=my-label`)
- **Advanced query parameter**: The `q` parameter using Lucene syntax (e.g., `q=labels:my-label`)

## Test Isolation

Search tests can use scenario-based label isolation to prevent interference from other tests:

- Tag scenarios with `@labelIsolation` to enable isolation
- A unique label (`scenario-{uuid}`) is automatically generated per scenario
- The label is added to all fixtures created during the scenario
- Search queries automatically filter by this label

This ensures each scenario only sees its own data, regardless of what other tests create.
