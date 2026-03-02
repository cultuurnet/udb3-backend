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
2. **Write the scenario** in the appropriate feature file under `features/`
3. **Run the test** using `make feature-filter path=<feature-file>`

## Best Practices

### Use helper steps for creating entities

Reusable steps in `features/Steps/` handle entity creation concisely. Prefer these over manual POST + status check + extract sequences.

**Places** (`PlaceSteps.php`):
```gherkin
# Create a minimal place (uses places/place-with-required-fields.json)
Given I create a minimal place and save the "url" as "placeUrl"
Given I create a minimal place and save the "id" as "placeId"

# Create a place from a specific JSON file
Given I create a place from "places/my-place.json" and save the "url" as "placeUrl"
```

**Events** (`EventSteps.php`):
```gherkin
# Create a minimal permanent event (uses events/event-minimal-permanent.json, requires %{placeUrl})
Given I create a minimal permanent event and save the "url" as "eventUrl"

# Create an event from a specific JSON file
And I create an event from "events/my-event.json" and save the "url" as "eventUrl"
```

**Organizers** (`OrganizerSteps.php`):
```gherkin
# Create a minimal organizer (uses organizers/organizer-minimal.json)
Given I create a minimal organizer and save the "url" as "organizerUrl"

# Create an organizer from a specific JSON file
When I create an organizer from "organizers/my-organizer.json" and save the "url" as "organizerUrl"
```

### Use `%{placeUrl}` in JSON fixtures

In JSON test data files, reference places and events by their saved URL variable rather than constructing the URL manually:
```json
{
  "location": {
    "@id": "%{placeUrl}"
  }
}
```

### No waiting for command completion

All API requests are processed synchronously (except exports). Some older tests still contain `wait for the command` steps; new tests should not include these.

## Features

- [Search](search.md) - SAPI3 search integration tests
