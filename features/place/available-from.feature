Feature: Test place availableFrom property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update availableFrom on draft place via PATCH
    When I set the JSON request payload to:
      """
      { "availableFrom": "2031-11-15T11:22:33+00:00" }
      """
    And I send a PUT request to "%{placeUrl}/available-from"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "availableFrom" should be "2031-11-15T11:22:33+00:00"

  Scenario: Update availableFrom on published place
    When I publish the place via legacy PATCH at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    When I set the JSON request payload to:
      """
      { "availableFrom": "2031-11-15T11:22:33+00:00" }
      """
    And I send a PUT request to "%{placeUrl}/available-from"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "availableFrom" should be "2031-11-15T11:22:33+00:00"

  Scenario: Update availableFrom on approved place
    When I publish the place via legacy PATCH at "%{placeUrl}"
    And I approve the place via legacy PATCH at "%{placeUrl}"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "APPROVED"
    When I set the JSON request payload to:
      """
      { "availableFrom": "2031-11-15T11:22:33+00:00" }
      """
    And I send a PUT request to "%{placeUrl}/available-from"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "availableFrom" should be "2031-11-15T11:22:33+00:00"

  Scenario: Update available from on rejected place
    When I publish the place via legacy PATCH at "%{placeUrl}"
    And I reject the place via legacy PATCH at "%{placeUrl}" with reason "The reject reason"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "workflowStatus" should be "REJECTED"
    When I set the JSON request payload to:
      """
      { "availableFrom": "2031-11-15T11:22:33+00:00" }
      """
    When I send a PUT request to "%{placeUrl}/available-from"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "availableFrom" should be "2031-11-15T11:22:33+00:00"
