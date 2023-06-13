Feature: Test deleting places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

   Scenario: Delete place
    When I delete the place at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"
