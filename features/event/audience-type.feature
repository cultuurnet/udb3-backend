Feature: Test event audienceType property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create an event with audienceType childrenOnly
    When I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "audience/audienceType" should be "childrenOnly"

  Scenario: Update an event to audienceType childrenOnly
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "audienceType": "childrenOnly"
    }
    """
    And I send a PUT request to "%{eventUrl}/audience"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "audience/audienceType" should be "childrenOnly"

  Scenario: Remove audienceType childrenOnly from an event
    When I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "audienceType": "everyone"
    }
    """
    And I send a PUT request to "%{eventUrl}/audience"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "audience/audienceType" should be "everyone"
