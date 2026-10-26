Feature: Test the Ticketmatic integration

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create an event from "events/ticketmatic/event-with-ticketmatic-link.json" and save the "url" as "eventUrl"

  Scenario: A date with a Ticketmatic reservation link gets its availability from Ticketmatic
    When I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"

  Scenario: Ticketmatic updates the availability of the dates with its reservation link
    Given I am authorized with an OAuth client access token for "ticketmatic"
    And I set the JSON request payload to:
    """
    {
      "identifier": "ticketmatic:30cc_testticketmaticcom:15960",
      "type": "Unavailable"
    }
    """
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "204"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Unavailable"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"

    Given I am authorized with an OAuth client access token for "ticketmatic"
    And I set the JSON request payload to:
    """
    {
      "identifier": "ticketmatic:30cc_testticketmaticcom:15960",
      "type": "Available"
    }
    """
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "204"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"

  Scenario: Other clients can not update the availability of reservation links
    Given I am authorized with an OAuth client access token for "test_client"
    And I set the JSON request payload to:
    """
    {
      "identifier": "ticketmatic:30cc_testticketmaticcom:15960",
      "type": "Unavailable"
    }
    """
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "403"

  Scenario: An unknown identifier is not found
    Given I am authorized with an OAuth client access token for "ticketmatic"
    And I set the JSON request payload to:
    """
    {
      "identifier": "ticketmatic:30cc_testticketmaticcom:0",
      "type": "Unavailable"
    }
    """
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "404"
