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

  Scenario: Create an event with a reservation link inline in the subEvent
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
            "url": "https://www.domain.be/reservations/eventname"
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
      "url": "https://www.domain.be/reservations/eventname"
    }
    """

  Scenario: The top-level bookingInfo does not end up on the subEvents
    Given I create an event from "events/event-with-single-calendar-and-bookinginfo.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "bookingInfo/url" should be "https://www.example.com"
    And the JSON response should not have "subEvent/0/bookingInfo"

  Scenario: Update an event with a reservation link per subEvent
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/event-with-subevent-reservation-links.json"
    Then the response status should be "200"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingInfo/url" should be "https://www.domain.be/reservations/first"
    And the JSON response at "subEvent/1/bookingInfo/url" should be "https://www.domain.be/reservations/second"

  Scenario: Set the reservation link of a subEvent with a calendar PUT
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "subEvent": [
        {
          "startDate": "2021-05-17T08:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "bookingInfo": {
            "url": "https://www.domain.be/reservations/eventname"
          }
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingInfo/url" should be "https://www.domain.be/reservations/eventname"

  Scenario: Set the reservation link of a subEvent
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname"
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
      "url": "https://www.domain.be/reservations/eventname"
    }
    """

  Scenario: An omitted bookingInfo keeps the current reservation link
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname"
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
        "status": {
          "type": "Unavailable"
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
      "url": "https://www.domain.be/reservations/eventname"
    }
    """

  Scenario: Replace the reservation link of a subEvent
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname"
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
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/othername"
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
      "url": "https://www.domain.be/reservations/othername"
    }
    """

  Scenario: Set the reservation link of one subEvent of a multiple calendar event
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {
        "nl": "Multiple calendar event"
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
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2021-05-17T08:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00"
        },
        {
          "startDate": "2021-05-18T08:00:00+00:00",
          "endDate": "2021-05-18T22:00:00+00:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname"
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
      "url": "https://www.domain.be/reservations/eventname"
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

  Scenario: Clear the reservation link with a calendar PUT
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "bookingInfo": {
          "url": "https://www.domain.be/reservations/eventname"
        }
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "startDate": "2021-05-17T08:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00"
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not include:
    """
    "bookingInfo"
    """

  Scenario: Other properties than url on a subEvent bookingInfo are ignored
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
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
      "url": "https://www.domain.be/reservations/eventname"
    }
    """

  Scenario: An empty bookingInfo is not allowed on a subEvent
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
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
    Then the response status should be "400"
    And the response body should be valid JSON
