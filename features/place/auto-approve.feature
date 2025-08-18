@api @places
Feature: Test auto approving places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "importerWithAutoApproval"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

  @auto-approve @create @workflow-status
  Scenario: Create an auto-approved place when workflow status is ready for validation
    Given I create a place from "places/place-with-workflow-status-ready-for-validation.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  @auto-approve @create @workflow-status
  Scenario: Don't create an auto-approved place when workflow status is draft
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DRAFT"

  @auto-approve @create @legacy @workflow-status
  Scenario: Create an auto-approved place with only the required fields via the legacy imports path
    Given I import a new place from "places/place-with-required-fields.json" and save the "url" as "placeUrl"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"
