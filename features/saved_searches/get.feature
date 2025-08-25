Feature: Test the UDB3 saved searches API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: get a saved search
    Given I create a random name of 12 characters
    And I set the JSON request payload to:
    """
      {"name":"%{name}","query":"Avondlessen"}
    """
    And I send a POST request to "/saved-searches/v3"
    Then the response status should be "201"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "/" should include "%{name}"

  Scenario: get a saved search with a dirty query
    Given I create a random name of 12 characters
    And I set the JSON request payload to:
    """
      {"name":"%{name}","query":"address.*.addressLocality:Scherpenheuvel-Zichem AND dateRange:[2015-05-31T22:00:00%2B00:00 TO 2015-07-31T21:59:59%2B00:00]"}
    """
    And I send a POST request to "/saved-searches/v3"
    Then the response status should be "201"
    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "/" should include "address.*.addressLocality:Scherpenheuvel-Zichem AND dateRange:[2015-05-31T22:00:00+00:00 TO 2015-07-31T21:59:59+00:00]"