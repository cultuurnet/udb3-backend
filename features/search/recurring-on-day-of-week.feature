@sapi3
Feature: Test the recurringOnDayOfWeek search filter

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @testIsolation
  Scenario: Permanent event is matched on the weekdays of its opening hours
    Given I create a minimal place and save the "url" as "placeUrl"
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
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Matching is case-insensitive, so the capitalised weekday returns the same result.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | Wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Tuesday is not part of the opening hours.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | tuesday |
      | disableDefaultFilters | true    |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The comma-separated syntax OR-combines the weekdays, so friday alone is enough to match.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | saturday,friday |
      | disableDefaultFilters | true            |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | tuesday,thursday |
      | disableDefaultFilters | true             |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The recurringOnDayOfWeek field is also exposed in the q parameter, where AND logic can be
    # expressed explicitly instead of the OR-combining of the url parameter.
    # Tuesday is not part of the opening hours, so requiring both weekdays excludes the event.
    When I send a GET request to "/events" with parameters:
      | q                     | recurringOnDayOfWeek:(monday AND tuesday) |
      | disableDefaultFilters | true                                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Both weekdays are part of the opening hours, so the AND query matches.
    When I send a GET request to "/events" with parameters:
      | q                     | recurringOnDayOfWeek:(monday AND wednesday) |
      | disableDefaultFilters | true                                        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Periodic event is only matched on weekdays that reach the minimum number of occurrences
    # The period from 1 to 26 August 2026 contains four Wednesdays (5, 12, 19 and 26), which is
    # exactly the required minimum, and three Thursdays (6, 13 and 20), which is one short.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-08-26T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "thursday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the recurringOnDayOfWeek filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday reaches the threshold exactly, so it is indexed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Thursday stays one occurrence below the threshold, so it is not indexed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | thursday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Closed days are subtracted from the occurrences of a weekday
    # The period from 1 August to 2 September 2026 contains five Wednesdays and five Saturdays.
    # The closed period from 5 to 12 August removes the Wednesdays of 5 and 12 August, leaving three,
    # which is below the required minimum of four. It also removes the Saturday of 8 August, leaving
    # four, which still reaches the minimum.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-09-02T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "saturday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-08-05",
          "endDate": "2026-08-12"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the recurringOnDayOfWeek filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday keeps four occurrences, so the closed days do not affect matching.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | saturday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday drops to three occurrences, so it is no longer indexed.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Multiple calendar event is matched on the weekdays covered by its sub-events
    # Four single day sub-events on a Wednesday, four sub-events running from Friday to Sunday and
    # three single day sub-events on a Thursday.
    Given I create a minimal place and save the "url" as "placeUrl"
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-08-05T10:00:00+02:00",
      "endDate": "2026-09-27T18:00:00+02:00",
      "subEvent": [
        {"startDate": "2026-08-05T10:00:00+02:00", "endDate": "2026-08-05T18:00:00+02:00"},
        {"startDate": "2026-08-12T10:00:00+02:00", "endDate": "2026-08-12T18:00:00+02:00"},
        {"startDate": "2026-08-19T10:00:00+02:00", "endDate": "2026-08-19T18:00:00+02:00"},
        {"startDate": "2026-08-26T10:00:00+02:00", "endDate": "2026-08-26T18:00:00+02:00"},
        {"startDate": "2026-08-06T10:00:00+02:00", "endDate": "2026-08-06T18:00:00+02:00"},
        {"startDate": "2026-08-13T10:00:00+02:00", "endDate": "2026-08-13T18:00:00+02:00"},
        {"startDate": "2026-08-20T10:00:00+02:00", "endDate": "2026-08-20T18:00:00+02:00"},
        {"startDate": "2026-09-04T10:00:00+02:00", "endDate": "2026-09-06T18:00:00+02:00"},
        {"startDate": "2026-09-11T10:00:00+02:00", "endDate": "2026-09-13T18:00:00+02:00"},
        {"startDate": "2026-09-18T10:00:00+02:00", "endDate": "2026-09-20T18:00:00+02:00"},
        {"startDate": "2026-09-25T10:00:00+02:00", "endDate": "2026-09-27T18:00:00+02:00"}
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the recurringOnDayOfWeek filter the event is returned, so it is indexed and searchable.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The four single day sub-events map to the weekday they take place on.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday sits in the middle of each Friday to Sunday range, so multi-day sub-events are
    # expanded to every weekday in their range.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | saturday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The edges of those ranges are part of the set too.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | friday,sunday |
      | disableDefaultFilters | true          |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Thursday is only covered three times, which is below the required minimum of four.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | thursday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # No sub-event ever touches a Monday.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | monday |
      | disableDefaultFilters | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Single calendar event is out of scope and not matched by recurringOnDayOfWeek
    Given I create a minimal place and save the "url" as "placeUrl"
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
    # The single event takes place on a Wednesday and is searchable without the recurringOnDayOfWeek filter.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # But single calendar events are never matched by the recurringOnDayOfWeek filter.
    When I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Permanent place is matched on the weekdays of its opening hours
    When I create a minimal place with overrides and save the "url" as "placeUrl"
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
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Matching is case-insensitive, so the capitalised weekday returns the same result.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | Wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Tuesday is not part of the opening hours.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | tuesday |
      | disableDefaultFilters | true    |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The comma-separated syntax OR-combines the weekdays, so friday alone is enough to match.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | saturday,friday |
      | disableDefaultFilters | true            |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | tuesday,thursday |
      | disableDefaultFilters | true             |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # The recurringOnDayOfWeek field is also exposed in the q parameter, where AND logic can be
    # expressed explicitly instead of the OR-combining of the url parameter.
    # Tuesday is not part of the opening hours, so requiring both weekdays excludes the place.
    When I send a GET request to "/places" with parameters:
      | q                     | recurringOnDayOfWeek:(monday AND tuesday) |
      | disableDefaultFilters | true                                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Both weekdays are part of the opening hours, so the AND query matches.
    When I send a GET request to "/places" with parameters:
      | q                     | recurringOnDayOfWeek:(monday AND wednesday) |
      | disableDefaultFilters | true                                        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Permanent place without opening hours is not matched by recurringOnDayOfWeek
    # A permanent place with no opening hours has no weekdays to derive the filter from.
    Given I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the recurringOnDayOfWeek filter the place is returned, so it is indexed and searchable.
    When I send a GET request to "/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | monday |
      | disableDefaultFilters | true   |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Periodic place is only matched on weekdays that reach the minimum number of occurrences
    # The period from 1 to 26 August 2026 contains four Wednesdays (5, 12, 19 and 26), which is
    # exactly the required minimum, and three Thursdays (6, 13 and 20), which is one short.
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-08-26T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "thursday"]
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the recurringOnDayOfWeek filter the place is returned, so it is indexed and searchable.
    When I send a GET request to "/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday reaches the threshold exactly, so it is indexed.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Thursday stays one occurrence below the threshold, so it is not indexed.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | thursday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Closed days are subtracted from the occurrences of a weekday of a place
    # The period from 1 August to 2 September 2026 contains five Wednesdays and five Saturdays.
    # The closed period from 5 to 12 August removes the Wednesdays of 5 and 12 August, leaving three,
    # which is below the required minimum of four. It also removes the Saturday of 8 August, leaving
    # four, which still reaches the minimum.
    When I create a minimal place with overrides and save the "url" as "placeUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-08-01T00:00:00+02:00",
      "endDate": "2026-09-02T23:59:59+02:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["wednesday", "saturday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2026-08-05",
          "endDate": "2026-08-12"
        }
      ]
    }
    """
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Without the recurringOnDayOfWeek filter the place is returned, so it is indexed and searchable.
    When I send a GET request to "/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Saturday keeps four occurrences, so the closed days do not affect matching.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | saturday |
      | disableDefaultFilters | true     |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Wednesday drops to three occurrences, so it is no longer indexed.
    When I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | wednesday |
      | disableDefaultFilters | true      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: An invalid weekday value is rejected with a validation error on the events endpoint
    When I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | recurringOnDayOfWeek  | someday |
      | disableDefaultFilters | true    |
    Then the response status should be "404"
    And the JSON response at "detail" should include "someday"

  @testIsolation
  Scenario: An invalid weekday value is rejected with a validation error on the places endpoint
    When I am using the Search API v3 base URL
    And I send a GET request to "/places" with parameters:
      | recurringOnDayOfWeek  | someday |
      | disableDefaultFilters | true    |
    Then the response status should be "404"
    And the JSON response at "detail" should include "someday"
