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
    Then the RDF response should match organizer projection "organizers/rdf/organizer.ttl"

  Scenario: Create an organizer with address
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match organizer projection "organizers/rdf/organizer-with-address.ttl"

  Scenario: Create an organizer with contact point
    Given I create an organizer from "organizers/organizer-with-contact-point.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match organizer projection "organizers/rdf/organizer-with-contact-point.ttl"

  Scenario: Create an organizer with labels
    Given I create an organizer from "organizers/organizer-with-labels.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match organizer projection "organizers/rdf/organizer-with-labels.ttl"

  Scenario: Create an organizer with images
    Given I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId1"
    And I keep the value of the JSON response at "@id" as "imageUrl1"

    Given I set the form data properties to:
      | description     | logo2 |
      | copyrightHolder | me2   |
      | language        | nl   |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId2"
    And I keep the value of the JSON response at "@id" as "imageUrl2"

    Given I create an organizer from "organizers/organizer-with-images.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    And I calculate the image hash with description "logo", copyright "me" and language "nl" for "imageId1"
    And I calculate the image hash with description "logo2", copyright "me2" and language "nl" for "imageId2"
    Then the RDF response should match organizer projection "organizers/rdf/organizer-with-images.ttl"

  Scenario: Create an organizer with description
    Given I create an organizer from "organizers/organizer-with-long-description.json" and save the "id" as "organizerId"
    And I accept "text/turtle"
    When I get the RDF of organizer with id "%{organizerId}"
    Then the RDF response should match organizer projection "organizers/rdf/organizer-with-description.ttl"