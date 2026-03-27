@sapi3
Feature: Test the Search API v3 calendar summary

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL

  Scenario: I can include various text calendar summaries
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-text                          |
    Then the JSON response should not have "calendarSummary"

  Scenario: I can include various text calendar summaries
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-text                          |
    Then the JSON response should include:
    """
    calendarSummary
    """

  Scenario: I can include various html calendar summaries
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-html                          |
    Then the JSON response should include:
    """
    calendarSummary
    """

  Scenario: I can use an unspported format
    When I am using the Search API v3 base URL
    And I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | md-pdf                           |
    Then the JSON response should be:
    """
    {
      "title": "Not Found",
      "type": "https:\/\/api.publiq.be\/probs\/url\/not-found",
      "status": 404,
      "detail": "Invalid type: pdf. Use one of: text,html"
    }
    """
