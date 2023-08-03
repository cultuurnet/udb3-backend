Feature: Test place description property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"

  Scenario: Delete the last description of an event
    When I send a DELETE request to "%{eventUrl}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}"
    Then the response status should be "200"
    And the JSON response should not have "description"

  Scenario: Delete a description of an event, with one description left
    When I set the JSON request payload to:
    """
    { "description": "Le description" }
    """
    And I send a PUT request to "%{eventUrl}/description/fr"
    Then the response status should be "204"
    When I send a DELETE request to "%{eventUrl}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}"
    Then the response status should be "200"
    And the JSON response at "description" should be:
    """
      {"fr": "Le description"}
    """