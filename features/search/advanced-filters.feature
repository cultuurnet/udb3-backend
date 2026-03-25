@sapi3
Feature: Test the Search API v3 advanced filters

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for a single label using an advanced query
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
      | q | labels:%{labelname} |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | q | labels:%{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | labels:%{labelname} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | locationLabels | %{labelname} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a single term using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | q | id:%{uuid_place} AND terms.id:Yf4aZBfsUEu2NsQqsprngw |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q          | id:%{uuid_place} AND terms.label:"Cultuur- of ontmoetingscentrum" |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q       | id:%{eventId} AND terms.id:0.50.4.0.0 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q          | id:%{eventId} AND terms.label:Concert |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q       | id:%{eventId} AND terms.id:1.8.2.0.0 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q          | id:%{eventId} AND terms.label:"Jazz en blues" |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for multiple labels using an advanced query
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
      | q | labels:(%{labelname} AND foobar) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | q | labels:(%{labelname} AND foobar) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | labels:(%{labelname} AND foobar) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | location.labels:(%{labelname} AND foobar) |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for multiple terms using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | q | id:%{uuid_place} AND terms.id:Yf4aZBfsUEu2NsQqsprngw |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:%{uuid_place} AND terms.label:"Cultuur- of ontmoetingscentrum" |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND terms.id:(0.50.4.0.0 OR 1.8.2.0.0) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND terms.label:(Concert OR "Jazz en blues") |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for ages using an advanced query
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-age-range.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND typicalAgeRange:[18 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND typicalAgeRange:[7 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND typicalAgeRange:[* TO 5] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND typicalAgeRange:[* TO 11] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND allAges:true |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND allAges:false |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} AND allAges:* |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for country using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND address.nl.addressCountry:NL |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND address.nl.addressCountry:BE |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND address.nl.addressCountry:NL |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND address.nl.addressCountry:BE |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND address.nl.addressCountry:NL |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND address.nl.addressCountry:BE |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a single region using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:nis-24020 |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:nis-24134 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:nis-24020 |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:nis-24134 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:nis-24020 |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:nis-24134 |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for multiple regions using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:(nis-20001 AND nis-24020) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:(nis-20001 AND nis-24134) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:(nis-20001 AND nis-24020) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:(nis-20001 AND nis-24134) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:(nis-20001 AND nis-24020) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND regions:(nis-20001 AND nis-24134) |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for languages using an advanced query
    When I create a random name of 10 characters
    And I create a place from "places/place-in-german-and-french.json" and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-in-german-and-french.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:nl |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:de |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:(de OR fr) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:nl |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:de |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:(de OR fr) |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND mainLanguage:de |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND mainLanguage:fr |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:nl |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:(de OR fr) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:nl |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:(de OR fr) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND mainLanguage:de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND mainLanguage:fr |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:nl |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND languages:(de OR fr) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:nl |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND completedLanguages:(de OR fr) |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND mainLanguage:de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND mainLanguage:fr |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for status using an advanced query
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
      | q | id:(%{uuid_place} OR %{eventId}) AND status:TemporarilyUnavailable |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND status:Available |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND status:TemporarilyUnavailable |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND status:Available |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND status:TemporarilyUnavailable |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND status:Available |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for booking availability using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-unavailable-sub-events.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | availableTo   | *                                                                    |
      | availableFrom | *                                                                    |
      | q             | id:(%{uuid_place} OR %{eventId}) AND bookingAvailability:Unavailable |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | availableTo   | *                                                                  |
      | availableFrom | *                                                                  |
      | q             | id:(%{uuid_place} OR %{eventId}) AND bookingAvailability:Available |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for date & time using an advanced query
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND calendarType:permanent |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND calendarType:periodic |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND calendarType:permanent |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND calendarType:periodic |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND calendarType:permanent |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND calendarType:periodic |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND created:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[2024-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND modified:[* TO 2024-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for timestamps using an advanced query
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-single-calendar.json" and save the "id" as "eventId"
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[2021-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[2021-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[2090-01-01T00:00:00%2B01:00 TO *] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[* TO 2020-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[* TO 2090-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | availableTo   | *                                                               |
      | availableFrom | *                                                               |
      | q             | id:(%{eventId}) AND dateRange:[* TO 2020-01-01T00:00:00%2B01:00] |
    Then the JSON response at "totalItems" should be 0

  Scenario: Search for text using an advanced query
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
      | q | id:(%{uuid_place} OR %{eventId}) AND description.nl:"%{name}" |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND description.nl:"%{name}" |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | q | id:(%{uuid_place} OR %{eventId}) AND description.nl:"%{name}" |
    Then the JSON response at "totalItems" should be 1
