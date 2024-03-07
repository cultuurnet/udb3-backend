Feature: Test the UDB3 saved searches API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: I fail to update a saved search that does not exist
    And I set the JSON request payload to:
       """
       {"name":"This will never work","query":"Avondlessen"}
       """
    When I send a PUT request to "/saved-searches/v3/85d6de44-9279-4780-9144-3ec6abf0ac66"
    Then the response status should be "404"

  Scenario: I update a saved search
    Given I create a random name of 12 characters
    And I set the JSON request payload to:
       """
       {"name":"This should change","query":"Lessen in de ochtend"}
       """
    When I send a POST request to "/saved-searches/v3/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "id"

    And I set the JSON request payload to:
       """
       {"name":"%{name}","query":"Lessen in de avond"}
       """
    When I send a PUT request to "/saved-searches/v3/%{id}"
    Then the response status should be "204"

    When I send a GET request to "/saved-searches/v3"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response should include:
      """
      {"name":"%{name}","query":"Lessen in de avond","id":"%{id}"}
      """