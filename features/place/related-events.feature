Feature: Test related events of a place

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Test related events of a place
    Given I create a minimal permanent event and save the "id" as "eventId1"
    And I create a minimal permanent event and save the "id" as "eventId2"
    And I create a minimal permanent event and save the "id" as "eventId3"

    When I send a GET request to "%{placeUrl}/events"

    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "events" should have 3 entries
    And the JSON response at "events" should include:
    """
      {
        "@id": "%{eventId1}"
      }
    """
    And the JSON response at "events" should include:
    """
      {
        "@id": "%{eventId2}"
      }
    """
    And the JSON response at "events" should include:
    """
      {
        "@id": "%{eventId3}"
      }
    """
