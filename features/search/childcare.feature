@sapi3
Feature: Test the hasChildcare offer search filter

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  Scenario: Single event with sub-event childcare is matched by hasChildcare=true
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-08-03T10:00:00+02:00",
      "endDate": "2026-08-03T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-08-03T10:00:00+02:00",
          "endDate": "2026-08-03T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  Scenario: Multiple event with childcare on one sub-event is matched by hasChildcare=true
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-10T10:00:00+02:00",
      "endDate": "2026-08-11T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-08-10T10:00:00+02:00",
          "endDate": "2026-08-10T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        },
        {
          "startDate": "2026-08-11T10:00:00+02:00",
          "endDate": "2026-08-11T18:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  Scenario: Periodic event with childcare on an opening hour is matched by hasChildcare=true
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {"start": "08:00", "end": "18:00"},
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0


  Scenario: Permanent event with childcare on an opening hour is matched by hasChildcare=true
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {"start": "08:00", "end": "18:00"},
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  Scenario: Event without childcare is matched by hasChildcare=false
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-08-03T10:00:00+02:00",
      "endDate": "2026-08-03T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-08-03T10:00:00+02:00",
          "endDate": "2026-08-03T18:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasChildcare          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Omitting hasChildcare applies no childcare filtering, so the event is still returned.
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  Scenario: Childcare hours do not extend the event date range
    # The activity runs 10:00-18:00 but childcare is configured for the wider 08:00-19:00 window.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-08-17T10:00:00+02:00",
      "endDate": "2026-08-17T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-08-17T10:00:00+02:00",
          "endDate": "2026-08-17T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # A date filter covering the activity window returns the event.
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | dateFrom              | 2026-08-17T10:00:00+02:00 |
      | dateTo                | 2026-08-17T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # A date filter covering only the childcare-only window (before the activity) returns nothing.
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | dateFrom              | 2026-08-17T08:00:00+02:00 |
      | dateTo                | 2026-08-17T09:59:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  Scenario: hasChildcare=true combines with a matching date filter
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-08-24T10:00:00+02:00",
      "endDate": "2026-08-24T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-08-24T10:00:00+02:00",
          "endDate": "2026-08-24T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | hasChildcare          | true                      |
      | dateFrom              | 2026-08-24T10:00:00+02:00 |
      | dateTo                | 2026-08-24T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | hasChildcare          | false                     |
      | dateFrom              | 2026-08-24T10:00:00+02:00 |
      | dateTo                | 2026-08-24T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  Scenario: Places are never matched by hasChildcare=true
    Given I wait for the place with url "%{placeUrl}" to be indexed
    When I am using the Search API v3 base URL
    # Without the filter the place is found, proving it is indexed and searchable.
    When I send a GET request to "/places" with parameters:
      | q                     | %{placeUrl} |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q                     | %{placeUrl} |
      | hasChildcare          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
