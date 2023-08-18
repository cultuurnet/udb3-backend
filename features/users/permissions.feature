Feature: Test the UDB3 roles API permissions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario: As an anonymous user I cannot get a user's roles
    Given I am not authorized
    When I send a GET request to "/users/40fadfd3-c4a6-4936-b1fe-20542ac56610/roles"
    Then the response status should be "401"

  Scenario: As a regular user I cannot get a user's roles
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/users/40fadfd3-c4a6-4936-b1fe-20542ac56610/roles"
    Then the response status should be "403"

  Scenario: As an anonymous user I cannot search users by their email address
    Given I am not authorized
    When I send a GET request to "/users/emails/mock@test.be"
    Then the response status should be "401"

  Scenario: As a regular user I cannot search users by their email address
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/users/emails/mock@test.be"
    Then the response status should be "403"

  Scenario: As a god user I can search users by their email address
    Given I am authorized as JWT provider v1 user "centraal_beheerder"
    When I send a GET request to "/users/emails/stan.vertessen+validatorDiest@cultuurnet.be"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "email": "stan.vertessen+validatordiest@cultuurnet.be",
      "username": "validatorDiest",
      "uuid": "50cc85fa-f278-44c5-a16b-b9db50ee93f6"
    }
    """

  Scenario: As an anonymous user I cannot get my user details
    Given I am not authorized
    When I send a GET request to "/user"
    Then the response status should be "401"

  Scenario: As a regular user I can get my user details
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/user"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "uuid":"40fadfd3-c4a6-4936-b1fe-20542ac56610",
      "email":"stan.vertessen+LGM@cultuurnet.be",
      "username":"Testuser-LGM",
      "id":"40fadfd3-c4a6-4936-b1fe-20542ac56610",
      "nick":"Testuser-LGM"
    }
    """

  Scenario: As an anonymous user I cannot get my roles
    Given I am not authorized
    When I send a GET request to "/user/roles"
    Then the response status should be "401"

  Scenario: As a regular user I can get my roles
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/user/roles"
    Then the response status should be "200"
    And the JSON response should be:
    """
    []
    """

  Scenario: As a user with at least one role I can get my roles
    Given I am authorized as JWT provider v1 user "validator_diest"
    When I send a GET request to "/user/roles"
    Then the response status should be "200"
    And the JSON response at "0/name" should be "Diest validatoren"

  Scenario: As a god user I can get my roles
    Given I am authorized as JWT provider v1 user "centraal_beheerder"
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
    Given I am authorized as JWT provider v1 user "invoerder_lgm"
    When I send a GET request to "/user/permissions"
    Then the response status should be "200"
    And the JSON response should be:
    """
    [
      "MEDIA_UPLOADEN"
    ]
    """

  Scenario: As a user with at least one role I can get my permissions
    Given I am authorized as JWT provider v1 user "validator_diest"
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
    Given I am authorized as JWT provider v1 user "centraal_beheerder"
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
