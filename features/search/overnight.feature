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
          "overnight": true
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A multiple event with overnight on only one sub-event matches hasOvernight=true
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
      | q                     | %{eventUrl} |
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: A multiple event where every sub-event is overnight matches hasOvernight=true
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
          "endDate": "2126-08-14T17:00:00+02:00",
          "overnight": true
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
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
      | q                     | %{eventUrl} |
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
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
      | q                     | %{eventUrl} |
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl} |
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: Places are never returned by hasOvernight=true
    Given I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | q                     | %{placeUrl} |
      | hasOvernight          | true        |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q                     | %{placeUrl} |
      | hasOvernight          | false       |
      | disableDefaultFilters | true        |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  @testIsolation
  Scenario: Overnight does not widen the date range of an event
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
          "overnight": true
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    # A date window covering the sub-event's own start/end still returns the event
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # A date window entirely after the sub-event's endDate returns nothing:
    # overnight has not extended the matched range beyond the actual endDate
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | dateFrom              | 2126-08-06T00:00:00+02:00 |
      | dateTo                | 2126-08-07T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  @testIsolation
  Scenario: hasOvernight=true combines with a matching date window
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
          "overnight": true
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | hasOvernight          | true                      |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q                     | %{eventUrl}               |
      | hasOvernight          | false                     |
      | dateFrom              | 2126-08-01T00:00:00+02:00 |
      | dateTo                | 2126-08-06T00:00:00+02:00 |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
