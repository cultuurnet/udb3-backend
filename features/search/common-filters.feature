@sapi3
Feature: Test the Search API v3 common filters

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for a single label using the common filter
    When I create a random labelname of 10 characters
    And I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{uuid_place}/labels/%{labelname}"
    And I send a PUT request to "/events/%{eventId}/labels/%{labelname}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | labels | %{labelname} |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | labels | %{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | labels | %{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | locationLabels | %{labelname} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a multiple labels using the common filter
    When I create a random labelname of 10 characters
    And I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{uuid_place}/labels/%{labelname}"
    And I send a PUT request to "/events/%{eventId}/labels/%{labelname}"
    And I send a PUT request to "/places/%{uuid_place}/labels/foobar"
    And I send a PUT request to "/events/%{eventId}/labels/foobar"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | labels[] | %{labelname} |
      | labels[] | foobar       |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | labels[] | %{labelname} |
      | labels[] | foobar       |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | labels[] | %{labelname} |
      | labels[] | foobar       |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | locationLabels[] | %{labelname} |
      | locationLabels[] | foobar       |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a single term using the common filter
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | termIds | Yf4aZBfsUEu2NsQqsprngw |
      | q       | id:%{uuid_place}       |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | termLabels | Cultuur- of ontmoetingscentrum |
      | q          | id:%{uuid_place}               |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | termIds | 0.50.4.0.0    |
      | q       | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | termLabels | Concert   |
      | q          | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | termIds | 1.8.2.0.0     |
      | q       | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | termLabels | Jazz en blues     |
      | q          | id:%{eventId}     |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a multiple terms using the common filter
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | termIds[] | Yf4aZBfsUEu2NsQqsprngw |
      | q         | id:%{uuid_place}       |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | termLabels[] | Cultuur- of ontmoetingscentrum |
      | q            | id:%{uuid_place}               |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | termIds[] | 0.50.4.0.0    |
      | termIds[] | 1.8.2.0.0     |
      | q         | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | termLabels[] | Concert       |
      | termLabels[] | Jazz en blues |
      | q            | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for ages using the common filter
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-age-range.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | minAge | 18            |
      | q      | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | minAge | 7             |
      | q      | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | maxAge | 5            |
      | q      | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | maxAge | 11            |
      | q      | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | allAges | true          |
      | q       | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | allAges | false         |
      | q       | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | allAges | *             |
      | q       | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for country using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | addressCountry | NL                               |
      | q              | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | addressCountry | BE                               |
      | q              | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | addressCountry | NL                               |
      | q              | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | addressCountry | BE                               |
      | q              | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | addressCountry | NL                               |
      | q              | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | addressCountry | BE                               |
      | q              | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a single region using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | regions   | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | regions[] | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | regions   | nis-24134                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | regions   | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | regions   | nis-24134                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | regions   | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | regions   | nis-24134                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for multiple regions using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | regions[] | nis-20001                        |
      | regions[] | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | regions[] | nis-20001                        |
      | regions[] | nis-24134                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | regions[] | nis-20001                        |
      | regions[] | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | regions[] | nis-20001                        |
      | regions[] | nis-24134                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | regions[] | nis-20001                        |
      | regions[] | nis-24020                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | regions[] | nis-20001                        |
      | regions[] | nis-24134                        |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for languages using the common filters
    When I create a random name of 10 characters
    And I create a place from "places/place-in-german-and-french.json" and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-in-german-and-french.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | languages[] | nl                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | languages[] | de                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | languages[] | de                               |
      | languages[] | fr                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | completedLanguages[] | nl                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | completedLanguages[] | de                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | completedLanguages[] | de                               |
      | completedLanguages[] | fr                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | mainLanguage | de                               |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | mainLanguage | fr                               |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | languages[] | nl                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | languages[] | de                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | languages[] | de                               |
      | languages[] | fr                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | completedLanguages[] | nl                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | completedLanguages[] | de                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | completedLanguages[] | de                               |
      | completedLanguages[] | fr                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | mainLanguage | de                               |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | mainLanguage | fr                               |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | languages[] | nl                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | languages[] | de                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | languages[] | de                               |
      | languages[] | fr                               |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | completedLanguages[] | nl                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | completedLanguages[] | de                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | completedLanguages[] | de                               |
      | completedLanguages[] | fr                               |
      | q                    | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | mainLanguage | de                               |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | mainLanguage | fr                               |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for status using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    {
      "type": "TemporarilyUnavailable",
      "reason": {
        "nl": "Uitzonderlijk gesloten wegens renovatie."
      }
    }
    """
    And I send a PUT request to "/places/%{uuid_place}/status"
    And I send a PUT request to "/events/%{eventId}/status"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | status | TemporarilyUnavailable           |
      | q      | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | status | Available                        |
      | q      | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | status | TemporarilyUnavailable           |
      | q      | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | status | Available                        |
      | q      | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | status | TemporarilyUnavailable           |
      | q      | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | status | Available                        |
      | q      | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for booking availability using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-unavailable-sub-events.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | bookingAvailability | Unavailable                      |
      | availableTo         | *                                |
      | availableFrom       | *                                |
      | q                   | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | bookingAvailability | Available                        |
      | availableTo         | *                                |
      | availableFrom       | *                                |
      | q                   | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for date & time using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | calendarType | permanent                        |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | calendarType | periodic                         |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | calendarType | permanent                        |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | calendarType | periodic                         |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | calendarType | permanent                        |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | calendarType | periodic                         |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | createdFrom | 2024-01-01T00:00:00%2B01:00      |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | createdFrom | 2090-01-01T00:00:00%2B01:00      |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | createdFrom | 2024-01-01T00:00:00%2B01:00      |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | createdFrom | 2090-01-01T00:00:00%2B01:00      |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | createdFrom | 2024-01-01T00:00:00%2B01:00      |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | createdFrom | 2090-01-01T00:00:00%2B01:00      |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | createdTo | 2090-01-01T00:00:00%2B01:00      |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | createdTo | 2024-01-01T00:00:00%2B01:00      |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | createdTo | 2090-01-01T00:00:00%2B01:00      |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | createdTo | 2024-01-01T00:00:00%2B01:00      |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | createdTo | 2090-01-01T00:00:00%2B01:00      |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | createdTo | 2024-01-01T00:00:00%2B01:00      |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | modifiedFrom | 2024-01-01T00:00:00%2B01:00     |
      | q           | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | modifiedFrom | 2090-01-01T00:00:00%2B01:00      |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | modifiedFrom | 2024-01-01T00:00:00%2B01:00      |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | modifiedFrom | 2090-01-01T00:00:00%2B01:00      |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | modifiedFrom | 2024-01-01T00:00:00%2B01:00      |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | modifiedFrom | 2090-01-01T00:00:00%2B01:00      |
      | q            | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | modifiedTo | 2090-01-01T00:00:00%2B01:00      |
      | q          | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | modifiedTo | 2024-01-01T00:00:00%2B01:00      |
      | q          | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | modifiedTo | 2090-01-01T00:00:00%2B01:00      |
      | q          | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | modifiedTo | 2024-01-01T00:00:00%2B01:00      |
      | q          | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | modifiedTo | 2090-01-01T00:00:00%2B01:00      |
      | q          | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | modifiedTo | 2024-01-01T00:00:00%2B01:00      |
      | q          | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for timestamps using the common filters
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-single-calendar.json" and save the "id" as "eventId"
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | dateFrom      | 2021-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | dateFrom      | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateFrom      | 2021-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateFrom      | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | dateTo        | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | dateTo        | 2020-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | dateTo        | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateTo        | 2020-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
      | q             | id:(%{eventId})             |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for text using the common filters
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    { "description": "%{name}" }
    """
    And I send a PUT request to "/places/%{uuid_place}/description/nl"
    And I send a PUT request to "/events/%{eventId}/description/nl"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | text      | %{name}                          |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | text      | %{name}                          |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | text      | %{name}                          |
      | q         | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 1
