@external
Feature: Get the UiTPAS details of an event

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I set the variable "uitpasEventId" to "5ed91e8d-a3fd-4ab9-9498-f4b0ebdd5ee8"

  Scenario: Get the UiTPAS details of an event
    When I send a GET request to "/uitpas/events/%{uitpasEventId}"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "@id": "%{baseUrl}/uitpas/events/%{uitpasEventId}",
      "cardSystems": "%{baseUrl}/uitpas/events/%{uitpasEventId}/card-systems"
    }
    """
