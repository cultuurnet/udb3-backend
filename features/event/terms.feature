Feature: Test event terms property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  @bugfix # https://jira.uitdatabank.be/browse/III-4705
  Scenario: Create an event with an invalid term id without label or domain
    When I set the JSON request payload from "events/event-with-invalid-term-id.json"
    When I send a POST request to "/events"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "schemaErrors": [
        {
          "jsonPointer": "/terms/0/id",
          "error": "The term 1.51.12.0. does not exist or is not supported"
        }
      ]
    }
    """

  Scenario: Create an event with an empty term id
    When I set the JSON request payload from "events/event-with-empty-term-id.json"
    When I send a POST request to "/events"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "schemaErrors": [
        {
          "jsonPointer": "/terms/0/id",
          "error": "Category ID should not be empty."
        }
      ]
    }
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4705
  Scenario: Create an event with a flandersregion term that should be ignored
    When I create an event from "events/event-with-flandersregion-term.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "terms" should be:
    """
    [
      {
        "id": "0.50.4.0.0",
        "label": "Concert",
        "domain": "eventtype"
      }
    ]
    """
