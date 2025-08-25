Feature: Test place name property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update place name dutch
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test_place" }
    """
    When I send a PUT request to "%{placeUrl}/name/nl"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "name" should be:
    """
    { "nl": "Updated name test_place" }
    """

  Scenario: Update place name english
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test_place in English" }
    """
    When I send a PUT request to "%{placeUrl}/name/en"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Cafe Den Hemel",
      "en": "Updated name test_place in English"
    }
    """

  Scenario: Update place name dutch through legacy endpoint
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test_place through legacy" }
    """
    When I send a POST request to "%{placeUrl}/nl/title"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "name" should be:
    """
    { "nl": "Updated name test_place through legacy" }
    """

  Scenario: Update place name english through legacy endpoint
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test_place in English through legacy" }
    """
    When I send a POST request to "%{placeUrl}/en/title"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Cafe Den Hemel",
      "en": "Updated name test_place in English through legacy"
    }
    """
