@sapi3
Feature: Test the Search API v3 default filters

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: By default non-belgium places are not shown
    Given I create a place from "places/place-in-the-netherlands.json" and save the "id" as "placeId"
    And I wait for the place with url "/places/%{placeId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | q | id:%{placeId} |
    Then the JSON response at "totalItems" should be 0
    And I send a GET request to "/places" with parameters:
      | addressCountry | *             |
      | q              | id:%{placeId} |
    Then the JSON response at "totalItems" should be 1

  Scenario: By default non-belgium events are not shown
    Given I create a place from "places/place-in-the-netherlands.json" and save the "id" as "uuid_place"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    And I send a GET request to "/events" with parameters:
      | addressCountry | *             |
      | q              | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
