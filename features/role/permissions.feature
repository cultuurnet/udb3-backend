Feature: Test the UDB3 roles API permissions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario: As an anonymous user I cannot get a list of all permissions
    Given I am not authorized
    When I send a GET request to "/permissions"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get a list of all permissions
    Given I am authorized as JWT provider user "invoerder_lgm"
    When I send a GET request to "/permissions"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot create a role
    Given I am not authorized
    When I set the JSON request payload to:
    """
    { "name": "test role" }
    """
    When I send a POST request to "/roles"
    Then the response status should be "401"

  Scenario: As a regular user I cannot create a role
    Given I am authorized as JWT provider user "invoerder_lgm"
    When I set the JSON request payload to:
    """
    { "name": "test role" }
    """
    When I send a POST request to "/roles"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot update a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I set the JSON request payload to:
    """
    {
      "name": "updated name"
    }
    """
    And I send "application/ld+json;domain-model=RenameRole"
    And I send a PATCH request to "/roles/%{roleId}"
    Then the response status should be "401"

  Scenario: As a regular user I cannot update a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I set the JSON request payload to:
    """
    {
      "name": "updated name"
    }
    """
    And I send "application/ld+json;domain-model=RenameRole"
    And I send a PATCH request to "/roles/%{roleId}"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot search roles
    Given I am not authorized
    When I send a GET request to "/roles"
    Then the response status should be "401"

  Scenario: As a regular user I cannot search roles
    Given I am authorized as JWT provider user "invoerder_lgm"
    When I send a GET request to "/roles"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot set a constraint on a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I set the JSON request payload to:
    """
    {
      "query": "*"
    }
    """
    And I send a POST request to "/roles/%{roleId}/constraints"
    Then the response status should be "401"

  Scenario: As a regular user I cannot set a constraint on a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I set the JSON request payload to:
    """
    {
      "query": "*"
    }
    """
    And I send a POST request to "/roles/%{roleId}/constraints"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot update a constraint on a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I set the JSON request payload to:
    """
    {
      "query": "*"
    }
    """
    And I send a PUT request to "/roles/%{roleId}/constraints"
    Then the response status should be "401"

  Scenario: As a regular user I cannot update a constraint on a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I set the JSON request payload to:
    """
    {
      "query": "*"
    }
    """
    And I send a PUT request to "/roles/%{roleId}/constraints"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot delete a constraint from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a DELETE request to "/roles/%{roleId}/constraints"
    Then the response status should be "401"

  Scenario: As a regular user I cannot delete a constraint from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a DELETE request to "/roles/%{roleId}/constraints"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot delete a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a DELETE request to "/roles/%{roleId}"
    Then the response status should be "401"

  Scenario: As a regular user I cannot delete a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a DELETE request to "/roles/%{roleId}"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot get a role's users
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a GET request to "/roles/%{roleId}/users"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get a role's users
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a GET request to "/roles/%{roleId}/users"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot add a user to a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a PUT request to "/roles/%{roleId}/users/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get add a user to a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a PUT request to "/roles/%{roleId}/users/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot delete a user from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a DELETE request to "/roles/%{roleId}/users/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get delete a user from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a DELETE request to "/roles/%{roleId}/users/269a8217-57a5-46b1-90e3-e9d2f91d45e5"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot add a permission to a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a PUT request to "/roles/%{roleId}/permissions/AANBOD_BEWERKEN"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get add a permission to a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a PUT request to "/roles/%{roleId}/permissions/AANBOD_BEWERKEN"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot delete a permission from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a DELETE request to "/roles/%{roleId}/permissions/AANBOD_BEWERKEN"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get delete a permission from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a DELETE request to "/roles/%{roleId}/permissions/AANBOD_BEWERKEN"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot get a role's labels
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a GET request to "/roles/%{roleId}/labels"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get a role's labels
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a GET request to "/roles/%{roleId}/labels"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot add a label to a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "labelId"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am not authorized
    When I send a PUT request to "/roles/%{roleId}/labels/%{labelId}"
    Then the response status should be "401"

  Scenario: As a regular user I cannot add a label to a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "labelId"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a PUT request to "/roles/%{roleId}/labels/%{labelId}"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot remove a label from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "labelId"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/labels/%{labelId}"
    And I am not authorized
    When I send a DELETE request to "/roles/%{roleId}/labels/%{labelId}"
    Then the response status should be "401"

  Scenario: As a regular user I cannot remove a label from a role
    Given I am authorized as JWT provider user "centraal_beheerder"
    And I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "labelId"
    And I create a role with a random name of 10 characters
    And I keep the value of the JSON response at "roleId" as "roleId"
    And I send a PUT request to "/roles/%{roleId}/labels/%{labelId}"
    And I am authorized as JWT provider user "invoerder_lgm"
    When I send a DELETE request to "/roles/%{roleId}/labels/%{labelId}"
    Then the response status should be "403"

  Scenario: Get a list of all the available permissions
    When I am authorized as JWT provider user "centraal_beheerder"
    And I send a GET request to "/permissions"

    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "AANBOD_BEWERKEN",
      "AANBOD_MODEREREN",
      "AANBOD_VERWIJDEREN",
      "AANBOD_HISTORIEK",
      "ORGANISATIES_BEHEREN",
      "ORGANISATIES_BEWERKEN",
      "GEBRUIKERS_BEHEREN",
      "LABELS_BEHEREN",
      "VOORZIENINGEN_BEWERKEN",
      "PRODUCTIES_AANMAKEN",
      "FILMS_AANMAKEN"
    ]
    """
