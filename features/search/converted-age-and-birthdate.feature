@sapi3
Feature: Test the Search API v3 converted typical age range and birthdate range on offers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  @testIsolation
  Scenario: A single event entered with a typical age range exposes the matching birthdate range
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-typical-age-range-single.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q             | id:%{eventId} |
      | embed         | true          |
      | availableFrom | *             |
      | availableTo   | *             |
    Then the JSON response at "member/0/birthdateRangeConverted" should be:
    """
    {
      "from": "2009-04-23",
      "to": "2011-04-22"
    }
    """
    And the JSON response should not include:
    """
    typicalAgeRangeConverted
    """

  @testIsolation
  Scenario: A single event entered with a birthdate range exposes the matching typical age range
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-birthdate-range-single.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q             | id:%{eventId} |
      | embed         | true          |
      | availableFrom | *             |
      | availableTo   | *             |
    Then the JSON response at "member/0/typicalAgeRangeConverted" should be "6-7"
    And the JSON response should not include:
    """
    birthdateRangeConverted
    """

  @testIsolation
  Scenario: A permanent event converts against the indexing date, so only presence is asserted
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-age-range-6-to-12.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q     | id:%{eventId} |
      | embed | true          |
    Then the JSON response should include:
    """
    birthdateRangeConverted
    """
    And the JSON response at "member/0/birthdateRangeConverted" should have 2 entries
    And the JSON response should not include:
    """
    typicalAgeRangeConverted
    """

  @testIsolation
  Scenario: An all ages event exposes no converted ranges and keeps its typical age range
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-all-ages.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q     | id:%{eventId} |
      | embed | true          |
    Then the JSON response at "member/0/typicalAgeRange" should be "-"
    And the JSON response should not include:
    """
    typicalAgeRangeConverted
    """
    And the JSON response should not include:
    """
    birthdateRangeConverted
    """

  @testIsolation
  Scenario: An event entered with both a typical age range and a birthdate range keeps the entered age
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-age-and-birthdate-range-single.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q             | id:%{eventId} |
      | embed         | true          |
      | availableFrom | *             |
      | availableTo   | *             |
    Then the JSON response at "member/0/typicalAgeRange" should be "9-11"
    And the JSON response should not include:
    """
    typicalAgeRangeConverted
    """
    And the JSON response should not include:
    """
    birthdateRangeConverted
    """
    When I send a GET request to "/events" with parameters:
      | minAge        | 9             |
      | maxAge        | 11            |
      | availableFrom | *             |
      | availableTo   | *             |
      | q             | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | minAge        | 6             |
      | maxAge        | 7             |
      | availableFrom | *             |
      | availableTo   | *             |
      | q             | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | birthdateRangeFrom | 2010-01-01    |
      | birthdateRangeTo   | 2010-12-31    |
      | availableFrom      | *             |
      | availableTo        | *             |
      | q                  | id:%{eventId} |
    Then the JSON response at "totalItems" should be 1
