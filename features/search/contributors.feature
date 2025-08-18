@sapi3
Feature: Test the Search API v3 contributors

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for Organizer with contributors
    Given I create an organizer from "organizers/organizer-minimal.json" and save the "url" as "organizerUrl"
    And I create a random email and keep it as "organizerContributorEmail"
    And I set the JSON request payload to:
    """
    [
      "%{organizerContributorEmail}"
    ]
    """
    And I send a PUT request to "%{organizerUrl}/contributors"
    And I send a GET request to "%{organizerUrl}"
    And I am using the Search API v3 base URL
    And I wait 2 seconds
    When I send a GET request to "/organizers" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | contributors:%{organizerContributorEmail} |
    Then the JSON response at "totalItems" should be 1
    And the JSON response at "member/0/@id" should be "%{organizerUrl}"
    But the JSON response should not have "member/0/contributors"

  Scenario: Search for Place with contributors
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a random email and keep it as "placeContributorEmail"
    And I set the JSON request payload to:
    """
    [
      "%{placeContributorEmail}"
    ]
    """
    And I send a PUT request to "%{placeUrl}/contributors"
    And I am using the Search API v3 base URL
    And I wait 2 seconds
    When I send a GET request to "/places" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | contributors:%{placeContributorEmail} |
    Then the JSON response at "totalItems" should be 1
    And the JSON response at "member/0/@id" should be "%{placeUrl}"
    But the JSON response should not have "member/0/contributors"

  Scenario: Search for Events with contributors
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I create a random email and keep it as "eventContributorEmail"
    And I set the JSON request payload to:
    """
    [
      "%{eventContributorEmail}"
    ]
    """
    And I send a PUT request to "%{eventUrl}/contributors"
    And I am using the Search API v3 base URL
    And I wait 2 seconds
    When I send a GET request to "/events" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | q                     | contributors:%{eventContributorEmail} |
    Then the JSON response at "totalItems" should be 1
    And the JSON response at "member/0/@id" should be "%{eventUrl}"
    But the JSON response should not have "member/0/contributors"
