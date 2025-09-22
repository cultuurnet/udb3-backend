Feature: Test the UDB3 implicit ownership for the creator of an organizer

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "invoerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"
    And I create a minimal place and save the "url" as "placeUrl"

  Scenario: Get implicit ownership for the creator of an event with an organizer
    When I am authorized as JWT provider user "invoerder_1"
    And I create an event from "events/event-minimal-permanent-with-organizer.json" and save the "url" as "eventUrl"
    And I am authorized as JWT provider user "invoerder"
    And I update the event at "%{eventUrl}" from "events/event-minimal-permanent-with-organizer-updated.json"
    Then the response status should be "200"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "name/nl" should be "Permanent event UPDATED"
