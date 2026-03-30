@sapi3
Feature: Test the Search API v3 url parameters on organizers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for an organizer using the id filter
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | id | %{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | id | ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the name filter
    Given I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | name | %{name}           |
      | q    | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | name | nonexistent       |
      | q    | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the website filter
    Given I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | website | https://www.%{name}.be |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | website | https://www.nonexistent-organizer.be |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the domain filter
    Given I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | domain | %{name}.be        |
      | q      | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | domain | nonexistent-organizer.be |
      | q      | id:%{organizerId}        |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the postalCode filter
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | postalCode | 1080              |
      | q          | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | postalCode | 9000              |
      | q          | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the addressCountry filter
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "belgianOrganizerId"
    And I wait for the organizer with url "/organizers/%{belgianOrganizerId}" to be indexed
    And I create an organizer from "organizers/organizer-in-the-netherlands.json" and save the "id" as "dutchOrganizerId"
    And I wait for the organizer with url "/organizers/%{dutchOrganizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | addressCountry | BE                       |
      | q              | id:%{belgianOrganizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | addressCountry | NL                       |
      | q              | id:%{belgianOrganizerId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | addressCountry | NL                     |
      | q              | id:%{dutchOrganizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | addressCountry | BE                     |
      | q              | id:%{dutchOrganizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using a single region filter
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | regions | nis-21012         |
      | q       | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | regions | nis-21016         |
      | q       | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using multiple region filters
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | regions[] | nis-01000         |
      | regions[] | nis-21016         |
      | q         | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | regions[] | nis-01000         |
      | regions[] | nis-21012         |
      | q         | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for an organizer using the geo distance filter
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | coordinates | 50.8511740,4.3386740 |
      | distance    | 5km                  |
      | q           | id:%{organizerId}    |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | coordinates | 51.054,3.717      |
      | distance    | 5km               |
      | q           | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the geo bounds filter
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | bounds | 50.0,2.0%7C51.5,6.0 |
      | q      | id:%{organizerId}   |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | bounds | 52.0,4.0%7C53.0,6.0 |
      | q      | id:%{organizerId}   |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the creator filter
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | creator | edcee0f7-5906-4e92-8551-a7f5d37ba453 |
      | q       | id:%{organizerId}                    |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | creator | ffffffff-ffff-ffff-ffff-ffffffffffff |
      | q       | id:%{organizerId}                    |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the hasImages filter
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | hasImages | false             |
      | q         | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | hasImages | true              |
      | q         | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for a single label using the labels filter
    Given I create a random labelname of 10 characters
    And I create a minimal organizer and save the "id" as "organizerId"
    And I send a PUT request to "/organizers/%{organizerId}/labels/%{labelname}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | labels | %{labelname}      |
      | q      | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | labels | nonexistentlabel    |
      | q      | id:%{organizerId}   |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for multiple labels using the labels filter
    Given I create a random labelname of 10 characters
    And I create a minimal organizer and save the "id" as "organizerId"
    And I send a PUT request to "/organizers/%{organizerId}/labels/%{labelname}"
    And I send a PUT request to "/organizers/%{organizerId}/labels/foobar"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | labels[] | %{labelname}      |
      | labels[] | foobar            |
      | q        | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | labels[] | %{labelname}        |
      | labels[] | nonexistentlabel    |
      | q        | id:%{organizerId}   |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an organizer using the workflowStatus filter
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | workflowStatus | ACTIVE            |
      | q              | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | workflowStatus | DELETED           |
      | q              | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0
    When I am using the UDB3 base URL
    And I delete the organizer at "/organizers/%{organizerId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | workflowStatus | DELETED           |
      | q              | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/organizers" with parameters:
      | workflowStatus | ACTIVE            |
      | q              | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for organizers with region facets
    Given I create an organizer from "organizers/organizer-with-address.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | facets[] | regions           |
      | q        | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
    And the JSON response at "facet/regions/nis-01000/children/reg-brussel/children/nis-21012/count" should be 1
