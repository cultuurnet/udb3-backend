Feature: Test searching ownerships
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Searching ownership of an organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|631748dba64ea78e3983b201" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I request ownership for "auth0|631748dba64ea78e3983b202" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I request ownership for "auth0|631748dba64ea78e3983b203" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I send a GET request to '/ownerships/?itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response at "0/ownerId" should be "auth0|631748dba64ea78e3983b201"
    And the JSON response at "0/state" should be "requested"
    And the JSON response at "1/ownerId" should be "auth0|631748dba64ea78e3983b202"
    And the JSON response at "1/state" should be "requested"
    And the JSON response at "2/ownerId" should be "auth0|631748dba64ea78e3983b203"
    And the JSON response at "2/state" should be "requested"

  Scenario: No ownerships found
    Given I create a minimal organizer and save the "id" as "organizerId"
    When I send a GET request to '/ownerships/?itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response should be:
    """
    []
    """
