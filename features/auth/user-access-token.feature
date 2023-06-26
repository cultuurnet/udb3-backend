Feature: Test authentication with Auth0 user access tokens

  Background:
    Given I am using the UDB3 base URL
    And I am not using an UiTID v1 API key
    And I send and accept "application/json"

  Scenario: I can get my user details with an Auth0 user access token
    Given I am authorized with an Auth0 user access token for "invoerder" via client "test_client"
    When I send a GET request to "/user"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "uuid":"auth0|630774d3b0c2b2dd21cf781d",
      "email":"bert+acceptance-tests@publiq.be",
      "username":"bert+acceptance-tests",
      "id":"auth0|630774d3b0c2b2dd21cf781d",
      "nick":"bert+acceptance-tests"
    }
    """

  Scenario: I cannot get my user details with an Auth0 user access token if the client cannot access Entry API
    Given I am authorized with an Auth0 user access token for "invoerder" via client "test_client_sapi3_only"
    When I send a GET request to "/user"
    Then the response status should be "403"
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/auth/forbidden",
      "title": "Forbidden",
      "status": 403,
      "detail": "The given token and its related client are not allowed to access EntryAPI."
    }
    """

  Scenario: I can create a place and an event with an Auth0 user access token
    Given I am authorized with an Auth0 user access token for "invoerder" via client "test_client"
    And I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    When I create a minimal permanent event and save the "url" as "eventUrl"
    Then the response status should be "201"

  Scenario: I cannot create a place with an Auth0 user access token if the client cannot access EntryAPI
    Given I am authorized with an Auth0 user access token for "invoerder" via client "test_client_sapi3_only"
    When I set the JSON request payload from "places/place.json"
    And I send a POST request to "/places"
    Then the response status should be "403"

