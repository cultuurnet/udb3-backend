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

    Scenarios: Every typed word must match, the last one may be the beginning of a word
      | typed                    | first suggestion                         |
      | lentedreef leuven        | Lentedreef, 3010 Kessel-Lo               |
      | lentedreef leuv          | Lentedreef, 3010 Kessel-Lo               |
      | kerkstraat gent          | Kerkstraat, 9050 Gent                    |
      | henegouwenkaai molenbeek | Henegouwenkaai, 1080 Sint-Jans-Molenbeek |

    Scenarios: Capitals, accents and punctuation do not matter
      | typed                    | first suggestion                         |
      | LENTEDREEF KESSEL-LO     | Lentedreef, 3010 Kessel-Lo               |
      | rue des brasseurs liege  | Rue des Brasseurs, 4000 Liège            |
      | o l v ten steenstraat    | O.L.V. ten Steenstraat, 3300 Tienen      |

    Scenarios: Without a number the street is suggested, not its house numbers
      | typed                    | first suggestion                         |
      | martelarenplein          | Martelarenplein, 3000 Leuven             |

    Scenarios: With a number the address is suggested
      | typed                    | first suggestion                         |
      | martelarenplein 12       | Martelarenplein 12, 3000 Leuven          |

    Scenarios: A number at the end matches a whole house number, not the beginning of a number or postcode
      | typed                    | first suggestion                         |
      | kessel-lo lentedreef 2   | Lentedreef 2, 3010 Kessel-Lo             |
      | zelzate kerkstraat 90    | Kerkstraat 90, 9060 Zelzate              |

    Scenarios: A street named exactly what is typed comes before streets that only start with it
      | typed                    | first suggestion                         |
      | bisschoppenhof           | Bisschoppenhof, 3150 Haacht              |

  Scenario: Without a number no house numbers are suggested
    When I send a GET request to "/location-suggestions" with parameters:
      | q | lentedreef |
    Then the response status should be "200"
    And the JSON response should not include:
    """
    "type":"address"
    """

  Scenario: A suggestion has a type and coordinates
    When I send a GET request to "/location-suggestions" with parameters:
      | q | lentedreef leuven |
    Then the response status should be "200"
    And the JSON response at "member/0/type" should be "street"
    And the JSON response should have "member/0/geo/latitude"
    And the JSON response should have "member/0/geo/longitude"

  Scenario: A suggestion has a search filter to pass to Search API 3, without the distance
    When I send a GET request to "/location-suggestions" with parameters:
      | q | martelarenplein 12 |
    Then the response status should be "200"
    And the JSON response at "member/0/label" should be "Martelarenplein 12, 3000 Leuven"
    And the JSON response at "member/0/searchFilter" should be:
    """
    { "coordinates": "50.88154,4.71359" }
    """

  Scenario: Typed text is required
    When I send a GET request to "/location-suggestions"
    Then the response status should be "404"

  Scenario: Typed text longer than 120 characters is refused
    When I send a GET request to "/location-suggestions" with parameters:
      | q | Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef Lentedreef |
    Then the response status should be "404"
