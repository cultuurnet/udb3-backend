Feature: Test RDF projection of places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create a place with only the required fields
    Given I create a minimal place and save the "id" as "placeId"
    And I accept "text/turtle"
    When I get the RDF of place with id "%{placeId}"
    And I save the identifier hash with the namespace "http://data.uitdatabank.local:80/places/" and id "placeId"
    Then the RDF response should match "places/rdf/place-with-required-fields.ttl"

  Scenario: Create a place with labels
    And I create a place from "places/rdf/place-with-labels.json" and save the "id" as "placeId"
    And I accept "text/turtle"
    When I get the RDF of place with id "%{placeId}"
    And I save the identifier hash with the namespace "http://data.uitdatabank.local:80/places/" and id "placeId"
    Then the RDF response should match "places/rdf/place-with-labels.ttl"

  Scenario: Create a place with invalid address
    And I create a place from "places/rdf/place-with-invalid-address.json" and save the "id" as "placeId"
    And I accept "text/turtle"
    When I get the RDF of place with id "%{placeId}"
    And I save the identifier hash with the namespace "http://data.uitdatabank.local:80/places/" and id "placeId"
    Then the RDF response should match "places/rdf/place-with-invalid-address.ttl"
