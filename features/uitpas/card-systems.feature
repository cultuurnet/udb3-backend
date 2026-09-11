Feature: Manage the card systems of a real UiTPAS event

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I set the variable "uitpasEventId" to "5ed91e8d-a3fd-4ab9-9498-f4b0ebdd5ee8"
    And I set the variable "cardSystemA" to "5"
    And I set the variable "cardSystemAName" to "UiTPAS Regio Gent"
    And I set the variable "cardSystemB" to "3"
    And I set the variable "cardSystemBName" to "Paspartoe"
    And I set the JSON request payload to:
    """
    [%{cardSystemA}, %{cardSystemB}]
    """
    And I send a PUT request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"

  Scenario: Get the card systems of an UiTPAS event
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "%{cardSystemA}": {
        "id": %{cardSystemA},
        "name": "%{cardSystemAName}",
        "enabled": true,
        "distributionKeys": []
      },
      "%{cardSystemB}": {
        "id": %{cardSystemB},
        "name": "%{cardSystemBName}",
        "enabled": true,
        "distributionKeys": []
      }
    }
    """

  Scenario: Disabling one card system keeps it in the response with enabled false
    When I send a DELETE request to "/uitpas/events/%{uitpasEventId}/card-systems/%{cardSystemB}"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "%{cardSystemA}": {
        "id": %{cardSystemA},
        "name": "%{cardSystemAName}",
        "enabled": true,
        "distributionKeys": []
      },
      "%{cardSystemB}": {
        "id": %{cardSystemB},
        "name": "%{cardSystemBName}",
        "enabled": false,
        "distributionKeys": []
      }
    }
    """

  Scenario: Enabling a disabled card system toggles the flag back to true
    Given I send a DELETE request to "/uitpas/events/%{uitpasEventId}/card-systems/%{cardSystemB}"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the JSON response at "%{cardSystemB}/enabled" should be "false"
    When I send a PUT request to "/uitpas/events/%{uitpasEventId}/card-systems/%{cardSystemB}"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response at "%{cardSystemB}/enabled" should be "true"
    And the JSON response at "%{cardSystemA}/enabled" should be "true"

  Scenario: Enabling a card system that is already enabled leaves the flag true
    When I send a PUT request to "/uitpas/events/%{uitpasEventId}/card-systems/%{cardSystemB}"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response at "%{cardSystemA}/enabled" should be "true"
    And the JSON response at "%{cardSystemB}/enabled" should be "true"

  Scenario: Disabling a card system that is already disabled leaves the flag false
    Given I send a DELETE request to "/uitpas/events/%{uitpasEventId}/card-systems/%{cardSystemB}"
    Then the response status should be "200"
    When I send a DELETE request to "/uitpas/events/%{uitpasEventId}/card-systems/%{cardSystemB}"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response at "%{cardSystemA}/enabled" should be "true"
    And the JSON response at "%{cardSystemB}/enabled" should be "false"

  Scenario: Setting card systems enables the given ones and disables the rest
    When I set the JSON request payload to:
    """
    [%{cardSystemB}]
    """
    And I send a PUT request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response at "%{cardSystemA}/enabled" should be "false"
    And the JSON response at "%{cardSystemB}/enabled" should be "true"

  Scenario: Clearing the card systems disables all of them
    When I set the JSON request payload to:
    """
    []
    """
    And I send a PUT request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    When I send a GET request to "/uitpas/events/%{uitpasEventId}/card-systems"
    Then the response status should be "200"
    And the JSON response at "%{cardSystemA}/enabled" should be "false"
    And the JSON response at "%{cardSystemB}/enabled" should be "false"
