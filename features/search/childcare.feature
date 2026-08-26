@sapi3
Feature: Test the hasChildcare offer search filter

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  @testIsolation
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
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false |
      | disableDefaultFilters | true  |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
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
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false |
      | disableDefaultFilters | true  |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
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
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false |
      | disableDefaultFilters | true  |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
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
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false |
      | disableDefaultFilters | true  |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
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
      | hasChildcare          | false |
      | disableDefaultFilters | true  |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Omitting hasChildcare applies no childcare filtering, so the event is still returned.
    When I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Childcare hours extend the period a date filter matches
    # The activity runs 10:00-18:00 with childcare for the wider 08:00-19:00 window, so the event
    # is searchable for the whole of 08:00-19:00.
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
      | dateFrom              | 2026-08-17T10:00:00+02:00 |
      | dateTo                | 2026-08-17T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # A date filter covering only the childcare hours before the activity returns the event too.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-08-17T08:00:00+02:00 |
      | dateTo                | 2026-08-17T09:59:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # And the same for the childcare hours after the activity.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-08-17T18:01:00+02:00 |
      | dateTo                | 2026-08-17T19:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The period is extended to the childcare hours, not beyond them.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-08-17T06:00:00+02:00 |
      | dateTo                | 2026-08-17T07:59:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare hours extend the period a local time filter matches
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-10-05T10:00:00+02:00",
      "endDate": "2026-10-05T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-10-05T10:00:00+02:00",
          "endDate": "2026-10-05T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # A local time window inside the childcare hours before the activity returns the event.
    When I send a GET request to "/events" with parameters:
      | localTimeFrom         | 0800 |
      | localTimeTo           | 0959 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # And the same after the activity.
    When I send a GET request to "/events" with parameters:
      | localTimeFrom         | 1801 |
      | localTimeTo           | 1900 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Outside the childcare hours the event is not returned.
    When I send a GET request to "/events" with parameters:
      | localTimeFrom         | 0600 |
      | localTimeTo           | 0759 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare hours extend the sub-event a nested query matches
    # hasChildcare combined with a local time filter queries the nested sub-event, so this covers
    # the extended period on subEvent.localTimeRange rather than on the top-level field.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-10-12T10:00:00+02:00",
      "endDate": "2026-10-12T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-10-12T10:00:00+02:00",
          "endDate": "2026-10-12T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true |
      | localTimeFrom         | 0800 |
      | localTimeTo           | 0959 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false |
      | localTimeFrom         | 0800  |
      | localTimeTo           | 0959  |
      | disableDefaultFilters | true  |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare with only a start extends only the beginning of the period
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-10-19T10:00:00+02:00",
      "endDate": "2026-10-19T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-10-19T10:00:00+02:00",
          "endDate": "2026-10-19T18:00:00+02:00",
          "childcare": {"start": "08:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-10-19T08:00:00+02:00 |
      | dateTo                | 2026-10-19T09:59:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Without a childcare end the activity still ends at 18:00.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-10-19T18:01:00+02:00 |
      | dateTo                | 2026-10-19T19:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare with only an end extends only the end of the period
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "single",
      "startDate": "2026-10-26T10:00:00+01:00",
      "endDate": "2026-10-26T18:00:00+01:00",
      "subEvent": [
        {
          "startDate": "2026-10-26T10:00:00+01:00",
          "endDate": "2026-10-26T18:00:00+01:00",
          "childcare": {"end": "19:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-10-26T18:01:00+01:00 |
      | dateTo                | 2026-10-26T19:00:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Without a childcare start the activity still begins at 10:00.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-10-26T08:00:00+01:00 |
      | dateTo                | 2026-10-26T09:59:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare extends only the sub-event that has it
    # Sub-event 1 (2026-11-02) has childcare; sub-event 2 (2026-11-09) does not.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-11-02T10:00:00+01:00",
      "endDate": "2026-11-09T18:00:00+01:00",
      "subEvent": [
        {
          "startDate": "2026-11-02T10:00:00+01:00",
          "endDate": "2026-11-02T18:00:00+01:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        },
        {
          "startDate": "2026-11-09T10:00:00+01:00",
          "endDate": "2026-11-09T18:00:00+01:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-11-02T08:00:00+01:00 |
      | dateTo                | 2026-11-02T09:59:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-11-09T08:00:00+01:00 |
      | dateTo                | 2026-11-09T09:59:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare on opening hours extends the period a date filter matches
    # The opening hours run 09:00-17:00 with childcare for the wider 08:00-18:00 window.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-11-16T00:00:00+01:00",
      "endDate": "2026-11-20T23:59:59+01:00",
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
    # A date filter covering only the childcare hours before the opening hours returns the event.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-11-16T08:00:00+01:00 |
      | dateTo                | 2026-11-16T08:59:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # And the same for the childcare hours after them.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-11-16T17:01:00+01:00 |
      | dateTo                | 2026-11-16T18:00:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The period is extended to the childcare hours, not beyond them.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2026-11-16T06:00:00+01:00 |
      | dateTo                | 2026-11-16T07:59:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Childcare on permanent opening hours extends the period a local time filter matches
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {"start": "08:00", "end": "18:00"},
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | localTimeFrom         | 0800 |
      | localTimeTo           | 0859 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | localTimeFrom         | 0600 |
      | localTimeTo           | 0759 |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: hasChildcare combines with a date filter on childcare from opening hours
    # Combining hasChildcare with a date filter queries the nested sub-event, so this needs the
    # childcare range of the opening hour to reach every sub-event it expands into.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2026-11-16T00:00:00+01:00",
      "endDate": "2026-11-20T23:59:59+01:00",
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
      | hasChildcare          | true                      |
      | dateFrom              | 2026-11-16T09:00:00+01:00 |
      | dateTo                | 2026-11-16T17:00:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false                     |
      | dateFrom              | 2026-11-16T09:00:00+01:00 |
      | dateTo                | 2026-11-16T17:00:00+01:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
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
      | hasChildcare          | true                      |
      | dateFrom              | 2026-08-24T10:00:00+02:00 |
      | dateTo                | 2026-08-24T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | false                     |
      | dateFrom              | 2026-08-24T10:00:00+02:00 |
      | dateTo                | 2026-08-24T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: hasChildcare=true with a date range only matches sub-events that both fall in the window and have childcare
    # Sub-event 1 (2026-09-07) has childcare; sub-event 2 (2026-09-14) does not.
    # This guards against a top-level boolean check that would return the event for any date
    # window just because the offer has childcare somewhere, even on a different sub-event.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "startDate": "2026-09-07T10:00:00+02:00",
      "endDate": "2026-09-14T18:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2026-09-07T10:00:00+02:00",
          "endDate": "2026-09-07T18:00:00+02:00",
          "childcare": {"start": "08:00", "end": "19:00"}
        },
        {
          "startDate": "2026-09-14T10:00:00+02:00",
          "endDate": "2026-09-14T18:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Top-level check: the offer has childcare (sub-event 1), so it matches.
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Date window covering sub-event 1 (which has childcare): matches.
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true                      |
      | dateFrom              | 2026-09-07T10:00:00+02:00 |
      | dateTo                | 2026-09-07T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Date window covering sub-event 2 (which has NO childcare): must not match,
    # even though the offer has childcare on a different sub-event.
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true                      |
      | dateFrom              | 2026-09-14T10:00:00+02:00 |
      | dateTo                | 2026-09-14T18:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Places are never matched by hasChildcare=true
    Given I wait for the place with url "%{placeUrl}" to be indexed
    When I am using the Search API v3 base URL
    # Without the filter the place is found, proving it is indexed and searchable.
    When I send a GET request to "/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
