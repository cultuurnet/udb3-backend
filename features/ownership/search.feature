Feature: Test searching ownerships
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Searching ownership of an organizer by item id
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|631748dba64ea78e3983b201" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I request ownership for "auth0|631748dba64ea78e3983b202" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId2"
    And I request ownership for "auth0|631748dba64ea78e3983b203" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId3"
    When I send a GET request to '/ownerships/?itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 3
    And the JSON response at "totalItems" should be 3
    And the JSON response at "member/0/id" should be "%{ownershipId1}"
    And the JSON response at "member/0/ownerId" should be "auth0|631748dba64ea78e3983b201"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/id" should be "%{ownershipId2}"
    And the JSON response at "member/1/ownerId" should be "auth0|631748dba64ea78e3983b202"
    And the JSON response at "member/1/state" should be "requested"
    And the JSON response at "member/2/id" should be "%{ownershipId3}"
    And the JSON response at "member/2/ownerId" should be "auth0|631748dba64ea78e3983b203"
    And the JSON response at "member/2/state" should be "requested"

  Scenario: Searching ownership of an organizer by state
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|631748dba64ea78e3983b201" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I request ownership for "auth0|631748dba64ea78e3983b202" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId2"
    And I request ownership for "auth0|631748dba64ea78e3983b203" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId3"
    When I reject the ownership with ownershipId "%{ownershipId1}"
    When I approve the ownership with ownershipId "%{ownershipId2}"
    When I approve the ownership with ownershipId "%{ownershipId3}"
    When I send a GET request to '/ownerships/?state=approved&itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 2
    And the JSON response at "totalItems" should be 2
    And the JSON response at "member/0/id" should be "%{ownershipId2}"
    And the JSON response at "member/0/ownerId" should be "auth0|631748dba64ea78e3983b202"
    And the JSON response at "member/0/state" should be "approved"
    And the JSON response at "member/1/id" should be "%{ownershipId3}"
    And the JSON response at "member/1/ownerId" should be "auth0|631748dba64ea78e3983b203"
    And the JSON response at "member/1/state" should be "approved"

  Scenario: Searching ownership of an organizer by ownerId
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|631748dba64ea78e3983b201" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I create a random name of 10 characters and keep it as "ownerId"
    And I request ownership for "%{ownerId}" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I create a minimal organizer and save the "id" as "anotherOrganizerId"
    And I request ownership for "%{ownerId}" on the organizer with organizerId "%{anotherOrganizerId}" and save the "id" as "ownershipId2"
    When I send a GET request to '/ownerships/?ownerId=%{ownerId}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 2
    And the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    "%{ownershipId1}"
    """
    And the JSON response should include:
    """
    "%{ownershipId2}"
    """
    And the JSON response at "member/0/ownerId" should be "%{ownerId}"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/ownerId" should be "%{ownerId}"
    And the JSON response at "member/1/state" should be "requested"

  Scenario: Searching ownership of an organizer by state and with start and limit
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|631748dba64ea78e3983b201" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I request ownership for "auth0|631748dba64ea78e3983b202" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId2"
    And I request ownership for "auth0|631748dba64ea78e3983b203" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId3"
    And I request ownership for "auth0|631748dba64ea78e3983b204" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId4"
    And I request ownership for "auth0|631748dba64ea78e3983b205" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId5"
    When I send a GET request to '/ownerships/?itemId=%{organizerId}&limit=2&start=2'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 2
    And the JSON response at "totalItems" should be 5
    And the JSON response at "member/0/id" should be "%{ownershipId3}"
    And the JSON response at "member/0/ownerId" should be "auth0|631748dba64ea78e3983b203"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/id" should be "%{ownershipId4}"
    And the JSON response at "member/1/ownerId" should be "auth0|631748dba64ea78e3983b204"
    And the JSON response at "member/1/state" should be "requested"

  Scenario: No ownerships found
    Given I create a minimal organizer and save the "id" as "organizerId"
    When I send a GET request to '/ownerships/?itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response should be:
    """
    {
      "@context":"http://www.w3.org/ns/hydra/context.jsonld",
      "@type":"PagedCollection",
      "itemsPerPage":0,
      "totalItems":0,
      "member":[]
    }
    """
