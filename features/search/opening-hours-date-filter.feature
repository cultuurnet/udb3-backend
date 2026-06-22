@sapi3
Feature: Test that opening hours are respected in date search results

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @testIsolation
  Scenario: Permanent event with weekday-only opening hours is not found when querying on a weekend
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    # Saturday July 11: within opening hours time range but on a closed day → not found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-11T09:00:00+02:00 |
      | dateTo                | 2026-07-11T17:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 6: within opening hours on an open day → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent event is only found when the queried time range overlaps with its opening hours
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    # Monday July 6, before opening hours → not found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T07:00:00+02:00 |
      | dateTo                | 2026-07-06T08:30:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 6, querying up to exactly the opening time → found (opens is inclusive)
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T07:00:00+02:00 |
      | dateTo                | 2026-07-06T09:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Monday July 6, after closing hours → not found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T17:30:00+02:00 |
      | dateTo                | 2026-07-06T19:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 6, querying from exactly the closing time → found (closes is inclusive)
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T17:00:00+02:00 |
      | dateTo                | 2026-07-06T19:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Monday July 6, querying from exactly opens to exactly closes → tests both boundaries at once
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Monday July 6, within opening hours → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T10:00:00+02:00 |
      | dateTo                | 2026-07-06T15:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent event with no opening hours is found on any date
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent"
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    # Weekday → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T10:00:00+02:00 |
      | dateTo                | 2026-07-06T15:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Weekend → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-11T10:00:00+02:00 |
      | dateTo                | 2026-07-11T15:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Multiple calendar event is only found on dates matching one of its sub-events
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2026-07-06T10:00:00+02:00",
          "endDate": "2026-07-06T15:00:00+02:00"
        },
        {
          "startDate": "2026-07-13T10:00:00+02:00",
          "endDate": "2026-07-13T15:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    # Monday July 6: matches first sub-event → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T10:00:00+02:00 |
      | dateTo                | 2026-07-06T15:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday July 8: between sub-events → not found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-08T10:00:00+02:00 |
      | dateTo                | 2026-07-08T15:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 13: matches second sub-event → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-13T10:00:00+02:00 |
      | dateTo                | 2026-07-13T15:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic event with summer weekday-only opening hours is not found on a weekend or outside summer
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-08-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    # Saturday July 11: within summer but on a weekend → not found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-11T09:00:00+02:00 |
      | dateTo                | 2026-07-11T17:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday September 7: a weekday but after summer endDate → not found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-09-07T09:00:00+02:00 |
      | dateTo                | 2026-09-07T17:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 6: a weekday within summer → found
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent place with weekday-only opening hours is not found when querying on a weekend
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    # Saturday July 11: within opening hours time range but on a closed day → not found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-11T09:00:00+02:00 |
      | dateTo                | 2026-07-11T17:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 6: within opening hours on an open day → found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent place with no opening hours is found on any date
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "permanent"
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    # Weekday → found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T10:00:00+02:00 |
      | dateTo                | 2026-07-06T15:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Weekend → found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-11T10:00:00+02:00 |
      | dateTo                | 2026-07-11T15:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic place with summer weekday-only opening hours is not found on a weekend or outside summer
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-08-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    # Saturday July 11: within summer but on a weekend → not found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-11T09:00:00+02:00 |
      | dateTo                | 2026-07-11T17:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday September 7: a weekday but after summer endDate → not found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-09-07T09:00:00+02:00 |
      | dateTo                | 2026-09-07T17:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Monday July 6: a weekday within summer → found
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | q                     | %{placeUrl}               |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
