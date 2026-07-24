@sapi3
Feature: Test the dayOfWeek event search filter

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  @testIsolation
  Scenario: Permanent event is matched by a weekday in its opening hours and not by another weekday
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "wednesday", "friday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | tuesday |
      | disableDefaultFilters | true    |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: dayOfWeek matching is case-insensitive
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | Wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | WEDNESDAY |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | wEdNeSdAy |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Multiple dayOfWeek values are OR-combined using the array syntax
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["sunday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The event only occurs on Sunday, so a set that includes Sunday matches.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek[]           | friday   |
      | dayOfWeek[]           | saturday |
      | dayOfWeek[]           | sunday   |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # A set that does not include Sunday does not match.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek[]           | friday   |
      | dayOfWeek[]           | saturday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple dayOfWeek values are OR-combined using the comma-separated syntax
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["sunday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | friday,saturday,sunday |
      | disableDefaultFilters | true                   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | monday,tuesday |
      | disableDefaultFilters | true           |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic event spanning several months is matched based on its opening hours weekday
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-11-30T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["thursday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | thursday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | friday |
      | disableDefaultFilters | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic event weekday is matched even when some of those weekdays are closed
    # The event runs every Wednesday for several months, with two individual Wednesdays
    # marked as closed. Closed days must be ignored, so dayOfWeek=wednesday still matches.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-11-30T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-08-05",
          "endDate": "2026-08-05",
          "description": {"nl": "Gesloten"}
        },
        {
          "startDate": "2026-08-12",
          "endDate": "2026-08-12",
          "description": {"nl": "Gesloten"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic event with fewer than four occurrences on a weekday is not matched by dayOfWeek
    # The event runs on Wednesdays but only spans two weeks (2026-08-05 and 2026-08-12),
    # so the weekday occurs fewer than the required minimum number of times (4).
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-08-16T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the dayOfWeek filter the event is returned, so it is searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # With the dayOfWeek filter it is not returned, because Wednesday occurs fewer than four times.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event maps single-day sub-events to their weekday
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-08-26T18:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T18:00:00+02:00"},
        {"startDate": "2026-08-12T10:00:00+02:00", "endDate": "2026-08-12T18:00:00+02:00"},
        {"startDate": "2026-08-19T10:00:00+02:00", "endDate": "2026-08-19T18:00:00+02:00"},
        {"startDate": "2026-08-26T10:00:00+02:00", "endDate": "2026-08-26T18:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # All four sub-events fall on a Wednesday.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | thursday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event expands multi-day sub-events to every weekday in their range
    # Each sub-event runs Friday to Sunday, so it covers Friday, Saturday and Sunday.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-09-04T10:00:00+02:00",
      "endDate": "2026-09-27T18:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-09-04T10:00:00+02:00", "endDate": "2026-09-06T18:00:00+02:00"},
        {"startDate": "2026-09-11T10:00:00+02:00", "endDate": "2026-09-13T18:00:00+02:00"},
        {"startDate": "2026-09-18T10:00:00+02:00", "endDate": "2026-09-20T18:00:00+02:00"},
        {"startDate": "2026-09-25T10:00:00+02:00", "endDate": "2026-09-27T18:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Saturday sits in the middle of each Friday-to-Sunday range, so it must be part of the set.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | saturday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The union OR-combines with the other days in the range too.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | friday,sunday |
      | disableDefaultFilters | true          |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # No sub-event ever touches a Monday.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | monday |
      | disableDefaultFilters | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Single calendar event is out of scope and not matched by dayOfWeek
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-08-05T18:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T18:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # The single event takes place on a Wednesday and is searchable without the dayOfWeek filter.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # But single calendar events are never matched by the dayOfWeek filter.
    When I send a GET request to "/events" with parameters:
      | dayOfWeek             | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: An invalid weekday value is rejected with a validation error
    When I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | dayOfWeek             | someday |
      | disableDefaultFilters | true    |
    Then the response status should be "404"
