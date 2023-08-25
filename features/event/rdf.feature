Feature: Test RDF projection of events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "id" as "uuid_place"

  Scenario: Create an event with only the required fields
    Given I create an event from "events/event-with-multiple-calendar.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/event-with-all-fields.ttl"

  Scenario: Create an event with permanent calendar and opening hours
    And I create an event from "events/event-with-permanent-calendar-and-opening-hours.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/event-with-permanent-calendar-and-opening-hours.ttl"

  Scenario: Create an event with periodic calendar and opening hours
    And I create an event from "events/event-with-periodic-calendar-and-opening-hours.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/event-with-periodic-calendar-and-opening-hours.ttl"