@sapi3 @testIsolation
Feature: Test the Search API v3 url parameters on offers

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Search for a single label using the common filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{placeId}/labels/%{labelname}"
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
    When I am using the UDB3 base URL
    And I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{placeId}/labels/%{labelname}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    And I send a GET request to "/events" with parameters:
      | locationLabels | %{labelname} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a multiple labels using the common filter
    When I create a random labelname of 10 characters
    And I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I create a random labelname of 10 characters
    And I send a PUT request to "/places/%{placeId}/labels/%{labelname}"
    And I send a PUT request to "/events/%{eventId}/labels/%{labelname}"
    And I send a PUT request to "/places/%{placeId}/labels/foobar"
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
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/places" with parameters:
      | termIds | Yf4aZBfsUEu2NsQqsprngw |
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
    When I send a GET request to "/places" with parameters:
      | termLabels | Cultuur- of ontmoetingscentrum |
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
      | termIds | 0.50.4.0.0 |
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
    When I send a GET request to "/events" with parameters:
      | termLabels | Concert |
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
    When I send a GET request to "/events" with parameters:
      | termIds | 1.8.2.0.0 |
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
    When I send a GET request to "/events" with parameters:
      | termLabels | Jazz en blues |
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

  Scenario: Search for a multiple terms using the common filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | termIds[] | 0.50.4.0.0 |
      | termIds[] | 1.8.2.0.0  |
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
    When I send a GET request to "/events" with parameters:
      | termLabels[] | Concert       |
      | termLabels[] | Jazz en blues |
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

  Scenario: Search for ages using the common filter
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/event-with-age-range-6-to-12.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | minAge | 18 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | minAge | 7 |
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
    When I send a GET request to "/events" with parameters:
      | maxAge | 5 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | maxAge | 11 |
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
    When I send a GET request to "/events" with parameters:
      | allAges | true |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | allAges | false |
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
    When I send a GET request to "/events" with parameters:
      | allAges | * |
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

  Scenario: Search for country using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | addressCountry | NL |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | addressCountry | BE |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | addressCountry | NL |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | addressCountry | BE |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | addressCountry | NL |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | addressCountry | BE |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for a single region using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | regions | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | regions[] | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | regions | nis-24134 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | regions | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | regions | nis-24134 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | regions | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | regions | nis-24134 |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for multiple regions using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | regions[] | nis-20001 |
      | regions[] | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | regions[] | nis-20001 |
      | regions[] | nis-24134 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | regions[] | nis-20001 |
      | regions[] | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | regions[] | nis-20001 |
      | regions[] | nis-24134 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | regions[] | nis-20001 |
      | regions[] | nis-24020 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | regions[] | nis-20001 |
      | regions[] | nis-24134 |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for offers using the geo distance filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | coordinates | 50.99,4.97 |
      | distance    | 5km        |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | coordinates | 50.99,4.97 |
      | distance    | 5km        |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | coordinates | 50.99,4.97 |
      | distance    | 5km        |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | coordinates | 51.054,3.717 |
      | distance    | 5km          |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | coordinates | 51.054,3.717 |
      | distance    | 5km          |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | coordinates | 51.054,3.717 |
      | distance    | 5km          |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | coordinates | 51.054,3.717 |
      | distance    | 5km          |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for offers using the geo bounds filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | bounds | 50.8,4.7%7C51.2,5.2 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | bounds | 50.8,4.7%7C51.2,5.2 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | bounds | 50.8,4.7%7C51.2,5.2 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | bounds | 52.0,4.0%7C53.0,6.0 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | bounds | 52.0,4.0%7C53.0,6.0 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | bounds | 52.0,4.0%7C53.0,6.0 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for languages using the common filters
    When I create a random name of 10 characters
    And I create a place from "places/place-in-german-and-french.json" and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-in-german-and-french.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | languages[] | nl |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | languages[] | de |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | languages[] | de |
      | languages[] | fr |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | completedLanguages[] | nl |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | completedLanguages[] | de |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | completedLanguages[] | de |
      | completedLanguages[] | fr |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | mainLanguage | de |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | mainLanguage | fr |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | languages[] | nl |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | languages[] | de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | languages[] | de |
      | languages[] | fr |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | completedLanguages[] | nl |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | completedLanguages[] | de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | completedLanguages[] | de |
      | completedLanguages[] | fr |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | mainLanguage | de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | mainLanguage | fr |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | languages[] | nl |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | languages[] | de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | languages[] | de |
      | languages[] | fr |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | completedLanguages[] | nl |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | completedLanguages[] | de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | completedLanguages[] | de |
      | completedLanguages[] | fr |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | mainLanguage | de |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | mainLanguage | fr |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for status using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
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
    And I send a PUT request to "/places/%{placeId}/status"
    And I send a PUT request to "/events/%{eventId}/status"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | status | TemporarilyUnavailable |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | status | Available |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | status | TemporarilyUnavailable |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | status | Available |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | status | TemporarilyUnavailable |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | status | Available |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for booking availability using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-unavailable-sub-events.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | bookingAvailability | Unavailable |
      | availableTo         | *           |
      | availableFrom       | *           |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | bookingAvailability | Available |
      | availableTo         | *         |
      | availableFrom       | *         |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for date & time using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I publish the event at "/events/%{eventId}"
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | calendarType | permanent |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | calendarType | periodic |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | calendarType | permanent |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | calendarType | periodic |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | calendarType | permanent |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | calendarType | periodic |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | createdFrom | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | createdFrom | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | createdFrom | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | createdFrom | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | createdFrom | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | createdFrom | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | createdTo | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | createdTo | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | createdTo | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | createdTo | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | createdTo | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | createdTo | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | modifiedFrom | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | modifiedFrom | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | modifiedFrom | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | modifiedFrom | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | modifiedFrom | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | modifiedFrom | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | modifiedTo | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | modifiedTo | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | modifiedTo | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | modifiedTo | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | modifiedTo | 2090-01-01T00:00:00%2B01:00 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | modifiedTo | 2024-01-01T00:00:00%2B01:00 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

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
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | dateFrom      | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | dateFrom      | 2021-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateFrom      | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/offers" with parameters:
      | dateTo        | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | dateTo        | 2020-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | dateTo        | 2090-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | dateTo        | 2020-01-01T00:00:00%2B01:00 |
      | availableTo   | *                           |
      | availableFrom | *                           |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for text using the common filters
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I create a random name of 10 characters
    And I set the JSON request payload to:
    """
    { "description": "%{name}" }
    """
    And I send a PUT request to "/places/%{placeId}/description/nl"
    And I send a PUT request to "/events/%{eventId}/description/nl"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | text | %{name} |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/places" with parameters:
      | text | %{name} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | text | %{name} |
    Then the JSON response at "totalItems" should be 1

  Scenario: Search for an offer using the id filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | id | %{placeId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/offers" with parameters:
      | id | %{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | id | %{placeId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | id | ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | id | %{eventId} |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | id | ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for offers using the postalCode filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | postalCode | 3271 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | postalCode | 9000 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | postalCode | 3271 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | postalCode | 9000 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | postalCode | 3271 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | postalCode | 9000 |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for offers using the creator filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | creator | edcee0f7-5906-4e92-8551-a7f5d37ba453 |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | creator | ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | creator | edcee0f7-5906-4e92-8551-a7f5d37ba453 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | creator | ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | creator | edcee0f7-5906-4e92-8551-a7f5d37ba453 |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | creator | ffffffff-ffff-ffff-ffff-ffffffffffff |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for offers using the audienceType filter
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/audience-type/event-audience-type-children-only.json" and save the "id" as "eventId"
    And I publish the event at "/events/%{eventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | audienceType | childrenOnly |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | audienceType | everyone |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """

  Scenario: Search for offers using the attendanceMode filter
    When I create a minimal place and save the "url" as "placeUrl"
    And I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "id" as "offLineEventId"
    And I create an event from "events/attendance-mode/event-with-attendance-mode-mixed.json" and save the "id" as "mixedEventId"
    And I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "id" as "onlineEventId"
    And I publish the event at "/events/%{offLineEventId}"
    And I publish the event at "/events/%{mixedEventId}"
    And I publish the event at "/events/%{onlineEventId}"
    And I wait 2 seconds
    And I am using the Search API v3 base URL
    When I send a GET request to "/events" with parameters:
      | attendanceMode | offline |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{offLineEventId}",
          "@type": "Event"
        }
      ]
    }
    """
    When I send a GET request to "/events" with parameters:
      | attendanceMode | mixed |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{mixedEventId}",
          "@type": "Event"
        }
      ]
    }
    """
    When I send a GET request to "/events" with parameters:
      | attendanceMode | online |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 1,
      "member" : [
        {
          "@id": "http:\/\/io.uitdatabank.local:80\/event\/%{onlineEventId}",
          "@type": "Event"
        }
      ]
    }
    """

  Scenario: Search for offers using the hasMediaObjects filter
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL
    When I send a GET request to "/offers" with parameters:
      | hasMediaObjects | false |
    Then the JSON response at "totalItems" should be 2
    When I send a GET request to "/offers" with parameters:
      | hasMediaObjects | true |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/places" with parameters:
      | hasMediaObjects | false |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/places" with parameters:
      | hasMediaObjects | true |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
    When I send a GET request to "/events" with parameters:
      | hasMediaObjects | false |
    Then the JSON response at "totalItems" should be 1
    When I send a GET request to "/events" with parameters:
      | hasMediaObjects | true |
    Then the JSON response should be:
    """
    {
      "@context": "http:\/\/www.w3.org\/ns\/hydra\/context.jsonld",
      "@type": "PagedCollection",
      "itemsPerPage": 30,
      "totalItems": 0,
      "member" : []
    }
    """
