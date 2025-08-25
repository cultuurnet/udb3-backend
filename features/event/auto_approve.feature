Feature: Auto approving events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "importerWithAutoApproval"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create an auto-approved event when workflow status is ready for validation
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/event-with-workflow-status-ready-for-validation.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Don't create an auto-approved event when workflow status is draft
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "DRAFT"

  Scenario: Create an auto-approved place via the legacy imports path
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    Given I set the JSON request payload from "events/legacy/event-with-permanent-calendar.json"
    When I send a POST request to "/imports/events/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "id" as "eventId"
    And I keep the value of the JSON response at "commandId" as "commandId"
    And I wait for the command with id "%{commandId}" to complete

    When I send a GET request to "events/%{eventId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "APPROVED"
