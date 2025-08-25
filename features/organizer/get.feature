Feature: Test retrieving organizers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"

  Scenario: Get non-existing organizer
    When I send a GET request to "/organizers/097e8b65-efcf-4310-abaf-ce7c083e3c91"
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
