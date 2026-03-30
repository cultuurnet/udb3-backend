@sapi3
Feature: Test the Search API v3 default filters on organizers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: By default deleted organizers are not shown
    Given I create an organizer from "organizers/organizer-minimal.json" and save the "id" as "organizerId"
    And I delete the organizer at "/organizers/%{organizerId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/organizers" with parameters:
      | workflowStatus | *                 |
      | q              | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Unlike offers, by default organizers outside Belgium are shown
    Given I create an organizer from "organizers/organizer-in-the-netherlands.json" and save the "id" as "organizerId"
    And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/organizers" with parameters:
      | q | id:%{organizerId} |
    Then the JSON response at "totalItems" should be 1
