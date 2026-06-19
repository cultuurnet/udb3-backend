Feature: Test event childrenOnly property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Enable childrenOnly via PUT
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "childrenOnly": true
    }
    """
    And I send a PUT request to "%{eventUrl}/children-only"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "childrenOnly" should be true

  Scenario: childrenOnly is omitted from the GET response by default
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "childrenOnly"

  Scenario: Disabling childrenOnly removes it from the GET response
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "childrenOnly": true
    }
    """
    And I send a PUT request to "%{eventUrl}/children-only"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "childrenOnly" should be true
    And I set the JSON request payload to:
    """
    {
      "childrenOnly": false
    }
    """
    And I send a PUT request to "%{eventUrl}/children-only"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "childrenOnly"

  Scenario: Enabling childrenOnly unlocks departure places
    Given I create a minimal place and save the "url" as "departurePlaceUrl"
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departure-places/"
    Then the response status should be "400"
    And I set the JSON request payload to:
    """
    {
      "childrenOnly": true
    }
    """
    And I send a PUT request to "%{eventUrl}/children-only"
    Then the response status should be "204"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departure-places/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "childrenOnly" should be true
    And the JSON response at "departurePlaces/0" should be "%{departurePlaceUrl}"
