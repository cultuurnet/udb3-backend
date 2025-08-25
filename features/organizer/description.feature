Feature: Test organizer description property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Set organizer description in `en` via description endpoint
    When I set the JSON request payload to:
    """
    {"description": "The best organizer in the world!"}
    """
    And I send a PUT request to "%{organizerUrl}/description/en"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "description" should be:
    """
    {"en": "The best organizer in the world!"}
    """

  Scenario: Set organizer description in `en` and `fr` via description endpoint
    When I set the JSON request payload to:
    """
    {"description": "The best organizer in the world!"}
    """
    And I send a PUT request to "%{organizerUrl}/description/en"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    {"description": "Le meilleur organisateur du monde!"}
    """
    And I send a PUT request to "%{organizerUrl}/description/fr"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "description" should be:
    """
    {
      "en": "The best organizer in the world!",
      "fr": "Le meilleur organisateur du monde!"
    }
    """

  Scenario: Set and then update organizer description in `en` via description endpoint
    When I set the JSON request payload to:
    """
    {"description": "The best organizer in the world!"}
    """
    And I send a PUT request to "%{organizerUrl}/description/en"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    {"description": "Still the best organizer in the world!"}
    """
    And I send a PUT request to "%{organizerUrl}/description/en"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "description" should be:
    """
    {
      "en": "Still the best organizer in the world!"
    }
    """

  Scenario: Remove organizer description via description endpoint
    When I set the JSON request payload to:
    """
    {"description": "De beste werkgever!"}
    """
    And I send a PUT request to "%{organizerUrl}/description/nl"
    Then the response status should be "204"
    When I send a DELETE request to "%{organizerUrl}/description/en"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "description" should be:
    """
    {"nl": "De beste werkgever!"}
    """
    When I send a DELETE request to "%{organizerUrl}/description/nl"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "description"