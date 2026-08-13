@sapi3
Feature: Test the hasOvernight search filter on offers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed

  @testIsolation
  Scenario: A single event with an overnight sub-event matches hasOvernight=true
    When I create an event from "events/overnight/event-single-with-overnight.json" and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A multiple event with overnight on only one sub-event matches hasOvernight=true
    # Only one sub-event is overnight (the other is not), which is enough to prove
    # the filter matches on ANY overnight sub-event, not just when all of them are.
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2126-08-01T09:00:00+02:00",
          "endDate": "2126-08-05T17:00:00+02:00",
          "overnight": true
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
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: An event without any overnight sub-event matches hasOvernight=false
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
          "overnight": false
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A periodic event with opening hours matches hasOvernight=false
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
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Places are never returned by hasOvernight=true
    Given I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: hasOvernight=true combines with a matching date window
    When I create an event from "events/overnight/event-single-with-overnight.json" and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true                      |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | false                     |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: hasOvernight=true does not match when the overnight sub-event falls outside the date window
    # Day 1 and day 2 have no overnight; only day 3 (outside the queried window) is overnight.
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
          "overnight": true
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # Top-level check: the offer has an overnight sub-event (day 3), so it matches without a date filter.
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # Date window covering only day 1-2 (no overnight sub-event in range): must not match,
    # even though the offer has overnight on a sub-event outside the window.
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true                      |
      | dateFrom              | 2126-09-01T00:00:00+02:00 |
      | dateTo                | 2126-09-02T23:59:59+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    # Date window covering day 3 (the overnight sub-event): matches.
    When I send a GET request to "/events" with parameters:
      | hasOvernight          | true                      |
      | dateFrom              | 2126-09-03T00:00:00+02:00 |
      | dateTo                | 2126-09-05T23:59:59+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
