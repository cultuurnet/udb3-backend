Feature: Test RDF projection of organizers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create an organizer with only the required fields
    Given I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match "organizers/rdf/organizer.ttl"

  Scenario: Create an organizer with address
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match "organizers/rdf/organizer-with-address.ttl"

  Scenario: Create an organizer with contact point
    Given I create an organizer from "organizers/organizer-with-contact-point.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match "organizers/rdf/organizer-with-contact-point.ttl"

  Scenario: Create an organizer with labels
    Given I create an organizer from "organizers/organizer-with-labels.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match "organizers/rdf/organizer-with-labels.ttl"

  Scenario: Create an organizer with description
    Given I create an organizer from "organizers/organizer-with-long-description.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match "organizers/rdf/organizer-with-description.ttl"
