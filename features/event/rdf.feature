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

  Scenario: Create an online event with permanent calendar and online url
    And I create an event from "events/rdf/online-event-with-online-url-and-permanent-calendar.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/online-event-with-online-url-and-permanent-calendar.ttl"

  Scenario: Create an online event with multiple calendar
    And I create an event from "events/rdf/online-event-with-multiple-calendar.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/online-event-with-multiple-calendar.ttl"

  Scenario: Create an online event with multiple calendar and online url
    And I create an event from "events/rdf/online-event-with-online-url-and-multiple-calendar.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/online-event-with-online-url-and-multiple-calendar.ttl"

  Scenario: Create a mixed event with permanent calendar and online url
    And I create an event from "events/rdf/mixed-event-with-online-url-and-permanent-calendar.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/mixed-event-with-online-url-and-permanent-calendar.ttl"

  Scenario: Create a mixed event with multiple calendar and online url
    And I create an event from "events/rdf/mixed-event-with-online-url-and-multiple-calendar.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/mixed-event-with-online-url-and-multiple-calendar.ttl"

  Scenario: Create an event with organizer
    Given I create a random name of 10 characters
    And I set the JSON request payload from "organizers/organizer-minimal.json"
    And I send a POST request to "/organizers/"
    And I keep the value of the JSON response at "id" as "organizerId"
    And I create an event from "events/rdf/event-with-organizer.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/event-with-organizer.ttl"

  Scenario: Create an event with contact point
    And I create an event from "events/rdf/event-with-contact-point.json" and save the "id" as "eventId"
    And I am using the RDF base URL
    And I accept "text/turtle"
    When I get the RDF of event with id "%{eventId}"
    Then the RDF response should match "events/rdf/event-with-contact-point.ttl"