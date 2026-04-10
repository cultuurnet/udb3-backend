@sapi3
Feature: Test the Search API v3 via POST requests

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I send and accept "text/plain"
    And I am using the Search API v3 base URL

  Scenario: Only content-type text/plain is accepted
    When I send and accept "application/json"
    And I send a POST request to "/offers"
    Then the response status should be "415"
    And the JSON response at "detail" should be 'POST requests require Content-Type text/plain.'

  Scenario: I can send parameters via a POST body
    When I set the plain text request payload to:
    """
    locationId=%{placeId}
    """
    And I send a POST request to "/offers"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{eventId}
    """

  Scenario: I can send advanced queries via a POST body
    When I set the plain text request payload to:
    """
    q=id:(%{eventId} OR %{placeId})
    """
    And I send a POST request to "/offers"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{placeId}
    """
    And the JSON response should include:
    """
    %{eventId}
    """
    When I send a POST request to "/places"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{placeId}
    """
    When I send a POST request to "/events"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{eventId}
    """

  Scenario: Endpoint with trailing slashes should work
    When I set the plain text request payload to:
    """
    q=id:(%{eventId} OR %{placeId})
    """
    And I send a POST request to "/offers/"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{placeId}
    """
    And the JSON response should include:
    """
    %{eventId}
    """
    When I send a POST request to "/places/"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{placeId}
    """
    When I send a POST request to "/events/"
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{eventId}
    """

  Scenario: When using a POST Body, url parameters are ignored.
    When I set the plain text request payload to:
    """
    q=id:%{eventId}
    """
    And I send a POST request to "/offers?q=id:%{placeId}"
    Then the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{eventId}
    """
    And the JSON response should not include:
    """
    %{placeId}
    """

  Scenario: Use a combination of parameters & advanced queries
    When I set the plain text request payload to:
    """
    workflowStatus=APPROVED&id=%{eventId}
    """
    And I send a POST request to "/offers"
    Then the JSON response at "totalItems" should be 0
    And I set the plain text request payload to:
    """
    workflowStatus=READY_FOR_VALIDATION&q=id:%{eventId}
    """
    And I send a POST request to "/offers"
    Then the JSON response at "totalItems" should be 1
    And the JSON response should include:
    """
    %{eventId}
    """
