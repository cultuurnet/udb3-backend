@sapi3
Feature: Test the location suggestions for search on route

  Background:
    Given I am using the Search API v3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I send and accept "application/json"

  Scenario Outline: The first suggestion for what a user types
    When I send a GET request to "/location-suggestions" with parameters:
      | q | <typed> |
    Then the response status should be "200"
    And the JSON response at "member/0/label" should be "<first suggestion>"

    Scenarios:
      | typed                    | first suggestion                         |
      | lentedreef leuven        | Lentedreef, 3010 Kessel-Lo               |
      | lentedreef leuv          | Lentedreef, 3010 Kessel-Lo               |
      | LENTEDREEF KESSEL-LO     | Lentedreef, 3010 Kessel-Lo               |
      | kerkstraat gent          | Kerkstraat, 9050 Gent                    |
      | rue des brasseurs liege  | Rue des Brasseurs, 4000 Liège            |
      | henegouwenkaai molenbeek | Henegouwenkaai, 1080 Sint-Jans-Molenbeek |
      | o l v ten steenstraat    | O.L.V. ten Steenstraat, 3300 Tienen      |
      | martelarenplein 12       | Martelarenplein 12, 3000 Leuven          |

  Scenario: A suggestion has a type and coordinates
    When I send a GET request to "/location-suggestions" with parameters:
      | q | lentedreef leuven |
    Then the response status should be "200"
    And the JSON response at "member/0/type" should be "street"
    And the JSON response should have "member/0/geo/latitude"
    And the JSON response should have "member/0/geo/longitude"

  Scenario: Typed text is required
    When I send a GET request to "/location-suggestions"
    Then the response status should be "404"

  Scenario: Typed text longer than 120 characters is refused
    When I send a GET request to "/location-suggestions" with parameters:
      | q | Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef |
    Then the response status should be "404"
