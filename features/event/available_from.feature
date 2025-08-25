@api @events
Feature: Test the available from endpoint on events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"

  Scenario: Update available from on draft event
    When I set the JSON request payload to:
        """
        { "availableFrom": "2030-11-15T11:22:33+00:00" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/available-from"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "availableFrom" should be "2030-11-15T11:22:33+00:00"

  Scenario: Update available from on published event
    And I send "application/ld+json;domain-model=Publish"
    When I send a PATCH request to "/events/%{uuid_testevent}"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    When I set the JSON request payload to:
        """
        { "availableFrom": "2030-11-15T11:22:33+00:00" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/available-from"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "availableFrom" should be "2030-11-15T11:22:33+00:00"

  Scenario: Update available from on approved event
    And I send "application/ld+json;domain-model=Publish"
    When I send a PATCH request to "/events/%{uuid_testevent}"
    And I send "application/ld+json;domain-model=Approve"
    When I send a PATCH request to "/events/%{uuid_testevent}"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "workflowStatus" should be "APPROVED"
    When I set the JSON request payload to:
        """
        { "availableFrom": "2030-11-15T11:22:33+00:00" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/available-from"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "availableFrom" should be "2030-11-15T11:22:33+00:00"

  Scenario: Update available from on rejected event
    And I send "application/ld+json;domain-model=Publish"
    When I send a PATCH request to "/events/%{uuid_testevent}"
    When I set the JSON request payload to:
        """
        { "reason": "The reject reason" }
        """
    And I send "application/ld+json;domain-model=Reject"
    When I send a PATCH request to "/events/%{uuid_testevent}"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "workflowStatus" should be "REJECTED"
    When I set the JSON request payload to:
        """
        { "availableFrom": "2030-11-15T11:22:33+00:00" }
        """
    When I send a PUT request to "/events/%{uuid_testevent}/available-from"
    Then the response status should be "204"
    And I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "availableFrom" should be "2030-11-15T11:22:33+00:00"
