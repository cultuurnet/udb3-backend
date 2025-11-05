Feature: Embedded resources in event data

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Update place name embedded on event
    When I create a minimal place and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I update the place at "%{placeUrl}" from "places/place-with-updated-name.json"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "location/name/nl" should be "%{name} UPDATED"

  Scenario: Update organizer name embedded on event
    When I create a minimal organizer and save the "url" as "organizerUrl"
    And I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent-with-organizer.json" and save the "url" as "eventUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-updated.json"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "organizer/name/nl" should be "%{name} UPDATED"

  Scenario: Update organizer name embedded on place embedded on event
    When I create a minimal organizer and save the "url" as "organizerUrl"
    And I create a place from "places/place-with-organizer.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-updated.json"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "location/organizer/name/nl" should be "%{name} UPDATED"
