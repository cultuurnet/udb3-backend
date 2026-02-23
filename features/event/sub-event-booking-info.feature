Feature: Test SubEvent bookingInfo

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: SubEvents have no bookingInfo by default
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not include:
    """
    "bookingInfo"
    """

  Scenario: Create an event with bookingInfo inline in the subEvent
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {
        "nl": "Event met bookingInfo op subEvent"
      },
      "terms": [
        {
          "id": "0.50.4.0.0",
          "label": "Concert",
          "domain": "eventtype"
        }
      ],
      "location": {
        "@id": "%{placeUrl}"
      },
      "calendarType": "single",
      "startDate": "2021-05-17T08:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T08:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "bookingInfo": {
            "url": "https://www.domain.be/reservations/eventname",
            "urlLabel": {
              "nl": "Reserveer plaatsen"
            },
            "email": "user@example.com",
            "phone": "0123456789"
          }
        }
      ]
    }
    """
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingInfo" should be:
    """
    {
      "email": "user@example.com",
      "phone": "0123456789",
      "url": "https://www.domain.be/reservations/eventname",
      "urlLabel": {
        "nl": "Reserveer plaatsen"
      }
    }
    """

  Scenario: Update bookingInfo on a subEvent
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname",
          "urlLabel": {
            "nl": "Reserveer plaatsen",
            "fr": "Réservez des places",
            "en": "Reserve places",
            "de": "Platzieren Sie eine Reservierung"
          },
          "email": "user@example.com",
          "phone": "0123456789"
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingInfo" should be:
    """
    {
      "email": "user@example.com",
      "phone": "0123456789",
      "url": "https://www.domain.be/reservations/eventname",
      "urlLabel": {
        "de": "Platzieren Sie eine Reservierung",
        "en": "Reserve places",
        "fr": "Réservez des places",
        "nl": "Reserveer plaatsen"
      }
    }
    """

  Scenario: Update bookingInfo on one subEvent of a multiple calendar event
    Given I create an event from "events/event-with-multiple-sub-events.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname",
          "urlLabel": {
            "nl": "Reserveer plaatsen"
          },
          "email": "user@example.com",
          "phone": "0123456789"
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingInfo" should be:
    """
    {
      "email": "user@example.com",
      "phone": "0123456789",
      "url": "https://www.domain.be/reservations/eventname",
      "urlLabel": {
        "nl": "Reserveer plaatsen"
      }
    }
    """
    And the JSON response at "subEvent/1" should be:
    """
    {
      "id": 1,
      "startDate": "2021-05-18T08:00:00+00:00",
      "endDate": "2021-05-18T22:00:00+00:00",
      "status": {
        "type": "Available"
      },
      "bookingAvailability": {
        "type": "Available"
      },
      "@type": "Event"
    }
    """

  Scenario: Clear bookingInfo on a subEvent
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname",
          "urlLabel": {
            "nl": "Reserveer plaatsen"
          }
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not include:
    """
    "bookingInfo"
    """
