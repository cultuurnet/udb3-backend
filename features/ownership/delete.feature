Feature: Test deleting ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Deleting ownership of an organizer as admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    When I delete the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "deleted"

  Scenario: Deleting an approved ownership of an organizer as admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I approve the ownership with ownershipId "%{ownershipId}"
    When I delete the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "deleted"

  Scenario: Deleting a rejected ownership of an organizer as admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I reject the ownership with ownershipId "%{ownershipId}"
    When I delete the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "deleted"

  Scenario: Deleting ownership of an organizer as creator
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I delete the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "deleted"

  Scenario: Deleting an approved ownership of an organizer as creator
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I approve the ownership with ownershipId "%{ownershipId}"
    When I delete the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "deleted"

  Scenario: Deleting a rejected ownership of an organizer as creator
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I reject the ownership with ownershipId "%{ownershipId}"
    When I delete the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "deleted"

  Scenario: Deleting a non-existing ownership
    When I send a DELETE request to '/ownerships/21a5c45b-78f8-4034-ab4d-5528847860b3'
    Then the response status should be 404
    And the JSON response should be:
      """
      {
       "type": "https://api.publiq.be/probs/url/not-found",
       "title": "Not Found",
       "status": 404,
       "detail": "The Ownership with id \"21a5c45b-78f8-4034-ab4d-5528847860b3\" was not found."
      }
      """

  Scenario: Deleting an organizer as non-authorized user
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I send a DELETE request to '/ownerships/%{ownershipId}'
    Then the response status should be 403
    And the JSON response should be:
      """
      {
        "type": "https://api.publiq.be/probs/auth/forbidden",
        "title": "Forbidden",
        "status": 403,
        "detail": "You are not allowed to delete this ownership"
      }
      """
