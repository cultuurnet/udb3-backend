@sapi3
Feature: Test the Search API v3 advanced queries on organizers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for a name using an advanced query
    Given I create a random name of 10 characters
    And I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.\*:%{name} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.\*:nonexistingorganizer |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.nl:%{name} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.nl:nonexistingorganizer |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for a description using an advanced query
    Given I create a random name of 10 characters
    And I create a minimal organizer and save the "id" as "organizerId"
    And I create a random string of 20 characters and keep it as "description"
    And I set the JSON request payload to:
    """
    { "description": "%{description}" }
    """
    And I send a PUT request to "/organizers/%{organizerId}/description/nl"
    And I wait 2 seconds
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND description.\*:%{description} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND description.\*:nonexistingdescription |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND description.nl:%{description} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND description.nl:nonexistingorganizer |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for text using an advanced query
    Given I create a random name of 10 characters
    And I create a minimal organizer and save the "id" as "organizerId"
    And I create a random string of 20 characters and keep it as "freeText"
    And I set the JSON request payload to:
    """
    { "description": "%{freeText}" }
    """
    And I send a PUT request to "/organizers/%{organizerId}/description/nl"
    And I wait 2 seconds
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND %{freeText} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND nonexistingfreetext |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for a name using an advanced query
    Given I create a random name of 10 characters
    And I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.\*:%{name} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.\*:nonexistingorganizer |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.nl:%{name} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND name.nl:nonexistingorganizer |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for a single label using an advanced query
    Given I create a random labelname of 10 characters
    And I create a minimal organizer and save the "id" as "organizerId"
    And I send a PUT request to "/organizers/%{organizerId}/labels/%{labelname}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND labels:%{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND labels:nonexistentlabel |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for multiple labels using an advanced query
    Given I create a random labelname of 10 characters
    And I create a minimal organizer and save the "id" as "organizerId"
    And I send a PUT request to "/organizers/%{organizerId}/labels/%{labelname}"
    And I send a PUT request to "/organizers/%{organizerId}/labels/foobar"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND labels:(%{labelname} AND foobar) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND labels:(%{labelname} AND nonexistentlabel) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND labels:(%{labelname} OR nonexistentlabel) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND NOT labels:%{labelname} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for country using an advanced query
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "belgianOrganizerId"
    And I wait for the organizer with url "/organizers/%{belgianOrganizerId}" to be indexed
    And I create an organizer from "organizers/organizer-in-the-netherlands.json" and save the "id" as "dutchOrganizerId"
    And I wait for the organizer with url "/organizers/%{dutchOrganizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{belgianOrganizerId} AND address.nl.addressCountry:BE |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{belgianOrganizerId} AND address.nl.addressCountry:NL |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{dutchOrganizerId} AND address.nl.addressCountry:NL |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{dutchOrganizerId} AND address.nl.addressCountry:BE |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for postal code using an advanced query
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND address.nl.postalCode:1080 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND address.fr.postalCode:1080 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND address.\*.postalCode:1080 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND address.nl.postalCode:9000 |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND address.fr.postalCode:9000 |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND address.\*.postalCode:9000 |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for a single region using an advanced query
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND regions:nis-21012 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND regions:nis-21016 |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for multiple regions using an advanced query
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND regions:(nis-01000 AND nis-21016) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND regions:(nis-01000 AND nis-21012) |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for creator using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND creator:edcee0f7-5906-4e92-8551-a7f5d37ba453 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND creator:ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for contributors using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I create a random email and keep it as "contributorEmail"
    And I set the JSON request payload to:
    """
    [
      "%{contributorEmail}"
    ]
    """
    And I send a PUT request to "/organizers/%{organizerId}/contributors"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND contributors:%{contributorEmail} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND contributors:nonexistent@example.com |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for workflow status using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND workflowStatus:ACTIVE |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND workflowStatus:DELETED |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for images count using an advanced query
    Given I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    And I upload "file" from path "images/udb.jpg" to "/images/"
    And I keep the value of the JSON response at "imageId" as "imageId1"
    And I keep the value of the JSON response at "@id" as "imageUrl1"
    And I set the form data properties to:
      | description     | logo2 |
      | copyrightHolder | me2   |
      | language        | nl   |
    And I upload "file" from path "images/udb.jpg" to "/images/"
    And I keep the value of the JSON response at "imageId" as "imageId2"
    And I keep the value of the JSON response at "@id" as "imageUrl2"
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I create an organizer from "organizers/organizer-with-images.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND imagesCount:0 |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND imagesCount:[2 TO *] |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for created timestamp using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND created:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND created:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND created:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND created:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for modified timestamp using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND modified:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND modified:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND modified:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND modified:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for languages using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND mainLanguage:nl |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND mainLanguage:fr |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND languages:nl |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND languages:fr |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND completedLanguages:nl |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND completedLanguages:fr |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for completeness using an advanced query
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND completeness:[1 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} AND completeness:[90 TO *] |
    Then the JSON response at "totalItems" should be 0