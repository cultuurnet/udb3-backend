Feature: Test the UDB3 events API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Delete event
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I keep the value of the JSON response at "eventId" as "eventId"
    When I send a DELETE request to "/events/%{eventId}"
    Then the response status should be "204"
    And I send a GET request to "/events/%{eventId}"
    And the response status should be "200"
    And the JSON response at "workflowStatus" should be "DELETED"
