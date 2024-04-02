Feature: Test approving ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Approving ownership of an organizer as admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    And I request ownership for "40fadfd3-c4a6-4936-b1fe-20542ac56610" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    When I approve the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "40fadfd3-c4a6-4936-b1fe-20542ac56610"
    And the JSON response at "requesterId" should be "40fadfd3-c4a6-4936-b1fe-20542ac56610"
    And the JSON response at "state" should be "approved"

  Scenario: Approving ownership of an organizer as creator
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "40fadfd3-c4a6-4936-b1fe-20542ac56610" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I approve the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "40fadfd3-c4a6-4936-b1fe-20542ac56610"
    And the JSON response at "requesterId" should be "40fadfd3-c4a6-4936-b1fe-20542ac56610"
    And the JSON response at "state" should be "approved"

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
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    And I request ownership for "40fadfd3-c4a6-4936-b1fe-20542ac56610" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
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