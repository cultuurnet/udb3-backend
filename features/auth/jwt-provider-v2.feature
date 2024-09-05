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
      "uuid":"d759fd36-fb28-4fe3-8ec6-b4aaf990371d",
      "email":"dev+udbtestinvoerder@publiq.be",
      "username":"dev+udbtestinvoerder@publiq.be",
      "id":"d759fd36-fb28-4fe3-8ec6-b4aaf990371d",
      "nick":"dev+udbtestinvoerder@publiq.be"
    }
    """

  Scenario: I can create a place and an event with a JWT provider v2 token
    Given I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    When I create a minimal permanent event and save the "url" as "eventUrl"
    Then the response status should be "201"
