Feature: Test the UDB3 roles API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "role_uuid"

  Scenario: Update role name
    When I set the JSON request payload to:
    """
    {
      "name": "updated name"
    }
    """
    And I send "application/ld+json;domain-model=RenameRole"
    And I send a PATCH request to "/roles/%{role_uuid}"
    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}"
    And the JSON response at "name" should be "updated name"

  Scenario: Update role with constraint via POST
    Given I set the JSON request payload to:
     """
        { "query":"address.nl.postalCode:3000" }
     """
  When I send a POST request to "/roles/%{role_uuid}/constraints/"
  Then the response status should be "204"
  When I send a GET request to "/roles/%{role_uuid}"
  Then the response status should be "200"
    And the JSON response at "constraint" should be "address.nl.postalCode:3000"

  Scenario: Update role with constraint via PUT
    Given I set the JSON request payload to:
     """
        { "query":"address.nl.postalCode:3000" }
     """
    And I send a POST request to "/roles/%{role_uuid}/constraints/"
    And I set the JSON request payload to:
     """
        { "query":"address.nl.postalCode:8000" }
     """
    When I send a PUT request to "/roles/%{role_uuid}/constraints/"
    Then the response status should be "204"
    When I send a GET request to "/roles/%{role_uuid}"
    Then the response status should be "200"
    And the JSON response at "constraint" should be "address.nl.postalCode:8000"

  Scenario: Remove constraint from role
    Given I set the JSON request payload to:
     """
        { "query":"address.nl.postalCode:3000" }
     """
    And I send a POST request to "/roles/%{role_uuid}/constraints/"
    When I send a DELETE request to "/roles/%{role_uuid}/constraints"
    Then the response status should be "204"
    And the JSON response should not have "constraint"

  Scenario: Update role with permissions
    Given I send and accept "application/json"
    When I send a PUT request to "/roles/%{role_uuid}/permissions/AANBOD_BEWERKEN"
    Then the response status should be "204"
    When I send a GET request to "/roles/%{role_uuid}"
    Then the response status should be "200"
      And the JSON response at "permissions" should be:
     """
     [
       "AANBOD_BEWERKEN"
     ]
     """

  Scenario: Delete permission from role
    Given I send and accept "application/json"
    And I send a PUT request to "/roles/%{role_uuid}/permissions/AANBOD_BEWERKEN"

    When I send a DELETE request to "/roles/%{role_uuid}/permissions/AANBOD_BEWERKEN"

    Then the response status should be "204"
    When I send a GET request to "/roles/%{role_uuid}"
    Then the response status should be "200"
    And the JSON response at "permissions" should be:
     """
     []
     """

  Scenario: Add a label to a role by uuid
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "label_uuid"

    When I send a PUT request to "/roles/%{role_uuid}/labels/%{label_uuid}"

    Then the response status should be "204"
    And I send a GET request to "/labels/%{label_uuid}"
    And I keep the JSON response as "label_json"
    And I send a GET request to "/roles/%{role_uuid}/labels/"
    Then the JSON response should include:
    """
    %{label_json}
    """

  Scenario: Add a label to a role by name
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "label_uuid"
    And I send a GET request to "/labels/%{label_uuid}"
    And I keep the JSON response as "label_json"
    And I keep the value of the JSON response at "name" as "label_name"

    When I send a PUT request to "/roles/%{role_uuid}/labels/%{label_name}"

    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}/labels/"
    Then the JSON response should include:
       """
       %{label_json}
       """

  Scenario: Delete a label from role by uuid
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "label_uuid"
    And I send a GET request to "/labels/%{label_uuid}"
    And I keep the JSON response as "label_json"
    And I send a PUT request to "/roles/%{role_uuid}/labels/%{label_uuid}"

    When I send a DELETE request to "/roles/%{role_uuid}/labels/%{label_uuid}"

    Then the response status should be "204"
    And I send a GET request to "/roles/%{role_uuid}/labels/"
    Then the JSON response should not include:
       """
       %{label_json}
       """

    Scenario: Delete a label from role by name
      Given I create a label with a random name of 10 characters
      And I keep the value of the JSON response at "uuid" as "label_uuid"
      And I send a GET request to "/labels/%{label_uuid}"
      And I keep the JSON response as "label_json"
      And I keep the value of the JSON response at "name" as "label_name"
      And I send a PUT request to "/roles/%{role_uuid}/labels/%{label_name}"

      When I send a DELETE request to "/roles/%{role_uuid}/labels/%{label_name}"

      Then the response status should be "204"
      And I send a GET request to "/roles/%{role_uuid}/labels/"
      Then the JSON response should not include:
       """
       %{label_json}
       """
