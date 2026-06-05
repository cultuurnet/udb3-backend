@sapi3
Feature: Test that closed days are excluded from calendar search results

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @testIsolation
  Scenario: Periodic event closed day is excluded from search results
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent event closed day is excluded from search results
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
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic place closed day is excluded from search results
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent place closed day is excluded from search results
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
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-06T09:00:00+02:00 |
      | dateTo                | 2026-07-06T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-07T09:00:00+02:00 |
      | dateTo                | 2026-07-07T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic event multi-day closed range is excluded from search results
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-10"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-08T09:00:00+02:00 |
      | dateTo                | 2026-07-08T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-13T09:00:00+02:00 |
      | dateTo                | 2026-07-13T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic place multi-day closed range is excluded from search results
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-10"
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-08T09:00:00+02:00 |
      | dateTo                | 2026-07-08T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-13T09:00:00+02:00 |
      | dateTo                | 2026-07-13T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic event adjusted day is searchable within adjusted hours
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-07-08",
          "endDate": "2026-07-08",
          "openingHours": [
            {
              "dayOfWeek": ["wednesday"],
              "opens": "10:00",
              "closes": "13:00"
            }
          ]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-08T10:00:00+02:00 |
      | dateTo                | 2026-07-08T13:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-08T14:00:00+02:00 |
      | dateTo                | 2026-07-08T17:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic event exceptionally open adjusted day is searchable
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-07-11",
          "endDate": "2026-07-11",
          "openingHours": [
            {
              "dayOfWeek": ["saturday"],
              "opens": "10:00",
              "closes": "14:00"
            }
          ]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-11T10:00:00+02:00 |
      | dateTo                | 2026-07-11T14:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-18T10:00:00+02:00 |
      | dateTo                | 2026-07-18T14:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Closed day takes precedence over adjusted day for a periodic event
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06"
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-07-06",
          "endDate": "2026-07-06",
          "openingHours": [
            {
              "dayOfWeek": ["monday"],
              "opens": "10:00",
              "closes": "13:00"
            }
          ]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-07-06T10:00:00+02:00 |
      | dateTo                | 2026-07-06T13:00:00+02:00 |
      | status                | Available                 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic place adjusted day is searchable within adjusted hours
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-07-01T00:00:00+02:00",
      "endDate": "2026-12-31T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursAdjustedDays": [
        {
          "startDate": "2026-07-08",
          "endDate": "2026-07-08",
          "openingHours": [
            {
              "dayOfWeek": ["wednesday"],
              "opens": "10:00",
              "closes": "13:00"
            }
          ]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-08T10:00:00+02:00 |
      | dateTo                | 2026-07-08T13:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | dateFrom              | 2026-07-08T14:00:00+02:00 |
      | dateTo                | 2026-07-08T17:00:00+02:00 |
      | status                | Available                 |
      | q                     | %{placeUrl}              |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
