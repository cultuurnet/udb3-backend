Feature: Test the UDB3 roles API permissions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario: As an anonymous user I cannot get a user's roles
    Given I am not authorized
    When I send a GET request to "/users/269a8217-57a5-46b1-90e3-e9d2f91d45e5/roles"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get a user's roles
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/users/269a8217-57a5-46b1-90e3-e9d2f91d45e5/roles"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot search users by their email address
    Given I am not authorized
    When I send a GET request to "/users/emails/mock@test.be"
    Then the response status should be "401"

  Scenario: As a regular user I cannot search users by their email address
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/users/emails/mock@test.be"
    Then the response status should be "403"

  Scenario: As a god user I can search users by their email address
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/users/emails/dev+validator_diest@publiq.be"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "email": "dev+validator_diest@publiq.be",
      "username": "validatorDiest",
      "uuid": "26808daa-e194-4ca8-ac93-2b69e3c722bd"
    }
    """

  Scenario: As an anonymous user I cannot get my user details
    Given I am not authorized
    When I send a GET request to "/user"
    Then the response status should be "401"

  Scenario: As a regular user I can get my user details
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/user"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "uuid":"269a8217-57a5-46b1-90e3-e9d2f91d45e5",
      "email":"stan.vertessen+LGM@cultuurnet.be",
      "username":"Testuser-LGM",
      "id":"269a8217-57a5-46b1-90e3-e9d2f91d45e5",
      "nick":"Testuser-LGM"
    }
    """

  Scenario: As an anonymous user I cannot get my roles
    Given I am not authorized
    When I send a GET request to "/user/roles"
    Then the response status should be "401"

  Scenario: As a regular user I can get my roles
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/user/roles"
    Then the response status should be "200"
    And the JSON response should be:
    """
    []
    """

  Scenario: As a user with at least one role I can get my roles
    Given I am authorized as JWT provider v2 user "validator_diest"
    When I send a GET request to "/user/roles"
    Then the response status should be "200"
    And the JSON response at "0/name" should be "Diest validatoren"

  Scenario: As a god user I can get my roles
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/user/roles"
    Then the response status should be "200"
    And the JSON response should be:
    """
    []
    """

  Scenario: As an anonymous user I cannot get my permissions
    Given I am not authorized
    When I send a GET request to "/user/permissions"
    Then the response status should be "401"

  Scenario: As a regular user I can get my permissions
    Given I am authorized as JWT provider v2 user "invoerder_lgm"
    When I send a GET request to "/user/permissions"
    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "MEDIA_UPLOADEN"
    ]
    """

  Scenario: As a user with at least one role I can get my permissions
    Given I am authorized as JWT provider v2 user "validator_diest"
    When I send a GET request to "/user/permissions"
    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "AANBOD_BEWERKEN",
      "AANBOD_MODEREREN",
      "AANBOD_VERWIJDEREN",
      "MEDIA_UPLOADEN"
    ]
    """

  Scenario: As a god user I can get my permissions
    Given I am authorized as JWT provider v2 user "centraal_beheerder"
    When I send a GET request to "/user/permissions"
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
      "FILMS_AANMAKEN",
      "MEDIA_UPLOADEN"
    ]
    """
