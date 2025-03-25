Feature: Test the UDB3 roles API with users

  Background:
    Given I am using the UDB3 base URL
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario: Adding an invalid role uuid to a user gives an error
    When I send a PUT request to "/roles/not-a-uuid/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https:\/\/api.publiq.be\/probs\/url\/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "The Role with id \"not-a-uuid\" was not found."
    }
    """

  Scenario: Add a role to a user
    Given I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "role_uuid"

    When I send a PUT request to "/roles/%{role_uuid}/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"

    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}/users"
    Then the JSON response should be:
    """
    [
      {
        "uuid": "f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5",
        "email": "dev+udbtestinvoerder_1@publiq.be",
        "username": "dev+udbtestinvoerder_1@publiq.be"
      }
    ]
    """

  Scenario: Removing an invalid role uuid from a user gives an error
    When I send a DELETE request to "/roles/not-a-uuid/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
      "type": "https:\/\/api.publiq.be\/probs\/url\/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "The Role with id \"not-a-uuid\" was not found."
    }
    """

  Scenario: Remove a role from a user
    Given I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "role_uuid"
    And I send a PUT request to "/roles/%{role_uuid}/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"

    When I send a DELETE request to "/roles/%{role_uuid}/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"

    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}/users"
    Then the JSON response should be:
    """
    []
    """

  Scenario: Get users for a non-existing role
    When I send a GET request to "/roles/c45706f4-c254-4f7f-8c34-2fdb683941f0/users"

    Then the response status should be "404"
    And the JSON response at "detail" should be 'The Role with id "c45706f4-c254-4f7f-8c34-2fdb683941f0" was not found.'

  Scenario: Get all users for a role
    Given I set the JSON request payload to:
    """
    { "name": "test_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"
    And I send a PUT request to "/roles/%{roleId}/users/google-oauth2|105581372645959335476"

    When I send a GET request to "/roles/%{roleId}/users"

    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      {
        "uuid": "f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5",
        "email": "dev+udbtestinvoerder_1@publiq.be",
        "username": "dev+udbtestinvoerder_1@publiq.be"
      },
      {
        "uuid": "30a34ead-4733-43ec-99d1-47b45c01cf2f",
        "email": "luc@madewithlove.be",
        "username": "luc"
      }
    ]
    """

  Scenario: When the user has no roles an empty array is returned
    Given I remove all roles for user with id "f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"

    When I send a GET request to "/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5/roles"

    Then the response status should be "200"
    And the JSON response should be:
    """
    []
    """

  Scenario: Gets all roles from a user
    Given I remove all roles for user with id "f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"
    And I set the JSON request payload to:
    """
    { "name": "test_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"
    And I set the JSON request payload to:
    """
    { "name": "another_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "anotherRoleId"
    And I send a PUT request to "/roles/%{anotherRoleId}/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5"

    When I send a GET request to "/users/f0ecb695-48b9-45d9-8874-b7ab4e9d5bc5/roles"

    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      {
        "uuid": "%{roleId}",
        "name": "test_role",
        "permissions": []
      },
      {
        "uuid": "%{anotherRoleId}",
        "name": "another_role",
        "permissions": []
      }
    ]
    """

  Scenario: Get all roles from the current user
    Given I send a GET request to "/user"
    And I keep the value of the JSON response at "uuid" as "currentUserId"
    And I set the JSON request payload to:
    """
    { "name": "test_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/users/%{currentUserId}"
    And I set the JSON request payload to:
    """
    { "name": "another_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "anotherRoleId"
    And I send a PUT request to "/roles/%{anotherRoleId}/users/%{currentUserId}"

    When I send a GET request to "/user/roles"

    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      {
        "uuid": "%{roleId}",
        "name": "test_role",
        "permissions": []
      },
      {
        "uuid": "%{anotherRoleId}",
        "name": "another_role",
        "permissions": []
      }
    ]
    """

    And I remove all roles for user with id "%{currentUserId}"
