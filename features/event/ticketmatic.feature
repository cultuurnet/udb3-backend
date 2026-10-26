Feature: Test the Ticketmatic integration

  # Every event with the Ticketmatic test link shares one identifier, so a push also reaches the events of other
  # scenarios. Each scenario only checks its own events and deletes them at the end, which removes their registration.

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I set the variable "reservationLink" to "https://leuven-school-met-cultuur.test.ticketmatic.com/30cc_testticketmaticcom/register/15960"

  Scenario: A date with a Ticketmatic reservation link gets its availability from Ticketmatic
    Given I create an event from "events/ticketmatic/event-with-ticketmatic-link.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"
    And I delete the event at "%{eventUrl}"

  Scenario: A Ticketmatic reservation link with capitals is registered too
    Given I set the variable "reservationLink" to "https://Leuven-School-Met-Cultuur.test.ticketmatic.com/30CC_testticketmaticcom/register/15960"
    And I create an event from "events/ticketmatic/event-with-ticketmatic-link.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And I delete the event at "%{eventUrl}"

  Scenario: A Ticketmatic reservation link added with a calendar update is registered
    Given I create an event from "events/event-with-single-calendar.json" and save the "url" as "eventUrl"
    And I set the JSON request payload from "events/ticketmatic/calendar-with-ticketmatic-link.json"
    When I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"
    And I delete the event at "%{eventUrl}"

  Scenario: Ticketmatic updates the availability of the dates with its reservation link
    Given I create an event from "events/ticketmatic/event-with-ticketmatic-link.json" and save the "url" as "eventUrl"
    And I am authorized with an OAuth client access token for "ticketmatic"
    And I set the variable "bookingAvailabilityType" to "Unavailable"
    And I set the JSON request payload from "ticketing/booking-availability.json"
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "204"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Unavailable"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"

    Given I am authorized with an OAuth client access token for "ticketmatic"
    And I set the variable "bookingAvailabilityType" to "Available"
    And I set the JSON request payload from "ticketing/booking-availability.json"
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "204"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"
    And I delete the event at "%{eventUrl}"

  Scenario: Ticketmatic updates every date with the same reservation link
    Given I create an event from "events/ticketmatic/event-with-ticketmatic-link-on-two-dates.json" and save the "url" as "eventUrl"
    And I am authorized with an OAuth client access token for "ticketmatic"
    And I set the variable "bookingAvailabilityType" to "Unavailable"
    And I set the JSON request payload from "ticketing/booking-availability.json"
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "204"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Unavailable"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"
    And I delete the event at "%{eventUrl}"

  Scenario: Ticketmatic updates a copied event too
    Given I create an event from "events/ticketmatic/event-with-ticketmatic-link.json" and save the "id" as "eventId"
    And I set the JSON request payload from "events/ticketmatic/calendar-with-ticketmatic-link.json"
    And I send a POST request to "/events/%{eventId}/copies"
    And the response status should be "201"
    And I keep the value of the JSON response at "eventId" as "copyId"
    And I get the event at "/events/%{copyId}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Available"
    And I am authorized with an OAuth client access token for "ticketmatic"
    And I set the variable "bookingAvailabilityType" to "Unavailable"
    And I set the JSON request payload from "ticketing/booking-availability.json"
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "204"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "/events/%{eventId}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Unavailable"
    And I get the event at "/events/%{copyId}"
    And the JSON response at "subEvent/0/bookingAvailability/type" should be "Unavailable"
    And I delete the event at "/events/%{eventId}"
    And I delete the event at "/events/%{copyId}"

  Scenario: Ticketmatic no longer updates a date after its reservation link was removed
    Given I create an event from "events/ticketmatic/event-with-ticketmatic-link.json" and save the "url" as "eventUrl"
    And I set the JSON request payload from "events/ticketmatic/calendar-without-ticketmatic-link.json"
    And I send a PUT request to "%{eventUrl}/calendar"
    And the response status should be "204"
    And I am authorized with an OAuth client access token for "ticketmatic"
    And I set the variable "bookingAvailabilityType" to "Available"
    And I set the JSON request payload from "ticketing/booking-availability.json"
    When I send a POST request to "/ticketing/booking-availability/"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/bookingAvailability/type" should be "Unavailable"
    And the JSON response at "subEvent/1/bookingAvailability/type" should be "Unavailable"
    And I delete the event at "%{eventUrl}"

  Scenario: Other clients can not update the availability of reservation links
    Given I am authorized with an OAuth client access token for "test_client"
    And I set the variable "bookingAvailabilityType" to "Unavailable"
    And I set the JSON request payload from "ticketing/booking-availability.json"
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

  Scenario: An update without availability type is rejected
    Given I am authorized with an OAuth client access token for "ticketmatic"
    And I set the JSON request payload to:
    """
    {
      "identifier": "ticketmatic:30cc_testticketmaticcom:15960"
    }
    """
    When I send a POST request to "/ticketing/booking-availability/"
    Then the response status should be "400"
