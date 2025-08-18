Feature: Test skipping UDB3 suggestions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create a production
    Given I create a minimal permanent event and save the "id" as "eventId"
    And I create a minimal permanent event and save the "id" as "otherEventId"

    When I set the JSON request payload to:
    """
    {
      "eventIds": [
        "%{eventId}",
        "%{otherEventId}"
      ]
    }
    """
    And I send a POST request to "/productions/skip"

    # There is no API call to get suggestions or skipped suggestions
    # So only the response code is validated
    Then the response status should be "200"
