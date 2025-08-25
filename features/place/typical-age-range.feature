Feature: Test places typicalAgeRange property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"

  Scenario: Places have a default typicalAgeRange of all ages
    When I create a place from "places/place.json" and save the "url" as "placeUrl"
    Then the response status should be "201"
    And I get the event at "%{placeUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: Update place typical age range
    When I set the JSON request payload to:
    """
    { "typicalAgeRange": "1-12" }
    """
    And I send a PUT request to "%{placeUrl}/typical-age-range"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "typicalAgeRange" should be "1-12"

  Scenario: Update place typical age range via legacy endpoint
    When I set the JSON request payload to:
    """
    { "typicalAgeRange": "1-12" }
    """
    And I send a POST request to "%{placeUrl}/typical-age-range"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "typicalAgeRange" should be "1-12"

  Scenario: Update and delete place typicalAgeRange
    When I set the JSON request payload to:
      """
      { "typicalAgeRange": "1-12" }
      """
    And I send a PUT request to "%{placeUrl}/typical-age-range"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "typicalAgeRange" should be "1-12"
    When I send a DELETE request to "%{placeUrl}/typical-age-range"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: When the request body is invalid an error is returned
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "" }
        """
    When I send a POST request to "%{placeUrl}/typicalAgeRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/typicalAgeRange",
        "error":"The string should match pattern: ^[\\d]*-[\\d]*$"
      }
    ]
    """
    And I get the place at "%{placeUrl}"
    And the JSON response at "typicalAgeRange" should be "-"

  Scenario: When the minimum age is bigger than the maximum age an error is returned
    And I set the JSON request payload to:
        """
        { "typicalAgeRange": "12-6" }
        """
    When I send a POST request to "%{placeUrl}/typicalAgeRange"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/typicalAgeRange",
        "error":"\"From\" age should not be greater than the \"to\" age."
      }
    ]
    """
    And I get the place at "%{placeUrl}"
    And the JSON response at "typicalAgeRange" should be "-"
