Feature: Test the UDB3 image API

  Background:
    Given I am using the UDB3 base URL
    And I am not authorized
    And I accept "application/json"

  Scenario: Get image with invalid UUID
    When I send a GET request to "/media/foobar"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "The media object with id \"foobar\" was not found."
    }
    """

  Scenario: Get image with non-existing UUID
    When I send a GET request to "/media/EFC0996F-E2FD-42FF-9D95-D9818A2AD6D6"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "The media object with id \"EFC0996F-E2FD-42FF-9D95-D9818A2AD6D6\" was not found."
    }
    """