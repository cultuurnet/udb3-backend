Feature: Test rejecting ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Rejecting ownership of an organizer as admin
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v1 user "invoerder_lgm"
    And I request ownership for "40fadfd3-c4a6-4936-b1fe-20542ac56610" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    When I reject the ownership with ownershipId "%{ownershipId}"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "40fadfd3-c4a6-4936-b1fe-20542ac56610"
    And the JSON response at "requesterId" should be "40fadfd3-c4a6-4936-b1fe-20542ac56610"
    And the JSON response at "state" should be "rejected"