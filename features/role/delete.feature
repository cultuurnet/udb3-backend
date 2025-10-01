Feature: Test the UDB3 roles API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"

  Scenario: An error is thrown when deleting a role with invalid id
    When I send a DELETE request to "/roles/not-a-uuid"
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

  Scenario: Delete a role
    Given I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "role_uuid"

    When I send a DELETE request to "/roles/%{role_uuid}"

    Then the response status should be "204"
    When I send a GET request to "/roles/%{role_uuid}"
    Then the response status should be "404"
