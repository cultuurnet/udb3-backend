Feature: Test the UiTPAS labels

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"

  Scenario: Get the mapping of UiTPAS card system ids to labels
    When I send a GET request to "/uitpas/labels"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "1": "UiTPAS Regio Aalst",
      "3": "Paspartoe",
      "5": "UiTPAS Gent",
      "7": "UiTPAS 7",
      "8": "UiTPAS Zuidwest",
      "12": "UiTPAS Kempen",
      "13": "UiTPAS Mechelen",
      "14": "UiTPAS Maasmechelen",
      "15": "UiTPAS"
    }
    """
