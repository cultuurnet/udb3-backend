Feature: Test searching ownerships
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Searching ownership of an organizer by item id
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "02566c96-8fd3-4b7e-aa35-cbebe6663b2d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I request ownership for "79dd2821-3b89-4dbb-9143-920ff2edfa34" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId2"
    And I request ownership for "92650bd8-037f-4722-a40e-7e0a0bf39a8e" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId3"
    When I send a GET request to '/ownerships/?itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 3
    And the JSON response at "totalItems" should be 3
    And the JSON response at "member/0/id" should be "%{ownershipId1}"
    And the JSON response at "member/0/ownerId" should be "02566c96-8fd3-4b7e-aa35-cbebe6663b2d"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/id" should be "%{ownershipId2}"
    And the JSON response at "member/1/ownerId" should be "79dd2821-3b89-4dbb-9143-920ff2edfa34"
    And the JSON response at "member/1/state" should be "requested"
    And the JSON response at "member/2/id" should be "%{ownershipId3}"
    And the JSON response at "member/2/ownerId" should be "92650bd8-037f-4722-a40e-7e0a0bf39a8e"
    And the JSON response at "member/2/state" should be "requested"

  Scenario: Searching ownership of an organizer by state
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "02566c96-8fd3-4b7e-aa35-cbebe6663b2d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I request ownership for "79dd2821-3b89-4dbb-9143-920ff2edfa34" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId2"
    And I request ownership for "92650bd8-037f-4722-a40e-7e0a0bf39a8e" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId3"
    When I reject the ownership with ownershipId "%{ownershipId1}"
    When I approve the ownership with ownershipId "%{ownershipId2}"
    When I approve the ownership with ownershipId "%{ownershipId3}"
    When I send a GET request to '/ownerships/?state=approved&itemId=%{organizerId}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 2
    And the JSON response at "totalItems" should be 2
    And the JSON response at "member/0/id" should be "%{ownershipId2}"
    And the JSON response at "member/0/ownerId" should be "79dd2821-3b89-4dbb-9143-920ff2edfa34"
    And the JSON response at "member/0/state" should be "approved"
    And the JSON response at "member/1/id" should be "%{ownershipId3}"
    And the JSON response at "member/1/ownerId" should be "92650bd8-037f-4722-a40e-7e0a0bf39a8e"
    And the JSON response at "member/1/state" should be "approved"

  Scenario: Searching ownership of an organizer by ownerId
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "02566c96-8fd3-4b7e-aa35-cbebe6663b2d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I request ownership for "67aebd9b-3033-459c-818e-ca684b3a27b3" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I create a minimal organizer and save the "id" as "anotherOrganizerId"
    And I request ownership for "67aebd9b-3033-459c-818e-ca684b3a27b3" on the organizer with organizerId "%{anotherOrganizerId}" and save the "id" as "ownershipId2"
    When I send a GET request to '/ownerships/?ownerId=67aebd9b-3033-459c-818e-ca684b3a27b3&state=requested'
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
    And the JSON response at "member/0/ownerId" should be "67aebd9b-3033-459c-818e-ca684b3a27b3"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/ownerId" should be "67aebd9b-3033-459c-818e-ca684b3a27b3"
    And the JSON response at "member/1/state" should be "requested"
    And I delete the ownership with ownershipId "%{ownershipId1}"
    And I delete the ownership with ownershipId "%{ownershipId2}"

  Scenario: Searching ownership of an organizer by state and with start and limit
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "02566c96-8fd3-4b7e-aa35-cbebe6663b2d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId1"
    And I request ownership for "79dd2821-3b89-4dbb-9143-920ff2edfa34" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId2"
    And I request ownership for "92650bd8-037f-4722-a40e-7e0a0bf39a8e" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId3"
    And I request ownership for "c9f2a19f-3dd7-401c-ad4d-73db7a9d1748" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId4"
    And I request ownership for "edf305f8-69b6-4553-914e-9ecedcba418e" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId5"
    And I approve the ownership with ownershipId "%{ownershipId1}"
    And I approve the ownership with ownershipId "%{ownershipId2}"
    When I send a GET request to '/ownerships/?state=requested&itemId=%{organizerId}&imit=2&start=1'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 2
    And the JSON response at "totalItems" should be 3
    And the JSON response at "member/0/id" should be "%{ownershipId4}"
    And the JSON response at "member/0/ownerId" should be "c9f2a19f-3dd7-401c-ad4d-73db7a9d1748"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/id" should be "%{ownershipId5}"
    And the JSON response at "member/1/ownerId" should be "edf305f8-69b6-4553-914e-9ecedcba418e"
    And the JSON response at "member/1/state" should be "requested"

  Scenario: Searching ownership of an organizer takes into permission organisaties bewerken
    Given I create a minimal organizer and save the "id" as "organizerId1"
    And I request ownership for "02566c96-8fd3-4b7e-aa35-cbebe6663b2d" on the organizer with organizerId "%{organizerId1}" and save the "id" as "ownershipId1"
    Given I create a minimal organizer and save the "id" as "organizerId2"
    And I request ownership for "79dd2821-3b89-4dbb-9143-920ff2edfa34" on the organizer with organizerId "%{organizerId2}" and save the "id" as "ownershipId2"
    And I request ownership for "d759fd36-fb28-4fe3-8ec6-b4aaf990371d" on the organizer with organizerId "%{organizerId2}" and save the "id" as "ownershipId3"
    And I approve the ownership with ownershipId "%{ownershipId3}"
    When I am authorized as JWT provider user "invoerder"
    When I send a GET request to '/ownerships/?itemId=%{organizerId2}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 2
    And the JSON response at "totalItems" should be 2
    And the JSON response at "member/0/id" should be "%{ownershipId2}"
    And the JSON response at "member/0/ownerId" should be "79dd2821-3b89-4dbb-9143-920ff2edfa34"
    And the JSON response at "member/0/state" should be "requested"
    And the JSON response at "member/1/id" should be "%{ownershipId3}"
    And the JSON response at "member/1/ownerId" should be "d759fd36-fb28-4fe3-8ec6-b4aaf990371d"
    And the JSON response at "member/1/state" should be "approved"

  Scenario: Searching ownership of an organizer takes into account current owner
    Given I create a minimal organizer and save the "id" as "organizerId1"
    And I request ownership for "02566c96-8fd3-4b7e-aa35-cbebe6663b2d" on the organizer with organizerId "%{organizerId1}" and save the "id" as "ownershipId1"
    Given I create a minimal organizer and save the "id" as "organizerId2"
    And I request ownership for "79dd2821-3b89-4dbb-9143-920ff2edfa34" on the organizer with organizerId "%{organizerId2}" and save the "id" as "ownershipId2"
    And I request ownership for "d759fd36-fb28-4fe3-8ec6-b4aaf990371d" on the organizer with organizerId "%{organizerId2}" and save the "id" as "ownershipId3"
    When I am authorized as JWT provider user "invoerder"
    When I send a GET request to '/ownerships/?itemId=%{organizerId2}'
    Then the response status should be 200
    And the JSON response at "itemsPerPage" should be 1
    And the JSON response at "totalItems" should be 1
    And the JSON response at "member/0/id" should be "%{ownershipId3}"
    And the JSON response at "member/0/ownerId" should be "d759fd36-fb28-4fe3-8ec6-b4aaf990371d"
    And the JSON response at "member/0/state" should be "requested"

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
