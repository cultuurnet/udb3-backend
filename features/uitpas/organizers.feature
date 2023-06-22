Feature: Test the UiTPAS organizers

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"

  Scenario: Get card systems of an organizer that is not an UiTPAS organizer
    When I send a GET request to "/uitpas/organizers/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "Organizer with id '18827e56-mock-4961-a5c8-7acd5dcfed9a' was not found in UiTPAS. Are you sure it is an UiTPAS organizer?"
    }
    """
