Feature: Test place subEvent property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Trying to patch a sub event of a place
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingAvailability": {
          "type": "Unavailable"
        }
      }
    ]
    """
    And I send a PATCH request to "%{placeUrl}/sub-events"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404
    }
    """