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
2. Wait for each modified entity to be indexed at its current playhead version
3. Assert search results

## Writing Stable Tests

UDB3 indexes entities into Elasticsearch asynchronously. To prevent intermittent failures, every SAPI3 assertion block must be preceded by an indexing wait for **every entity that was created or modified since the previous assertion block**.

### The stable pattern

```gherkin
# Setup — all creates and updates first
When I create a minimal place and save the "id" as "placeId"
And I publish the place at "/places/%{placeId}"
And I create an event from "events/foo.json" and save the "id" as "eventId"
And I send a PUT request to "/events/%{eventId}/labels/%{labelname}"
# Wait for every modified entity — right before switching to SAPI3
And I wait for the place with url "/places/%{placeId}" to be indexed
And I wait for the event with url "/events/%{eventId}" to be indexed
And I am using the Search API v3 base URL
When I send a GET request to "/events" with parameters:
  | q | id:%{eventId} |
Then the JSON response at "totalItems" should be 1    # direct assertion — no polling step
```

### Multiple modification rounds

When a scenario returns to UDB3 to make further changes after an initial assertion block, wait again for the re-modified entities before the next SAPI3 block:

```gherkin
# Round 1
And I wait for the event with url "/events/%{eventId}" to be indexed
And I am using the Search API v3 base URL
When I send a GET request to "/events" with parameters: ...
Then ...

# Round 2
When I am using the UDB3 base URL
And I send a PUT request to "/events/%{eventId}/labels/%{label2}"
And I wait for the event with url "/events/%{eventId}" to be indexed   # wait again
And I am using the Search API v3 base URL
When I send a GET request to "/events" with parameters: ...
Then ...
```

### Step reference

The wait step exists for all three entity types:

```gherkin
And I wait for the event with url "/events/%{eventId}" to be indexed
And I wait for the place with url "/places/%{placeId}" to be indexed
And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
```

If the creation step saved the `url` instead of the `id`, use the variable directly:

```gherkin
And I wait for the place with url "%{placeUrl}" to be indexed
```

## Query Parameters

SAPI3 supports two types of query parameters:

- **URL parameters**: Direct query string parameters (e.g., `labels=my-label`)
- **Advanced query parameter**: The `q` parameter using Lucene syntax (e.g., `q=labels:my-label`)

## Test Isolation

Search tests can use scenario-based label isolation to prevent interference from other tests:

- Tag scenarios with `@testIsolation` to enable isolation
- A unique label (`scenario-{uuid}`) is automatically generated per scenario
- The label is added to all fixtures created during the scenario
- Search queries automatically filter by this label

This ensures each scenario only sees its own data, regardless of what other tests create.
