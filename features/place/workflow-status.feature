Feature: Test place workflowStatus property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Publish place
    When I publish the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Publish place with specific availableFrom
    When I publish the place at "%{placeUrl}" with availableFrom "2222-10-23T12:32:15+01:00"
    And I get the event at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    And the JSON response at "availableFrom" should be "2222-10-23T12:32:15+01:00"

  Scenario: Publish place via legacy PATCH
    When I publish the place via legacy PATCH at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Approve place
    When I publish the place at "%{placeUrl}"
    And I approve the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Approve place via legacy PATCH
    When I publish the place via legacy PATCH at "%{placeUrl}"
    And I approve the place via legacy PATCH at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Reject place
    When I publish the place at "%{placeUrl}"
    And I reject the place at "%{placeUrl}" with reason "Nope"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

  Scenario: Reject place via legacy PATCH
    When I publish the place via legacy PATCH at "%{placeUrl}"
    And I reject the place via legacy PATCH at "%{placeUrl}" with reason "Nope"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

  Scenario: Update a place workflowStatus from DRAFT to READY_FOR_VALIDATION via complete overwrite
    When I update the place at "%{placeUrl}" from "places/place-with-workflow-status-ready-for-validation.json"
    And I get the place at "%{placeUrl}"
    And the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Update a place workflowStatus from DRAFT to DELETED via complete overwrite
    When I update the place at "%{placeUrl}" from "places/place-with-workflow-status-deleted.json"
    And I get the place at "%{placeUrl}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Update a place workflowStatus from READY_FOR_VAlIDATION to DELETED via complete overwrite
    Given I create a place from "places/place-with-workflow-status-ready-for-validation.json" and save the "url" as "placeUrl"
    And I update the place at "%{placeUrl}" from "places/place-with-workflow-status-deleted.json"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Ignore a place workflowStatus update from APPROVED to READY FOR VALIDATION via complete overwrite
    Given I create a place from "places/place-with-workflow-status-ready-for-validation.json" and save the "url" as "placeUrl"
    When I approve the place via legacy PATCH at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

    Given I update the place at "%{placeUrl}" from "places/place-with-workflow-status-ready-for-validation.json"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Ignore a place workflowStatus update from REJECTED to READY FOR VALIDATION via complete overwrite
    Given I create a place from "places/place-with-workflow-status-ready-for-validation.json" and save the "url" as "placeUrl"
    When I reject the place via legacy PATCH at "%{placeUrl}" with reason "Rejected reason"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

    Given I update the place at "%{placeUrl}" from "places/place-with-workflow-status-ready-for-validation.json"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

  Scenario: Ignore a place workflowStatus update from DELETED to READY FOR VALIDATION via complete overwrite
    Given I create a place from "places/place-with-workflow-status-ready-for-validation.json" and save the "url" as "placeUrl"
    When I approve the place via legacy PATCH at "%{placeUrl}"
    When I delete the place at "%{placeUrl}"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

    Given I update the place at "%{placeUrl}" from "places/place-with-workflow-status-ready-for-validation.json"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Trying to publish a place as a moderator without sufficient permissions
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I am authorized as JWT provider user "validator_diest"
    When I set the JSON request payload to:
    """
    {"workflowStatus": "APPROVED"}
    """
    And I send a PUT request to "%{placeUrl}/workflow-status"
    Then the response status should be "403"
    And the JSON response at "detail" should include 'has no permission "Aanbod modereren" on resource'

  Scenario: Trying to publish a place as a moderator without sufficient permissions via legacy PATCH
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I am authorized as JWT provider user "validator_diest"
    When I send "application/ld+json;domain-model=Publish"
    And I send a PATCH request to "%{placeUrl}"
    Then the response status should be "403"
    And the JSON response at "detail" should include 'has no permission "Aanbod bewerken" on resource'
