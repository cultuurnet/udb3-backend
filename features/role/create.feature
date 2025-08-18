Feature: Test the UDB3 roles API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create a new role
  	Given I set the JSON request payload to:
       """
       { "name": "test_role" }
       """
	When I send a POST request to "/roles/"
	Then the response status should be "201"
	  And I keep the value of the JSON response at "roleId" as "id_role"
	When I send a GET request to "/roles/%{id_role}"
	Then the response status should be "200"
    And the JSON response at "name" should be "test_role"
