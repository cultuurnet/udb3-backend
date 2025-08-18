@api @events
Feature: Test event workflowStatus property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Publish an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Publish an event with specific availableFrom
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event at "%{eventUrl}" with availableFrom "2222-10-23T12:32:15+01:00"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    And the JSON response at "availableFrom" should be "2222-10-23T12:32:15+01:00"

  Scenario: Publish an event via legacy PATCH
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event via legacy PATCH at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

  Scenario: Approve an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event at "%{eventUrl}"
    And I approve the event at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Approve an event via legacy PATCH
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event via legacy PATCH at "%{eventUrl}"
    And I approve the event via legacy PATCH at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Reject an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event at "%{eventUrl}"
    And I reject the event at "%{eventUrl}" with reason "Reject event"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

  Scenario: Reject an event via legacy PATCH
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event via legacy PATCH at "%{eventUrl}"
    And I reject the event via legacy PATCH at "%{eventUrl}" with reason "Reject event"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

  Scenario: Delete an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I publish the event via legacy PATCH at "%{eventUrl}"
    And I approve the event via legacy PATCH at "%{eventUrl}"
    And I delete the event at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Update event to deleted status with complete overwrite
    Given I create a place from "places/place.json" and save the "placeId" as "uuid_place"
    When I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"

    Given I update the event at "%{eventUrl}" from "events/event-with-workflow-status-deleted.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Ignore an event workflowStatus update from APPROVED to READY FOR VALIDATION via complete overwrite
    Given I create a place from "places/place.json" and save the "placeId" as "uuid_place"
    Given I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "url" as "eventUrl"
    When I approve the event via legacy PATCH at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

    Given I update the event at "%{eventUrl}" from "events/event-with-workflow-status-ready-for-validation.json"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"

  Scenario: Ignore an event workflowStatus update from REJECTED to READY FOR VALIDATION via complete overwrite
    Given I create a place from "places/place.json" and save the "placeId" as "uuid_place"
    Given I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "url" as "eventUrl"
    When I reject the event via legacy PATCH at "%{eventUrl}" with reason "Rejected reason"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

    Given I update the event at "%{eventUrl}" from "events/event-with-workflow-status-ready-for-validation.json"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"

  Scenario: Ignore an event workflowStatus update from DELETED to READY FOR VALIDATION via complete overwrite
    Given I create a place from "places/place.json" and save the "placeId" as "uuid_place"
    Given I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "url" as "eventUrl"
    When I approve the event via legacy PATCH at "%{eventUrl}"
    When I delete the event at "%{eventUrl}"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

    Given I update the event at "%{eventUrl}" from "events/event-with-workflow-status-ready-for-validation.json"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: Trying to publish an event as a moderator without sufficient permissions
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    And I am authorized as JWT provider v1 user "validator_diest"
    When I set the JSON request payload to:
    """
    {"workflowStatus": "APPROVED"}
    """
    And I send a PUT request to "%{eventUrl}/workflow-status"
    Then the response status should be "403"
    And the JSON response at "detail" should include 'has no permission "Aanbod modereren" on resource'

  Scenario: Trying to publish an event as a moderator without sufficient permissions via legacy PATCH
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    And I am authorized as JWT provider v1 user "validator_diest"
    When I send "application/ld+json;domain-model=Publish"
    And I send a PATCH request to "%{eventUrl}"
    Then the response status should be "403"
    And the JSON response at "detail" should include 'has no permission "Aanbod bewerken" on resource'
