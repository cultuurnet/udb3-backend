Feature: Test getting creator of organizer
    Background:
        Given I am using the UDB3 base URL
        And I am using an UiTID v1 API key of consumer "uitdatabank"
        And I am authorized as JWT provider user "invoerder_ownerships"
        And I send and accept "application/json"

    Scenario: Getting the creator of an organizer as creator
        And I create a minimal organizer and save the "id" as "organizerId"
        When I send a GET request to "/organizers/%{organizerId}/creator"
        Then the response status should be 200
        And the JSON response at "userId" should be "d4e7ed87-50ac-4c35-8193-f899ea0af66b"
        And the JSON response at "email" should be "dev+e2etest@publiq.be"

    Scenario: Getting the creator of an organizer as a different owner
        And I create a minimal organizer and save the "id" as "organizerId"
        And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed
        And I request ownership for "d759fd36-fb28-4fe3-8ec6-b4aaf990371d" on the organizer with organizerId "%{organizerId}" and save the "id" as "ownershipId"
        And I approve the ownership with ownershipId "%{ownershipId}"

        When I am authorized as JWT provider user "invoerder"
        And I send a GET request to "/organizers/%{organizerId}/creator"
        Then the response status should be 200
        And the JSON response at "userId" should be "d4e7ed87-50ac-4c35-8193-f899ea0af66b"
        And the JSON response at "email" should be "dev+e2etest@publiq.be"

    Scenario: Getting the creator of an organizer that you're not an owner of is not allowed
        And I create a minimal organizer and save the "id" as "organizerId"
        And I wait for the organizer with url "/organizers/%{organizerId}" to be indexed

        When I am authorized as JWT provider user "invoerder"
        And I send a GET request to "/organizers/%{organizerId}/creator"
        Then the JSON response should be:
        """
        {
         "type": "https://api.publiq.be/probs/auth/forbidden",
         "title": "Forbidden",
         "status": 403,
         "detail": "You are not allowed to get creator for this item"
        }
        """

