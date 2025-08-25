Feature: Test the UiTPAS events

  Background:
    Given I am using the UDB3 base URL
    And I send and accept "application/json"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"

  Scenario: Get details of an event that is not an UiTPAS event
    When I send a GET request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "Event with id '18827e56-mock-4961-a5c8-7acd5dcfed9a' was not found in UiTPAS. Are you sure it is an UiTPAS event?"
    }
    """

  Scenario: Get card systems of an event that is not an UiTPAS event
    When I send a GET request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "Event with id '18827e56-mock-4961-a5c8-7acd5dcfed9a' was not found in UiTPAS. Are you sure it is an UiTPAS event?"
    }
    """

  Scenario: Clear card systems of an event that is not an UiTPAS event
    When I set the JSON request payload to:
    """
    []
    """
    And I send a PUT request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems"
    Then the response status should be "200"

  Scenario: Update card systems of an event that is not an UiTPAS event
    When I set the JSON request payload to:
    """
    [8]
    """
    And I send a PUT request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems"
    Then the response status should be "200"

  Scenario: Enable 1 card system of an event that is not an UiTPAS event
    And I send a PUT request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems/8"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "Event with id '18827e56-mock-4961-a5c8-7acd5dcfed9a' was not found in UiTPAS. Are you sure it is an UiTPAS event?"
    }
    """

  Scenario: Enable 1 card system distribution key of an event that is not an UiTPAS event
    And I send a PUT request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems/8/distribution-key/10"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "Event with id '18827e56-mock-4961-a5c8-7acd5dcfed9a' was not found in UiTPAS. Are you sure it is an UiTPAS event?"
    }
    """

  Scenario: Disable 1 card system of an event that is not an UiTPAS event
    And I send a DELETE request to "/uitpas/events/18827e56-mock-4961-a5c8-7acd5dcfed9a/card-systems/8"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "Event with id '18827e56-mock-4961-a5c8-7acd5dcfed9a' was not found in UiTPAS. Are you sure it is an UiTPAS event?"
    }
    """

