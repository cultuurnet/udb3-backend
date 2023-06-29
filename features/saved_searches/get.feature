Feature: Test the UDB3 saved searches API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: get a saved search
    Given I create a random name of 12 characters
    And I set the JSON request payload to:
    """
      {"name":"%{name}","query":"Avondlessen"}
    """
    And I send a POST request to "/saved-searches/v3"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "/" should include "%{name}"
