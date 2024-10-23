Feature: Test requesting ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Requesting ownership of an organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "7a583ed3-cbc1-481d-93b1-d80fff0174dd"
    And the JSON response at "state" should be "requested"

  Scenario: Requesting ownership of an organizer via email
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for email "dev+e2etest@publiq.be" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "7a583ed3-cbc1-481d-93b1-d80fff0174dd"
    And the JSON response at "state" should be "requested"


  Scenario: Requesting the same ownership of an organizer is not allowed
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I set the JSON request payload to:
      """
      {
        "itemId": "%{organizerId}",
        "itemType": "organizer",
        "ownerId": "auth0|64089494e980aedd96740212"
      }
      """
    When I send a POST request to '/ownerships'
    Then the response status should be 409
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/uitdatabank/ownership-already-exists",
     "title": "Ownership already exists",
     "status": 409,
     "detail": "An ownership request for this item and owner already exists with id %{ownershipId}"
    }
    """

  Scenario: Requesting the ownership of a non-existing organizer is not allowed
    When I set the JSON request payload to:
      """
      {
        "itemId": "b192b05f-9294-4c07-a3f9-6a15e267d746",
        "itemType": "organizer",
        "ownerId": "auth0|64089494e980aedd96740212"
      }
      """
    When I send a POST request to '/ownerships'
    Then the response status should be 404
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The Organizer with id \"b192b05f-9294-4c07-a3f9-6a15e267d746\" was not found."
    }
    """
