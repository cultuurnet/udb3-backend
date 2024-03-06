Feature: Test the UDB3 saved searches API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: create a saved search
    Given I create a random name of 12 characters
    And I set the JSON request payload to:
       """
       {"name":"%{name}","query":"Avondlessen"}
       """
    When I send a POST request to "/saved-searches/v3"
    Then the response status should be "201"
    And the JSON response should have "id"
