Feature: Test place bookingInfo property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update place booking info
    When I set the JSON request payload to:
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
        "availabilityStarts": "2015-05-01T00:00:00+00:00",
        "availabilityEnds": "2015-07-01T00:00:00+00:00"
      }
    }
    """
    And I send a PUT request to "%{placeUrl}/booking-info"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    And the JSON response at "bookingInfo" should be:
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
      "availabilityStarts": "2015-05-01T00:00:00+00:00",
      "availabilityEnds": "2015-07-01T00:00:00+00:00"
    }
    """

  Scenario: Update place booking info via legacy endpoint
    When I set the JSON request payload to:
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
        "availabilityStarts": "2015-05-01T00:00:00+00:00",
        "availabilityEnds": "2015-07-01T00:00:00+00:00"
      }
    }
    """
    And I send a POST request to "%{placeUrl}/booking-info"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    And the JSON response at "bookingInfo" should be:
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
      "availabilityStarts": "2015-05-01T00:00:00+00:00",
      "availabilityEnds": "2015-07-01T00:00:00+00:00"
    }
    """

  Scenario: When updating the bookingInfo of an unknown place an error is returned
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
    When I send a PUT request to "/places/0680f399-7768-4ba0-b33a-d4d15c21282e/booking-info"
    Then the response status should be "404"
    And the JSON response at "detail" should be:
      """
      "The place with id \"0680f399-7768-4ba0-b33a-d4d15c21282e\" was not found."
      """

  Scenario: Delete place booking info (with put)
    Given I create a place from "places/place-with-all-fields.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "bookingInfo": {}
    }
    """
    And I send a PUT request to "%{placeUrl}/booking-info"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    And the JSON response should not have "bookingInfo"
