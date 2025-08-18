Feature: Test event name property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "url" as "eventUrl"

  Scenario: Update event name dutch
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test event" }
    """
    When I send a PUT request to "%{eventUrl}/name/nl"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "name" should be:
    """
    { "nl": "Updated name test event" }
    """

  Scenario: Update event name english
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test event in English" }
    """
    When I send a PUT request to "%{eventUrl}/name/en"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Permanent event",
      "en": "Updated name test event in English"
    }
    """

  Scenario: Update event name dutch through legacy endpoint
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test event through legacy" }
    """
    When I send a POST request to "%{eventUrl}/nl/title"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "name" should be:
    """
    { "nl": "Updated name test event through legacy" }
    """

  Scenario: Update event name english through legacy endpoint
    Given I set the JSON request payload to:
    """
    { "name": "Updated name test event in English through legacy" }
    """
    When I send a POST request to "%{eventUrl}/en/title"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Permanent event",
      "en": "Updated name test event in English through legacy"
    }
    """
