Feature: Test event departure places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal place and save the "url" as "departurePlaceUrl1"
    And I create a minimal place and save the "url" as "departurePlaceUrl2"

  Scenario: Set departure places on a childrenOnly event
    When I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl1}",
      "%{departurePlaceUrl2}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departurePlaces/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "departurePlaces/0" should be "%{departurePlaceUrl1}"
    And the JSON response at "departurePlaces/1" should be "%{departurePlaceUrl2}"

  Scenario: Remove departure places with empty array
    When I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl1}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departurePlaces/"
    Then the response status should be "204"
    And I set the JSON request payload to:
    """
    []
    """
    And I send a PUT request to "%{eventUrl}/departurePlaces/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "departurePlaces"

  Scenario: Reject departure places on non-childrenOnly event
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl1}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departurePlaces/"
    Then the response status should be "400"

  Scenario: Reject departure places with non-existing place
    When I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl1}",
      "%{baseUrl}/place/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departurePlaces/"
    Then the response status should be "400"

  Scenario: Changing audienceType away from childrenOnly keeps departure places
    When I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl1}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/departurePlaces/"
    Then the response status should be "204"
    And I set the JSON request payload to:
    """
    {
      "audienceType": "everyone"
    }
    """
    And I send a PUT request to "%{eventUrl}/audience"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "departurePlaces/0" should be "%{departurePlaceUrl1}"
