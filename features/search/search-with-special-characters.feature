@sapi3
Feature: Test the UDB3 search proxy

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"


  Scenario: Search places via proxy endpoint
    Given I set the value of name to "test-test"
    Given I create a name that includes a dash and keep it as "name"
    Given I create a place from "places/place-with-required-fields-and-variable-name.json" and save the "url" as "placeUrl"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I am not authorized

    When I send a GET request to "/offers" with parameters:
      | limit                 | 1 |
      | embed                 | true |
      | disableDefaultFilters | true |
      | text      | *%{name}* |
    Then the response status should be "200"
    And the JSON response at "itemsPerPage" should be 1
    And the JSON response at "totalItems" should not be 0
    And the JSON response at "member/0/@id" should be "%{placeUrl}"