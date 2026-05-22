@sapi3
Feature: Test departure places in search results

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  @testIsolation
  Scenario: Departure places are embedded in search results
    When I create a minimal place and save the "url" as "departurePlaceUrl1"
    And I create a minimal place and save the "url" as "departurePlaceUrl2"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId"
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

  @testIsolation
  Scenario: Events can be searched by departure place UUID in q parameter
    When I create a minimal place and save the "url" as "departurePlaceUrl1"
    And I keep the value of the JSON response at "id" as "departurePlaceId1"
    And I create a minimal place and save the "url" as "departurePlaceUrl2"
    And I keep the value of the JSON response at "id" as "departurePlaceId2"
    And I create a minimal place and save the "url" as "departurePlaceUrl3"
    And I keep the value of the JSON response at "id" as "departurePlaceId3"
    And I create a minimal place and save the "url" as "departurePlaceUrl4"
    And I keep the value of the JSON response at "id" as "departurePlaceId4"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId1"
    And I publish the event at "/events/%{eventId1}"
    And I set the JSON request payload to:
    """
    ["%{departurePlaceUrl1}", "%{departurePlaceUrl3}", "%{departurePlaceUrl4}"]
    """
    And I send a PUT request to "/events/%{eventId1}/departurePlaces/"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId2"
    And I publish the event at "/events/%{eventId2}"
    And I set the JSON request payload to:
    """
    ["%{departurePlaceUrl2}", "%{departurePlaceUrl4}"]
    """
    And I send a PUT request to "/events/%{eventId2}/departurePlaces/"
    And I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                                 |
      | q                     | departurePlaces:%{departurePlaceId1} |
    Then I wait for the JSON response at "totalItems" to be 1
    And the JSON response at "member/0/@id" should include "%{eventId1}"
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                                 |
      | q                     | departurePlaces:%{departurePlaceId3} |
    Then I wait for the JSON response at "totalItems" to be 1
    And the JSON response at "member/0/@id" should include "%{eventId1}"
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                                 |
      | q                     | departurePlaces:%{departurePlaceId2} |
    Then I wait for the JSON response at "totalItems" to be 1
    And the JSON response at "member/0/@id" should include "%{eventId2}"
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                                                                        |
      | q                     | departurePlaces:%{departurePlaceId1} OR departurePlaces:%{departurePlaceId2} |
    Then I wait for the JSON response at "totalItems" to be 2
    And the JSON response should include:
    """
    %{eventId1}
    """
    And the JSON response should include:
    """
    %{eventId2}
    """
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                                                                         |
      | q                     | departurePlaces:%{departurePlaceId4} AND departurePlaces:%{departurePlaceId1} |
    Then I wait for the JSON response at "totalItems" to be 1
    And the JSON response at "member/0/@id" should include "%{eventId1}"

  @testIsolation
  Scenario: Events can be filtered by departure place using the departurePlaces url parameter
    When I create a minimal place and save the "url" as "departurePlaceUrl1"
    And I keep the value of the JSON response at "id" as "departurePlaceId1"
    And I create a minimal place and save the "url" as "departurePlaceUrl2"
    And I keep the value of the JSON response at "id" as "departurePlaceId2"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId1"
    And I publish the event at "/events/%{eventId1}"
    And I set the JSON request payload to:
    """
    ["%{departurePlaceUrl1}"]
    """
    And I send a PUT request to "/events/%{eventId1}/departurePlaces/"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId2"
    And I publish the event at "/events/%{eventId2}"
    And I set the JSON request payload to:
    """
    ["%{departurePlaceUrl2}"]
    """
    And I send a PUT request to "/events/%{eventId2}/departurePlaces/"
    And I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                  |
      | departurePlaces[]     | %{departurePlaceId1}  |
    Then I wait for the JSON response at "totalItems" to be 1
    And the JSON response at "member/0/@id" should include "%{eventId1}"

  @testIsolation
  Scenario: Multiple departure place url parameters use AND logic
    When I create a minimal place and save the "url" as "departurePlaceUrl1"
    And I keep the value of the JSON response at "id" as "departurePlaceId1"
    And I create a minimal place and save the "url" as "departurePlaceUrl2"
    And I keep the value of the JSON response at "id" as "departurePlaceId2"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId1"
    And I publish the event at "/events/%{eventId1}"
    And I set the JSON request payload to:
    """
    ["%{departurePlaceUrl1}", "%{departurePlaceUrl2}"]
    """
    And I send a PUT request to "/events/%{eventId1}/departurePlaces/"
    And I create a place from "places/place.json" and save the "url" as "placeId"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId2"
    And I publish the event at "/events/%{eventId2}"
    And I set the JSON request payload to:
    """
    ["%{departurePlaceUrl1}"]
    """
    And I send a PUT request to "/events/%{eventId2}/departurePlaces/"
    And I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                  |
      | departurePlaces[]     | %{departurePlaceId1}  |
    Then I wait for the JSON response at "totalItems" to be 2
    And I send a GET request to "/events" with parameters:
      | disableDefaultFilters | true                  |
      | departurePlaces[]     | %{departurePlaceId1}  |
      | departurePlaces[]     | %{departurePlaceId2}  |
    Then I wait for the JSON response at "totalItems" to be 1
    And the JSON response at "member/0/@id" should include "%{eventId1}"
