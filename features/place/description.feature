Feature: Test place description property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update place description Dutch
    When I set the JSON request payload to:
    """
    { "description": "Updated description test_place in Dutch" }
    """
    And I send a PUT request to "%{placeUrl}/description/nl"
    Then the response status should be "204"
    And I set the JSON request payload to:
    """
    { "description": "Updated description test_place in English" }
    """
    And I send a PUT request to "%{placeUrl}/description/en"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "description/nl" should be:
    """
    "Updated description test_place in Dutch"
    """
    And the JSON response at "description/en" should be:
    """
    "Updated description test_place in English"
    """

  @bugfix # Relates to https://jira.uitdatabank.be/browse/III-5150
  # Right now the JSON response returns an empty string when the description is empty, it shouldn't return any value
  Scenario: It can remove a description by sending an empty description
    When I set the JSON request payload to:
    """
    { "description": "Updated description test_place in Dutch" }
    """
    And I send a PUT request to "%{placeUrl}/description/nl"
    Given I set the JSON request payload to:
    """
    { "description": "" }
    """
    And I send a PUT request to "%{placeUrl}/description/nl"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "description/nl" should be:
    """
    ""
    """
