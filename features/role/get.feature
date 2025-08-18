Feature: Test the UDB3 roles API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"

  Scenario: Get all roles with a specific name
    Given I set the JSON request payload to:
       """
       { "name": "test_role" }
       """
    And I send a POST request to "/roles/"
    And the response status should be "201"
    When I send a GET request to "/roles/" with parameters:
       | limit   | 1   |
       | query   | test_role |
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 1
    And the JSON response at "totalItems" should not be 0
    And the JSON response at "member/0/name" should not be ""

  Scenario: Get non-existing role
    When I send a GET request to "/roles/this-does-not-exist"
    Then the response status should be "404"
    And the JSON response at "detail" should be 'The Role with id "this-does-not-exist" was not found.'

  Scenario: Get all labels for a role
    Given I set the JSON request payload to:
    """
    { "name": "test_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I create a random name of 11 characters and keep it as "firstLabelName"
    And I set the JSON request payload to:
      """
      { "name": "%{firstLabelName}", "visibility": "visible", "privacy": "public" }
      """
    And I send a POST request to "/labels/"
    And I keep the value of the JSON response at "uuid" as "firstLabelId"
    And I send a PUT request to "/roles/%{roleId}/labels/%{firstLabelId}"
    And I create a random name of 11 characters and keep it as "secondLabelName"
    And I set the JSON request payload to:
      """
      { "name": "%{secondLabelName}", "visibility": "visible", "privacy": "public" }
      """
    And I send a POST request to "/labels/"
    And I keep the value of the JSON response at "uuid" as "secondLabelId"
    And I send a PUT request to "/roles/%{roleId}/labels/%{secondLabelId}"

    When I send a GET request to "/roles/%{roleId}/labels"

    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      {
        "uuid": "%{firstLabelId}",
        "name": "%{firstLabelName}",
        "visibility": "visible",
        "privacy": "public",
        "excluded": false
      },
      {
        "uuid": "%{secondLabelId}",
        "name": "%{secondLabelName}",
        "visibility": "visible",
        "privacy": "public",
        "excluded": false
      }
    ]
    """

  Scenario: An empty array is returned when the role has no labels
    Given I set the JSON request payload to:
    """
    { "name": "test_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "roleId"

    When I send a GET request to "/roles/%{roleId}/labels"

    Then the response status should be "200"
    And the JSON response should be:
    """
    []
    """

  Scenario: When getting labels for non-existing role an error is returned
    When I send a GET request to "/roles/this-does-not-exist/labels"
    Then the response status should be "404"
    And the JSON response at "detail" should be 'The Role with id "this-does-not-exist" was not found.'
