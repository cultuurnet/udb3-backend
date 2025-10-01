Feature: Test updating organizers of places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  Scenario: Update with a valid organizer
    When I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    And I keep the value of the JSON response at "organizerId" as "organizerId"
    And I send a PUT request to "%{placeUrl}/organizer/%{organizerId}"
    Then the response status should be "204"
    And I send a GET request to "%{placeUrl}"
    Then the response status should be "200"
    And the JSON response at "organizer/@id" should be "%{organizerUrl}"

  Scenario: Update with an invalid organizer
    When I send a PUT request to "%{placeUrl}/organizer/qwerty"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "The Organizer with id \"qwerty\" was not found."
    }
    """

  Scenario: Update with a valid organizer via Post Request
    When I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl2"
    And I keep the value of the JSON response at "organizerId" as "organizer2Id"
    And I set the JSON request payload to:
    """
    {
      "organizer": "%{organizer2Id}"
    }
    """
    And I send a POST request to "%{placeUrl}/organizer"
    Then the response status should be "204"
    And I send a GET request to "%{placeUrl}"
    Then the response status should be "200"
    And the JSON response at "organizer/@id" should be "%{organizerUrl2}"

  Scenario: Update with a invalid organizer via Post Request
    When I set the JSON request payload to:
    """
    {
      "organizer": "qwerty"
    }
    """
    And I send a POST request to "%{placeUrl}/organizer"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "Organizer with id \"qwerty\" does not exist."
    }
    """
