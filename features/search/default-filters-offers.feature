@sapi3
Feature: Test the Search API v3 default filters on offers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: By default non-belgium offers are not shown
    Given I create a place from "places/place-in-the-netherlands.json" and save the "id" as "placeId"
    And I wait for the place with url "/places/%{placeId}" to be indexed
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{placeId} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | addressCountry | *                             |
      | q              | id:(%{placeId} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{placeId}
    """
    And the JSON response should include:
    """
    %{eventId}
    """
    When I send a GET request to "/places" with parameters:
      | q | id:(%{placeId} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | addressCountry | *                             |
      | q              | id:(%{placeId} OR %{eventId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/place\/%{placeId}",
          "@type": "Place"
        }
      ]
    }
    """
    When I send a GET request to "/events" with parameters:
      | q | id:(%{placeId} OR %{eventId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | addressCountry | *                             |
      | q              | id:(%{placeId} OR %{eventId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: By default non public audienceTypes are not shown
    Given I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-minimal-permanent-for-members.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | audienceType | *             |
      | q            | id:%{eventId} |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: By default draft offers are not shown
    Given I create a minimal place and save the "url" as "placeUrl"
    And I keep the value of the JSON response at "id" as "placeId"
    And I create a minimal permanent event and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | workflowStatus | *                              |
      | availableFrom  | *                              |
      | availableTo    | *                              |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{eventId}
    """
    And the JSON response should include:
    """
    %{placeId}
    """
    When I send a GET request to "/places" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | workflowStatus | *                              |
      | availableFrom  | *                              |
      | availableTo    | *                              |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/place\/%{placeId}",
          "@type": "Place"
        }
      ]
    }
    """
    When I send a GET request to "/events" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | workflowStatus | *                              |
      | availableFrom  | *                              |
      | availableTo    | *                              |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: By default rejected offers are no longer shown
    Given I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I reject the event at "/events/%{eventId}" with reason "Reject event"
    And I reject the place at "/places/%{placeId}" with reason "Rejected"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | workflowStatus | *                             |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{eventId}
    """
    And the JSON response should include:
    """
    %{placeId}
    """
    When I send a GET request to "/places" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | workflowStatus | *                             |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/place\/%{placeId}",
          "@type": "Place"
        }
      ]
    }
    """
    When I send a GET request to "/events" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | workflowStatus | *                             |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: By default deleted offers are no longer shown
    Given I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I delete the event at "/events/%{eventId}"
    And I delete the place at "/places/%{placeId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/offers" with parameters:
      | workflowStatus | *                             |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 2
    And the JSON response should include:
    """
    %{eventId}
    """
    And the JSON response should include:
    """
    %{placeId}
    """
    When I send a GET request to "/places" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/places" with parameters:
      | workflowStatus | *                             |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/place\/%{placeId}",
          "@type": "Place"
        }
      ]
    }
    """
    When I send a GET request to "/events" with parameters:
      | q | id:(%{eventId} OR %{placeId}) |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | workflowStatus | *                             |
      | q              | id:(%{eventId} OR %{placeId}) |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: By default events with available to in the past should not be shown
    Given I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-single-calendar.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | availableFrom | *             |
      | availableTo   | *             |
      | q             | id:%{eventId} |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: By default events with available from in the future should not be shown
    Given I create a minimal place and save the "id" as "placeId"
    And I create an event from "events/event-with-available-from-in-the-far-future.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | q | id:%{eventId} |
    Then the JSON response at "totalItems" should be 0
    When I send a GET request to "/events" with parameters:
      | availableFrom | *             |
      | availableTo   | *             |
      | q             | id:%{eventId} |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{eventId}",
          "@type": "Event"
        }
      ]
    }
    """
