@sapi3
Feature: Test departure places in search results

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  @testIsolation
  Scenario: Departure places are returned in search results
    When I create a minimal place and save the "url" as "departurePlaceUrl1"
    And I create a minimal place and save the "url" as "departurePlaceUrl2"
    And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "eventId"
    And I send a GET request to "/events/%{eventId}"
    And I publish the event at "/events/%{eventId}"
    And I set the JSON request payload to:
    """
    [
      "%{departurePlaceUrl1}",
      "%{departurePlaceUrl2}"
    ]
    """
    And I send a PUT request to "/events/%{eventId}/departurePlaces/"
    And I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | embed                 | true          |
      | disableDefaultFilters | true          |
    Then I wait for the JSON response at "totalItems" to be 1
    And I wait for the JSON response at "member/0/departurePlaces" to have 2 entries
    And the JSON response at "member/0/departurePlaces" should include "%{departurePlaceUrl1}"
    And the JSON response at "member/0/departurePlaces" should include "%{departurePlaceUrl2}"
