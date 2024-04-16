Feature: Test permissions based on ownership
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Approving the ownership of an organizer gives permission on the organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/organizers/%{organizerId}/name/nl"
    And the response status should be "403"
    And I request ownership for "31adb53a-a4c6-47a4-96ad-d1da713e138d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I am authorized as JWT provider v1 user "centraal_beheerder"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/organizers/%{organizerId}/name/nl"
    Then the response status should be "204"

  Scenario: Deleting the ownership of an organizer removes permission on the organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I request ownership for "31adb53a-a4c6-47a4-96ad-d1da713e138d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/organizers/%{organizerId}/name/nl"
    And the response status should be "204"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I delete the ownership with ownershipId "%{ownershipId}"
    When I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/organizers/%{organizerId}/name/nl"
    Then the response status should be "403"

  Scenario: Approving the ownership of an organizer gives permission on the event associated with the organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I create a minimal place and save the "id" as "placeId"
    And I create an event from "events/event-with-organizer.json" and save the "id" as "eventId"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/events/%{eventId}/name/nl"
    And the response status should be "403"
    And I request ownership for "31adb53a-a4c6-47a4-96ad-d1da713e138d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I am authorized as JWT provider v1 user "centraal_beheerder"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/events/%{eventId}/name/nl"
    Then the response status should be "204"

  Scenario: Deleting the ownership of an organizer removes permission on the event associated with the organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I create a minimal place and save the "id" as "placeId"
    And I create an event from "events/event-with-organizer.json" and save the "id" as "eventId"
    And I request ownership for "31adb53a-a4c6-47a4-96ad-d1da713e138d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/events/%{eventId}/name/nl"
    And the response status should be "204"
    When I am authorized as JWT provider v1 user "centraal_beheerder"
    And I delete the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/events/%{eventId}/name/nl"
    Then the response status should be "403"

  Scenario: Approving the ownership of an organizer doesn't give permission on the place associated with the organizer
    Given I create a minimal organizer and save the "id" as "organizerId"
    And I keep the value of the JSON response at "url" as "organizerUrl"
    And I create a place from "places/place-with-organizer.json" and save the "id" as "placeId"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/places/%{placeId}/name/nl"
    And the response status should be "403"
    And I request ownership for "31adb53a-a4c6-47a4-96ad-d1da713e138d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
    When I am authorized as JWT provider v1 user "centraal_beheerder"
    And I approve the ownership with ownershipId "%{ownershipId}"
    And I am authorized as JWT provider v1 user "invoerder_ownership"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/places/%{placeId}/name/nl"
    Then the response status should be "403"