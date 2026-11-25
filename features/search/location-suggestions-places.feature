@sapi3
Feature: Test the places in the location suggestions

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: A published place is suggested by its name and its address, as a point
    Given I create a place from "places/place-het-depot.json" and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I am using the Search API v3 base URL
    When I wait until the location suggestions for "het depot %{name}" contain "Het Depot %{name} (Martelarenplein 12, 3000 Leuven)"
    Then the JSON response at "member/0/type" should be "place"
    And the JSON response should have "member/0/searchFilter/coordinates"
    When I wait until the location suggestions for "martelarenplein 12 leuven" contain "Het Depot %{name} (Martelarenplein 12, 3000 Leuven)"

  Scenario: A place in a sub-municipality is also suggested on its municipality
    Given I create a place from "places/place-in-kessel-lo.json" and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I am using the Search API v3 base URL
    Then I wait until the location suggestions for "café %{name} leuven" contain "Café %{name} (Tiensesteenweg 2, 3010 Kessel-Lo)"

  Scenario: A deleted place is no longer suggested
    Given I create a place from "places/place-het-depot.json" and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I am using the Search API v3 base URL
    And I wait until the location suggestions for "het depot %{name}" contain "Het Depot %{name} (Martelarenplein 12, 3000 Leuven)"
    And I am using the UDB3 base URL
    When I delete the place at "/places/%{placeId}"
    And I am using the Search API v3 base URL
    Then I wait until the location suggestions for "het depot %{name}" no longer contain "Het Depot %{name} (Martelarenplein 12, 3000 Leuven)"

  Scenario: A place that is not published is not suggested
    Given I create a place from "places/place-het-depot.json" and save the "id" as "placeId"
    And I wait for the place with url "/places/%{placeId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/location-suggestions" with parameters:
      | q | het depot %{name} |
    Then the JSON response should not include:
    """
    Het Depot %{name}
    """
