Feature: Test requesting ownership

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "invoerder"
    And I send and accept "application/json"

  @mails
  Scenario: Requesting ownership of an organizer as creator of the organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "d759fd36-fb28-4fe3-8ec6-b4aaf990371d"
    And the JSON response at "requesterEmail" should be "dev+udbtestinvoerder@publiq.be"
    And the JSON response at "state" should be "requested"
    And I wait till there are 1 mails in the mailbox
    And an "ownership-request" mail has been sent from "no-reply@uitdatabank.be" to "dev+udbtestinvoerder@publiq.be" with subject "Beheeraanvraag voor organisatie"

  Scenario: Requesting ownership of an organizer for yourself
    Given I am authorized as JWT provider v1 user "centraal_beheerder"
    And I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder"
    And I request ownership for "d759fd36-fb28-4fe3-8ec6-b4aaf990371d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"

  Scenario: Requesting ownership of an organizer for someone else is not allowed if you are not an owner
    Given I am authorized as JWT provider v1 user "centraal_beheerder"
    And I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v2 user "invoerder"
    And I set the JSON request payload to:
    """
    {
      "itemId": "%{organizerId}",
      "itemType": "organizer",
      "ownerId": "auth0|64089494e980aedd96740212"
    }
    """
    When I send a POST request to '/ownerships'
    Then the response status should be 403
    And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/auth/forbidden",
      "title": "Forbidden",
      "status": 403,
      "detail": "You are not allowed to request ownership for this item"
    }
    """

  @mails
  Scenario: Requesting ownership of an organizer via email
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for email "dev+e2etest@publiq.be" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"
    And the JSON response at "itemId" should be "%{organizerId}"
    And the JSON response at "itemType" should be "organizer"
    And the JSON response at "ownerId" should be "auth0|64089494e980aedd96740212"
    And the JSON response at "ownerEmail" should be "dev+e2etest@publiq.be"
    And the JSON response at "requesterId" should be "d759fd36-fb28-4fe3-8ec6-b4aaf990371d"
    And the JSON response at "state" should be "requested"
    And I wait till there are 1 mails in the mailbox
    And an "ownership-request" mail has been sent from "no-reply@uitdatabank.be" to "dev+udbtestinvoerder@publiq.be" with subject "Beheeraanvraag voor organisatie"
    
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

  Scenario: Requesting the same ownership of an organizer is not allowed when already approved
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I approve the ownership with ownershipId "%{ownershipId}"
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

  Scenario: Requesting the same ownership of an organizer is allowed when the previous request was rejected
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I reject the ownership with ownershipId "%{ownershipId}"
    When I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"

  Scenario: Requesting the same ownership of an organizer is allowed when the previous request was deleted
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I delete the ownership with ownershipId "%{ownershipId}"
    When I request ownership for "auth0|64089494e980aedd96740212" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I get the ownership with ownershipId "%{ownershipId}"
    Then the JSON response at "id" should be "%{ownershipId}"

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
