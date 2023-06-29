Feature: Test the UDB3 roles API with users

  Background:
    Given I am using the UDB3 base URL
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario: It gives an error when adding invalid role uuid to user
    When I send a PUT request to "/roles/not-a-uuid/users/auth0|631748dba64ea78e3983b207"
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

  Scenario: It can add a role to a user
    Given I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "role_uuid"

    When I send a PUT request to "/roles/%{role_uuid}/users/auth0|631748dba64ea78e3983b207"

    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}/users"
    Then the JSON response should be:
    """
    [
      {
        "uuid": "d0859cfa-4904-495a-863e-424923a9cb27",
        "email": "stef@madewithlove.com",
        "username": "stef"
      }
    ]
    """

  Scenario: It gives an error when removing invalid role uuid to user
    When I send a DELETE request to "/roles/not-a-uuid/users/auth0|631748dba64ea78e3983b207"
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

  Scenario: It can remove a role from a user
    Given I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "role_uuid"
    And I send a PUT request to "/roles/%{role_uuid}/users/auth0|631748dba64ea78e3983b207"

    When I send a DELETE request to "/roles/%{role_uuid}/users/auth0|631748dba64ea78e3983b207"

    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}/users"
    Then the JSON response should be:
    """
    []
    """

  Scenario: It gets users for a non-existing role
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
    And I send a PUT request to "/roles/%{roleId}/users/auth0|631748dba64ea78e3983b207"
    And I send a PUT request to "/roles/%{roleId}/users/google-oauth2|105581372645959335476"

    When I send a GET request to "/roles/%{roleId}/users"

    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      {
        "uuid": "d0859cfa-4904-495a-863e-424923a9cb27",
        "email": "stef@madewithlove.com",
        "username": "stef"
      },
      {
        "uuid": "google-oauth2|105581372645959335476",
        "email": "luc@madewithlove.be",
        "username": "luc"
      }
    ]
    """

  Scenario: It returns an empty array when the user has no roles
    Given I remove all roles for user with id "auth0|631748dba64ea78e3983b207"

    When I send a GET request to "/users/auth0|631748dba64ea78e3983b207/roles"

    Then the response status should be "200"
    And the JSON response should be:
    """
    []
    """

  Scenario: It gets all roles from a user
    Given I remove all roles for user with id "auth0|631748dba64ea78e3983b207"
    And I set the JSON request payload to:
    """
    { "name": "test_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/users/auth0|631748dba64ea78e3983b207"
    And I set the JSON request payload to:
    """
    { "name": "another_role" }
    """
    And I send a POST request to "/roles/"
    And I keep the value of the JSON response at "roleId" as "anotherRoleId"
    And I send a PUT request to "/roles/%{anotherRoleId}/users/auth0|631748dba64ea78e3983b207"

    When I send a GET request to "/users/auth0|631748dba64ea78e3983b207/roles"

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

  Scenario: It gets all roles from the current user
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
