Feature: Test approving ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  @mails
  Scenario: Approving ownership of an organizer as admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    When I approve the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "approved"
    And the JSON response at "approvedById" should be "7a583ed3-cbc1-481d-93b1-d80fff0174dd"
    And I wait till there are 2 mails in the mailbox
    And an "ownership-approved" mail has been sent from "no-reply@uitdatabank.be" to "dev+e2etest@publiq.be" with subject "Je bent nu beheerder van organisatie %{name}!"

  @mails
  Scenario: Approving ownership of an organizer as creator
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I approve the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "state" should be "approved"
    And the JSON response at "approvedById" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "approvedByEmail" should be "dev+e2etest@publiq.be"
    And I wait till there are 2 mails in the mailbox
    And an "ownership-approved" mail has been sent from "no-reply@uitdatabank.be" to "dev+e2etest@publiq.be" with subject "Je bent nu beheerder van organisatie %{name}"

  Scenario: Approving a non-existing ownership
    When I send a POST request to '/ownerships/21a5c45b-78f8-4034-ab4d-5528847860b3/approve'
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

  Scenario: Approving an organizer as non-authorized user
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder_ownerships"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I send a POST request to '/ownerships/%{ownershipId}/approve'
    Then the response status should be 403
    And the JSON response should be:
      """
      {
        "type": "https://api.publiq.be/probs/auth/forbidden",
        "title": "Forbidden",
        "status": 403,
        "detail": "You are not allowed to approve this ownership"
      }
      """
