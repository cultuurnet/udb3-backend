Feature: Test ownership suggestions
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"
    
  Scenario: Suggest the ownership on an organizer of a created place
    And I am authorized as JWT provider user "centraal_beheerder"
    And I create a minimal organizer and save the "id" as "organizerId"
    And I keep the value of the JSON response at "url" as "organizerUrl"
    And I am authorized as JWT provider user "invoerder"
    And I create a place from "places/place-with-organizer.json" and save the "id" as "placeId"
    And I wait for the place with url "places/%{placeId}" to be indexed
    When I send a GET request to "ownerships/suggestions/?itemType=organizer"
    Then the response status should be 200
    And the JSON response should include:
    """
    %{organizerId}
    """

  Scenario: Suggest the ownership of an organizer of a created event
    And I am authorized as JWT provider user "centraal_beheerder"
    And I create a minimal organizer and save the "id" as "organizerId"
    And I keep the value of the JSON response at "url" as "organizerUrl"
    And I create a minimal place and save the "id" as "placeId"
    And I am authorized as JWT provider user "invoerder"
    And I create an event from "events/event-with-organizer.json" and save the "id" as "eventId"
    And I wait for the event with url "events/%{eventId}" to be indexed
    When I send a GET request to "ownerships/suggestions/?itemType=organizer"
    Then the response status should be 200
    And the JSON response should include:
    """
    %{organizerId}
    """

  Scenario: Don't suggest the ownership of an organizer already owned
    And I am authorized as JWT provider user "centraal_beheerder"
    And I create a minimal organizer and save the "id" as "organizerId"
    And I keep the value of the JSON response at "url" as "organizerUrl"
    And I request ownership for "d759fd36-fb28-4fe3-8ec6-b4aaf990371d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider user "invoerder"
    And I create a place from "places/place-with-organizer.json" and save the "id" as "placeId"
    And I wait for the place with url "places/%{placeId}" to be indexed
    When I send a GET request to "ownerships/suggestions/?itemType=organizer"
    Then the response status should be 200
    And the JSON response should not include:
    """
    %{organizerId}
    """
