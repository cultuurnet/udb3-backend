@sapi3
Feature: Test the Search API v3 never returns the capacity of subEvents

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for an event with capacities
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/sub-event-capacity/event-multiple-with-capacities.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    And the JSON response at "member/0/subEvent/0/startDate" should be "2027-07-01T10:00:00+02:00"
    But the JSON response should not have "member/0/subEvent/0/capacity"
    And the JSON response should not have "member/0/subEvent/1/capacity"
