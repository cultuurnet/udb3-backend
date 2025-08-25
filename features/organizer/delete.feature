Feature: Test deleting organizers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I create a minimal organizer and save the "url" as "organizerUrl"
    And I keep the value of the JSON response at "id" as "organizerId"

  Scenario: delete organizer
    When I delete the organizer at "%{organizerUrl}"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "workflowStatus" should be "DELETED"

  Scenario: delete non-existing organizer
    When I send a DELETE request to "/organizers/097e8b65-efcf-4310-abaf-ce7c083e3c91"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The Organizer with id \"097e8b65-efcf-4310-abaf-ce7c083e3c91\" was not found."
    }
    """

  Scenario: delete organizer with relations
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I send a PUT request to "%{placeUrl}/organizer/%{organizerId}"
    And I send a PUT request to "%{eventUrl}/organizer/%{organizerId}"
    And I get the place at "%{placeUrl}"
    And the JSON response at "organizer/@id" should be "%{organizerUrl}"
    And I get the event at "%{eventUrl}"
    And the JSON response at "organizer/@id" should be "%{organizerUrl}"
    When I delete the organizer at "%{organizerUrl}"
    Then I get the place at "%{placeUrl}"
    And the JSON response should not have "organizer"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "organizer"
