Feature: Test the UDB3 events API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Events have no bookingInfo by default
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    Then the response status should be "201"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "bookingInfo"

  Scenario: Update bookingInfo
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        {
          "url": "https://www.domain.be/reservations/eventname",
          "urlLabel": {
            "nl": "Reserveer plaatsen",
            "fr": "Réservez des places",
            "en": "Reserve places",
            "de": "Platzieren Sie eine Reservierung"
          },
          "email": "user@example.com",
          "phone": "0123456789",
          "availabilityStarts": "2025-05-01T00:00:00+00:00",
          "availabilityEnds": "2025-07-01T00:00:00+00:00"
        }
        """
    When I send a PUT request to "%{eventUrl}/booking-info"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingInfo" should be:
    """
    {
      "availabilityEnds": "2025-07-01T00:00:00+00:00",
      "availabilityStarts": "2025-05-01T00:00:00+00:00",
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

  Scenario: Update bookingInfo with malformed url
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
      "url": "https://www.arboretumkalmthout.be%20",
      "urlLabel": {
        "nl": "Koop tickets"
      }
    }
    """
      When I send a PUT request to "%{eventUrl}/booking-info"
      Then the response status should be "400"
      And the JSON response should be:
    """
    {
      "type": "https://api.publiq.be/probs/body/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "detail": "Given string is not a valid url."
    }
    """

  Scenario: Update bookingInfo with url but missing urlLabel
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
        "url": "https://www.domain.be/reservations/eventname"
    }
    """
    When I send a PUT request to "%{eventUrl}/booking-info"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type":"https://api.publiq.be/probs/body/invalid-data",
      "title":"Invalid body data",
      "status":400,
      "schemaErrors": [
        {
          "jsonPointer":"/",
          "error":"'urlLabel' property is required by 'url' property"
        }
      ]
    }
    """

  Scenario: Update bookingInfo with invalid properties
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    {
        "email": "foobar",
        "phone": 1234,
        "availabilityStarts": "2025-05-01",
        "availabilityEnds": "2025-07-01"
    }
    """
    When I send a PUT request to "%{eventUrl}/booking-info"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type":"https://api.publiq.be/probs/body/invalid-data",
      "title":"Invalid body data",
      "status":400,
      "schemaErrors": [
        {
          "error": "The data (integer) must match the type: string",
          "jsonPointer": "/phone"
        },
        {
          "error": "The data must match the 'email' format",
          "jsonPointer": "/email"
        },
        {
          "error": "The data must match the 'date-time' format",
          "jsonPointer": "/availabilityStarts"
        },
        {
          "error": "The data must match the 'date-time' format",
          "jsonPointer": "/availabilityEnds"
        }
      ]
    }
    """

  Scenario: Update bookingInfo via deprecated camelCase URL and deprecated bookingInfo wrapper property
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        {
          "bookingInfo": {
            "url": "https://www.domain.be/reservations/eventname",
            "urlLabel": {
              "nl": "Reserveer plaatsen",
              "fr": "Réservez des places",
              "en": "Reserve places",
              "de": "Platzieren Sie eine Reservierung"
            },
            "email": "user@example.com",
            "phone": "0123456789",
            "availabilityStarts": "2025-05-01T00:00:00+00:00",
            "availabilityEnds": "2025-07-01T00:00:00+00:00"
          }
        }
        """
    When I send a PUT request to "%{eventUrl}/bookingInfo"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingInfo" should be:
    """
    {
      "availabilityEnds": "2025-07-01T00:00:00+00:00",
      "availabilityStarts": "2025-05-01T00:00:00+00:00",
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

  Scenario: Update bookingInfo via deprecated POST method and deprecated bookingInfo wrapper property
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        {
          "bookingInfo": {
            "url": "https://www.domain.be/reservations/eventname",
            "urlLabel": {
              "nl": "Reserveer plaatsen",
              "fr": "Réservez des places",
              "en": "Reserve places",
              "de": "Platzieren Sie eine Reservierung"
            },
            "email": "user@example.com",
            "phone": "0123456789",
            "availabilityStarts": "2025-05-01T00:00:00+00:00",
            "availabilityEnds": "2025-07-01T00:00:00+00:00"
          }
        }
        """
    When I send a POST request to "%{eventUrl}/bookingInfo"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "bookingInfo" should be:
    """
    {
      "availabilityEnds": "2025-07-01T00:00:00+00:00",
      "availabilityStarts": "2025-05-01T00:00:00+00:00",
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

  Scenario: When updating the bookingInfo of an unknown event an error is returned
    Given I set the JSON request payload to:
      """
      {
        "url": "https://www.domain.be/reservations/eventname",
        "urlLabel": {
          "nl": "Reserveer plaatsen",
          "fr": "Réservez des places",
          "en": "Reserve places",
          "de": "Platzieren Sie eine Reservierung"
        },
        "email": "user@example.com",
        "phone": "0123456789",
        "availabilityStarts": "2025-05-01T00:00:00+00:00",
        "availabilityEnds": "2025-07-01T00:00:00+00:00"
      }
      """
      When I send a PUT request to "/events/0680f399-7768-4ba0-b33a-d4d15c21282e/booking-info"
      Then the response status should be "404"
      And the JSON response at "detail" should be:
      """
      "The event with id \"0680f399-7768-4ba0-b33a-d4d15c21282e\" was not found."
      """

  Scenario: When using a triple slashed url an error is returned
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I set the JSON request payload to:
        """
        {
          "url": "https:///www.domain.be/reservations/eventname",
          "urlLabel": {
            "nl": "Reserveer plaatsen",
            "fr": "Réservez des places",
            "en": "Reserve places",
            "de": "Platzieren Sie eine Reservierung"
          },
          "email": "user@example.com",
          "phone": "0123456789",
          "availabilityStarts": "2025-05-01T00:00:00+00:00",
          "availabilityEnds": "2025-07-01T00:00:00+00:00"
        }
        """
    When I send a PUT request to "%{eventUrl}/booking-info"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type":"https://api.publiq.be/probs/body/invalid-data",
      "title":"Invalid body data",
      "status":400,
      "schemaErrors": [
        {
          "error": "The string should match pattern: ^http[s]?:\\/\\/\\w",
          "jsonPointer": "/url"
        }
      ]
    }
    """

  Scenario: Delete event booking info (with put)
    Given I create an event from "events/event-with-bookinginfo.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "bookingInfo": {}
    }
    """
    And I send a PUT request to "%{eventUrl}/booking-info"
    Then the response status should be "204"
    When I get the event at "%{eventUrl}"
    And the JSON response should not have "bookingInfo"
