Feature: Test organizer name property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Update organizer name in default language `nl` via name endpoint
    When I set the JSON request payload to:
    """
    {"name": "madewithlove"}
    """
    And I send a PUT request to "%{organizerUrl}/name/nl"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "name/nl" should be "madewithlove"

  Scenario: Update organizer name in non-default language `fr` via name endpoint
    When I set the JSON request payload to:
    """
    {"name": "faitavecamour"}
    """
    And I send a PUT request to "%{organizerUrl}/name/fr"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "name/nl" should not be "faitavecamour"
    And the JSON response at "name/fr" should be "faitavecamour"