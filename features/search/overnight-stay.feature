@sapi3
Feature: Test the hasOvernightStay search filter on offers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed

  @testIsolation
  Scenario: A single event with an overnight stay matches hasOvernightStay=true
    When I create an event from "events/overnight-stay/event-single-with-overnight-stay.json" and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A multiple event with an overnight stay on only one sub-event matches hasOvernightStay=true
    # Only one sub-event has an overnight stay (the other does not), which is enough to prove
    # the filter matches on ANY sub-event with an overnight stay, not just when all of them have one.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2126-08-01T09:00:00+02:00",
          "endDate": "2126-08-05T17:00:00+02:00",
          "hasOvernightStay": true
        },
        {
          "startDate": "2126-08-10T09:00:00+02:00",
          "endDate": "2126-08-14T17:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: An event without any overnight stay matches hasOvernightStay=false
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "calendarType": "single",
      "startDate": "2126-08-01T09:00:00+02:00",
      "endDate": "2126-08-05T17:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2126-08-01T09:00:00+02:00",
          "endDate": "2126-08-05T17:00:00+02:00",
          "hasOvernightStay": false
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A periodic event with opening hours matches hasOvernightStay=false
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "periodic",
      "startDate": "2126-08-01T00:00:00+02:00",
      "endDate": "2126-12-31T23:59:59+02:00",
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
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Places are never returned by hasOvernightStay=true
    Given I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | hasOvernightStay          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | hasOvernightStay          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: hasOvernightStay=true combines with a matching date window
    When I create an event from "events/overnight-stay/event-single-with-overnight-stay.json" and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true                      |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | false                     |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: hasOvernightStay=true does not match when the overnight stay falls outside the date window
    # Day 1 and day 2 have no overnight stay; only day 3 (outside the queried window) has one.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2126-09-01T09:00:00+02:00",
          "endDate": "2126-09-01T17:00:00+02:00"
        },
        {
          "startDate": "2126-09-02T09:00:00+02:00",
          "endDate": "2126-09-02T17:00:00+02:00"
        },
        {
          "startDate": "2126-09-03T09:00:00+02:00",
          "endDate": "2126-09-05T17:00:00+02:00",
          "hasOvernightStay": true
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Top-level check: the offer has a sub-event with an overnight stay (day 3), so it matches without a date filter.
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Date window covering only day 1-2 (no overnight stay in range): must not match,
    # even though the offer has an overnight stay on a sub-event outside the window.
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true                      |
      | dateFrom              | 2126-09-01T00:00:00+02:00 |
      | dateTo                | 2126-09-02T23:59:59+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Date window covering day 3 (the sub-event with the overnight stay): matches.
    When I send a GET request to "/events" with parameters:
      | hasOvernightStay          | true                      |
      | dateFrom              | 2126-09-03T00:00:00+02:00 |
      | dateTo                | 2126-09-05T23:59:59+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
