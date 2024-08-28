@sapi3
Feature: Test the Search API v3 default queries from UiTID

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for a place blocked by the default query
    Given I create a place from "places/hemmekes.json" and save the "id" as "placeId"
    And I am using the Search API v3 base URL
    And I am not authorized
    And I am using an UiTID v1 API key of consumer "sapi3KeyWithUitIdFilterForDiest"
    And I am not using a x-client-id header
    And I wait 2 seconds
    When I send a GET request to "/places" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | id:%{placeId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for an event blocked by the default query
    Given I create a place from "places/hemmekes.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId"
    And I am using the Search API v3 base URL
    And I am not authorized
    And I am using an UiTID v1 API key of consumer "sapi3KeyWithUitIdFilterForDiest"
    And I am not using a x-client-id header
    And I wait 2 seconds
    When I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for a place within by the default query
    Given I create a place from "places/citadel.json" and save the "id" as "placeId"
    And I am using the Search API v3 base URL
    And I am not authorized
    And I am using an UiTID v1 API key of consumer "sapi3KeyWithUitIdFilterForDiest"
    And I am not using a x-client-id header
    And I wait 2 seconds
    When I send a GET request to "/places" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | id:%{placeId} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for an event within by the default query
    Given I create a place from "places/citadel.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "id" as "eventId"
    And I am using the Search API v3 base URL
    And I am not authorized
    And I am using an UiTID v1 API key of consumer "sapi3KeyWithUitIdFilterForDiest"
    And I am not using a x-client-id header
    And I wait 2 seconds
    When I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
