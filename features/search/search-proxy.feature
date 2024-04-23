@sapi3
Feature: Test the UDB3 search proxy

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I create a minimal place and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I create a minimal permanent event and save the "url" as "eventUrl"
    And I wait for the event with url "%{eventUrl}" to be indexed
    And I create a minimal organizer and save the "url" as "organizerUrl"
    And I wait for the organizer with url "%{organizerUrl}" to be indexed
    And I am not authorized

  Scenario: Search events via proxy endpoint
    When I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | %{eventUrl} |
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 1
    And the JSON response at "totalItems" should not be 0
    And the JSON response at "member/0/@id" should be "%{eventUrl}"

  Scenario: Search places via proxy endpoint
    When I send a GET request to "/places" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | %{placeUrl} |
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 1
    And the JSON response at "totalItems" should not be 0
    And the JSON response at "member/0/@id" should be "%{placeUrl}"

  Scenario: Search organizers via proxy endpoint
    When I send a GET request to "/organizers" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | %{organizerUrl} |
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 1
    And the JSON response at "totalItems" should not be 0
    And the JSON response at "member/0/@id" should be "%{organizerUrl}"
