Feature: Test requesting ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Requesting ownership of an organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership of the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|631748dba64ea78e3983b207"