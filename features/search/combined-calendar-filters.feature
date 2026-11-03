@sapi3
Feature: Test combining calendar filters on offers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed

  @testIsolation
  Scenario: Every calendar filter applies when no date or time is given
    When I create a minimal event with overrides and save the "url" as "childcareEventUrl"
    """
    {
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2126-08-01T09:00:00+02:00",
          "endDate": "2126-08-05T17:00:00+02:00",
          "childcare": {"start": "08:00", "end": "18:00"}
        }
      ]
    }
    """
    And I wait for the event with url "%{childcareEventUrl}" to be indexed
    And I create a minimal event with overrides and save the "url" as "childcareAndOvernightEventUrl"
    """
    {
      "terms": [{"id": "0.57.0.0.0", "label": "Kamp of vakantie", "domain": "eventtype"}],
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2126-08-01T09:00:00+02:00",
          "endDate": "2126-08-05T17:00:00+02:00",
          "childcare": {"start": "08:00", "end": "18:00"},
          "hasOvernightStay": true
        }
      ]
    }
    """
    And I wait for the event with url "%{childcareAndOvernightEventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 2
    When I send a GET request to "/events" with parameters:
      | hasChildcare          | true |
      | hasOvernightStay      | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    And the JSON response at "member/0/@id" should be "%{childcareAndOvernightEventUrl}"

  @testIsolation
  Scenario: A date and a time filter have to match the same sub-event
    When I create a minimal event with overrides and save the "url" as "eventUrl"
    """
    {
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2126-08-03T10:00:00+02:00",
          "endDate": "2126-08-03T12:00:00+02:00"
        },
        {
          "startDate": "2126-08-04T14:00:00+02:00",
          "endDate": "2126-08-04T16:00:00+02:00"
        }
      ]
    }
    """
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2126-08-03T00:00:00+02:00 |
      | dateTo                | 2126-08-03T23:59:59+02:00 |
      | localTimeFrom         | 1000                      |
      | localTimeTo           | 1200                      |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    # The time only matches the sub-event of the next day.
    When I send a GET request to "/events" with parameters:
      | dateFrom              | 2126-08-03T00:00:00+02:00 |
      | dateTo                | 2126-08-03T23:59:59+02:00 |
      | localTimeFrom         | 1400                      |
      | localTimeTo           | 1600                      |
      | disableDefaultFilters | true                      |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0
