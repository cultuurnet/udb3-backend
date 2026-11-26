@sapi3
Feature: Test the number of events per place

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @testIsolation
  Scenario: The place with the most events comes first, with its number of events
    Given I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl1"
    And I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl2"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl3"
    And I wait for the event with url "%{eventUrl1}" to be indexed
    And I wait for the event with url "%{eventUrl2}" to be indexed
    And I wait for the event with url "%{eventUrl3}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalEvents" should be 3
    And the JSON response at "places" should have 2 entries
    And the JSON response at "places/0/name/nl" should be "%{name}"
    And the JSON response at "places/0/eventCount" should be 2
    And the JSON response at "places/1/eventCount" should be 1
    And the JSON response should have "places/0/geo/latitude"

  @testIsolation
  Scenario: The search filters decide which events are counted
    Given I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-children-only.json" and save the "url" as "childrenEventUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "otherEventUrl"
    And I wait for the event with url "%{childrenEventUrl}" to be indexed
    And I wait for the event with url "%{otherEventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events/places" with parameters:
      | childrenOnly          | true |
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalEvents" should be 1
    And the JSON response at "places/0/eventCount" should be 1

  @testIsolation
  Scenario: An online event counts as an event, but not for a place
    Given I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "onlineEventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I wait for the event with url "%{onlineEventUrl}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalEvents" should be 2
    And the JSON response at "places" should have 1 entry
    And the JSON response at "places/0/eventCount" should be 1

  @testIsolation
  Scenario: Without matching events there are no places
    Given I am using the Search API v3 base URL
    When I send a GET request to "/events/places" with parameters:
      | disableDefaultFilters | true |
    Then the response status should be "200"
    And the JSON response at "totalEvents" should be 0
    And the JSON response at "places" should have 0 entries

  Scenario: Grouping by production is not supported
    Given I am using the Search API v3 base URL
    When I send a GET request to "/events/places" with parameters:
      | groupBy | productionId |
    Then the response status should be "404"
