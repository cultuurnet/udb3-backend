Feature: Test place bookingAvailability property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update booking availability of place
    Given I set the JSON request payload to:
    """
    {"type":"Available"}
    """
    And I send a PUT request to "%{placeUrl}/booking-availability"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/uitdatabank/calendar-type-not-supported",
      "title": "Calendar type not supported",
      "status": 400,
      "detail": "Updating booking availability on calendar type: \"PERMANENT\" is not supported. Only single and multiple calendar types can be updated."
    }
    """