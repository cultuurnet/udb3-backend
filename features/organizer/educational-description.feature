Feature: Test updating organizers via complete overwrite

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Creating a new educational description
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    Given I set the JSON request payload to:
    """
    {
        "educationalDescription": "Nederlandse update"
    }
    """
    When I send a PUT request to "%{organizerUrl}/educational-description/nl"
    Then the response status should be "204"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "educationalDescription" should be:
    """
    {
        "nl": "Nederlandse update"
    }
    """

  Scenario: Failing to update educational description with faulty body
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    Given I set the JSON request payload to:
    """
    {
        "so-wrong": "Invalid value"
    }
    """
    When I send a PUT request to "%{organizerUrl}/educational-description/nl"
    Then the response status should be "400"

  Scenario: Update an existing educational description
    Given I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    Given I set the JSON request payload to:
    """
    {
        "educationalDescription": "Nederlandse update"
    }
    """
    When I send a PUT request to "%{organizerUrl}/educational-description/nl"
    Then the response status should be "204"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "educationalDescription" should be:
    """
    {
        "nl": "Nederlandse update",
        "fr": "French educational description",
        "de": "German educational description",
        "en": "English educational description"
    }
    """

  Scenario: Delete an educational description
    Given I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    When I send a DELETE request to "%{organizerUrl}/educational-description/nl"
    Then the response status should be "204"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "educationalDescription" should be:
    """
    {
        "fr": "French educational description",
        "de": "German educational description",
        "en": "English educational description"
    }
    """

  Scenario: Fail to delete an educational description on an organisation that does not exist
    When I send a DELETE request to "organizers/39171e0b-7b9f-4b28-b2f4-bae8abba1a39/educational-description/nl"
    Then the response status should be "404"
