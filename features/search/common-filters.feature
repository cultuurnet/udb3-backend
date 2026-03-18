@sapi3
Feature: Test the Search API v3 default filters

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for labels using the common filter
    When I create a random labelname of 10 characters
    And I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{uuid_place}/labels/%{labelname}"
    And I send a PUT request to "/events/%{eventId}/labels/%{labelname}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | labels | %{labelname} |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | labels | %{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | labels | %{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | locationLabels | %{labelname} |
    Then the JSON response at "totalItems" should be 1