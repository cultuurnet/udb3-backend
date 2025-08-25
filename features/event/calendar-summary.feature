@api @events
Feature: Test calendar summary on events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-with-eventtype-lessenreeks.json" and save the "url" as "eventUrl"

  Scenario: Get the calendar summary of an event
    Given I am not authorized
    When I send a GET request to "%{eventUrl}/calendar-summary"
    Then the response status should be "200"
    And the content type should be "text/plain"
    And the body should be "Van maandag 17 mei 2021 om 10:00 tot en met woensdag 19 mei 2021 om 00:00"

  Scenario: Get the small text calendar summary of an event
    Given I am not authorized
    When I send a GET request to "%{eventUrl}/calendar-summary?format=sm&style=text"
    Then the response status should be "200"
    And the content type should be "text/plain"
    And the body should be "Ma 17 mei - wo 19 mei"

  Scenario: Get the calendar summary of an event with legacy endpoint
    Given I am not authorized
    When I send a GET request to "%{eventUrl}/calsum"
    Then the response status should be "200"
    And the content type should be "text/plain"
    And the body should be "Van maandag 17 mei 2021 om 10:00 tot en met woensdag 19 mei 2021 om 00:00"
