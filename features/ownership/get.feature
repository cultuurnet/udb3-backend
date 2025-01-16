Feature: Test getting a single ownership by ID
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Get the ownership as an admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I send a GET request to '/ownerships/%{ownershipId}'
    Then the response status should be 200
    And the JSON response at id should be "%{ownershipId}"
    And the JSON response at ownerId should be "auth0|64089494e980aedd96740212"
    And the JSON response at itemId should be "%{organizerId}"
    And the JSON response at state should be "requested"
    And the JSON response at itemType should be "organizer"
    And the JSON response at requesterId should be "7a583ed3-cbc1-481d-93b1-d80fff0174dd"
    And the JSON response at ownerEmail should be "dev+e2etest@publiq.be"

  Scenario: Get the ownership as owner
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I am authorized as JWT provider v2 user "dev_e2e_test"
    And I send a GET request to '/ownerships/%{ownershipId}'
    Then the response status should be 200
    And the JSON response at id should be "%{ownershipId}"
    And the JSON response at ownerId should be "auth0|64089494e980aedd96740212"
    And the JSON response at itemId should be "%{organizerId}"
    And the JSON response at state should be "requested"
    And the JSON response at itemType should be "organizer"
    And the JSON response at requesterId should be "7a583ed3-cbc1-481d-93b1-d80fff0174dd"
    And the JSON response at ownerEmail should be "dev+e2etest@publiq.be"

  Scenario: Not allowed to get the ownership as an unrelated user
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I am authorized as JWT provider v2 user "invoerder"
    And I send a GET request to '/ownerships/%{ownershipId}'
    Then the response status should be 403
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/auth/forbidden",
      "title": "Forbidden",
      "status": 403,
      "detail": "You are not allowed to get this ownership"
    }
    """

  Scenario: Get an unexisting ownership
    When I send a GET request to '/ownerships/a0a9f9c0-84b5-471b-869e-c2c692217cc0'
    Then the response status should be 404
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/url/not-found",
      "title": "Not Found",
      "status": 404,
      "detail": "The Ownership with id \"a0a9f9c0-84b5-471b-869e-c2c692217cc0\" was not found."
    }
    """
