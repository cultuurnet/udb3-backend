Feature: Test updating UDB3 productions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Add an event to a production
    Given I create a minimal permanent event and save the "id" as "eventId"
    And I create a minimal permanent event and save the "id" as "otherEventId"
    And I create a minimal permanent event and save the "id" as "extraEventId"
    And I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {
      "name": "%{name}",
      "eventIds": [
        "%{eventId}",
        "%{otherEventId}"
      ]
    }
    """
    And I send a POST request to "/productions"
    And the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "productionId" as "productionId"

    When I send a PUT request to "/productions/%{productionId}/events/%{extraEventId}"

    Then the response status should be "204"
    And I send a GET request to "/productions?name=%{name}"
    And the response body should be valid JSON
    And the JSON response at "@context" should be "http://www.w3.org/ns/hydra/context.jsonld"
    And the JSON response at "@type" should be "PagedCollection"
    And the JSON response at "itemsPerPage" should be 30
    And the JSON response at "totalItems" should be 1
    And the JSON response at "member" should have 1 entry
    And the JSON response at "member/0/productionId" should be "%{productionId}"
    And the JSON response at "member/0/production_id" should be "%{productionId}"
    And the JSON response at "member/0/name" should be "%{name}"
    And the JSON response at "member/0/events" should have 3 entries
    And the JSON response at "member/0/events" should include "%{eventId}"
    And the JSON response at "member/0/events" should include "%{otherEventId}"
    And the JSON response at "member/0/events" should include "%{extraEventId}"

  Scenario: Remove an event from a production
    Given I create a minimal permanent event and save the "id" as "eventId"
    And I create a minimal permanent event and save the "id" as "otherEventId"
    And I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {
      "name": "%{name}",
      "eventIds": [
        "%{eventId}",
        "%{otherEventId}"
      ]
    }
    """
    And I send a POST request to "/productions"
    And the response status should be "201"
    And I keep the value of the JSON response at "productionId" as "productionId"

    When I send a DELETE request to "/productions/%{productionId}/events/%{otherEventId}"

    Then the response status should be "204"
    And I send a GET request to "/productions?name=%{name}"
    And the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "@context" should be "http://www.w3.org/ns/hydra/context.jsonld"
    And the JSON response at "@type" should be "PagedCollection"
    And the JSON response at "itemsPerPage" should be 30
    And the JSON response at "totalItems" should be 1
    And the JSON response at "member" should have 1 entry
    And the JSON response at "member/0/productionId" should be "%{productionId}"
    And the JSON response at "member/0/production_id" should be "%{productionId}"
    And the JSON response at "member/0/name" should be "%{name}"
    And the JSON response at "member/0/events" should have 1 entry
    And the JSON response at "member/0/events" should include "%{eventId}"

  Scenario: Rename a production
    Given I create a minimal permanent event and save the "id" as "eventId"
    And I create a minimal permanent event and save the "id" as "otherEventId"
    And I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {
      "name": "%{name}",
      "eventIds": [
        "%{eventId}",
        "%{otherEventId}"
      ]
    }
    """
    And I send a POST request to "/productions"
    And the response status should be "201"
    And I keep the value of the JSON response at "productionId" as "productionId"

    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {
      "name": "%{name}"
    }
    """
    And I send a PUT request to "/productions/%{productionId}/name"

    Then the response status should be "204"
    And I send a GET request to "/productions?name=%{name}"
    And the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "@context" should be "http://www.w3.org/ns/hydra/context.jsonld"
    And the JSON response at "@type" should be "PagedCollection"
    And the JSON response at "itemsPerPage" should be 30
    And the JSON response at "totalItems" should be 1
    And the JSON response at "member" should have 1 entry
    And the JSON response at "member/0/productionId" should be "%{productionId}"
    And the JSON response at "member/0/production_id" should be "%{productionId}"
    And the JSON response at "member/0/name" should be "%{name}"
    And the JSON response at "member/0/events" should have 2 entries
    And the JSON response at "member/0/events" should include "%{eventId}"
    And the JSON response at "member/0/events" should include "%{otherEventId}"
