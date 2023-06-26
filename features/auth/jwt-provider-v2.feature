Feature: Test authentication with JWT provider v2 tokens

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"
    And I am authorized as JWT provider v2 user "invoerder"

  Scenario: I can get my user details with a JWT provider v2 token
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

  Scenario: I can create a place and an event with a JWT provider v2 token
    Given I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    When I create a minimal permanent event and save the "url" as "eventUrl"
    Then the response status should be "201"
