Feature: Embedded resources in place data

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Update organizer name embedded on place
    When I create a minimal organizer and save the "url" as "organizerUrl"
    And I create a place from "places/place-with-organizer.json" and save the "url" as "placeUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-updated.json"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "organizer/name/nl" should be "%{name} UPDATED"
