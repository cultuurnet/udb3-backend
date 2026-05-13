Feature: Test SubEvent childcare times

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create a single-calendar event with childcare times in CET
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"start": "15:00", "end": "23:00"}
    """

  Scenario: Create a single-calendar event with childcare times in UTC
    Given I set the variable "startDate" to "2021-05-17T14:00:00+00:00"
    And I set the variable "endDate" to "2021-05-17T20:00:00+00:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"start": "15:00", "end": "23:00"}
    """

  Scenario: Create a multiple-calendar event with childcare times
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "startDate2" to "2021-05-18T16:00:00+02:00"
    And I set the variable "endDate2" to "2021-05-18T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-multiple-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"start": "15:00", "end": "23:00"}
    """

  Scenario: Add childcare times to a subEvent via PATCH
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "childcare": {"start": "15:00", "end": "23:00"}}]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"start": "15:00", "end": "23:00"}
    """

  Scenario: Childcare times are preserved when omitted from PATCH
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "status": {"type": "Available"}}]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"start": "15:00", "end": "23:00"}
    """

  Scenario: Childcare times are cleared when explicitly set to empty in PATCH
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "childcare": {}}]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not include:
    """
    "childcare"
    """

  Scenario: Childcare times are cleared when omitted from a PUT calendar update
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+02:00",
      "endDate": "2021-05-17T22:00:00+02:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+02:00",
          "endDate": "2021-05-17T22:00:00+02:00"
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not include:
    """
    "childcare"
    """

  Scenario: Create a single-calendar event with only childcare.start
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare-start-only.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"start": "15:00"}
    """

  Scenario: Create a single-calendar event with only childcare.end
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare-end-only.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "subEvent/0/childcare" should be:
    """
    {"end": "23:00"}
    """

  Scenario: Cannot create an event when childcare.start equals the startDate time in CET
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "16:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before the time portion of startDate"

  Scenario: Cannot create an event when childcare.start equals the startDate time in UTC
    Given I set the variable "startDate" to "2021-05-17T14:00:00+00:00"
    And I set the variable "endDate" to "2021-05-17T20:00:00+00:00"
    And I set the variable "childcareStart" to "16:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before the time portion of startDate"

  Scenario: Cannot create an event when childcare.start is after the startDate time
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "17:00"
    And I set the variable "childcareEnd" to "23:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before the time portion of startDate"

  Scenario: Cannot create an event when childcare.end equals the endDate time
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "22:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after the time portion of endDate"

  Scenario: Cannot create an event when childcare.end is before the endDate time
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "21:00"
    When I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after the time portion of endDate"

  Scenario: Cannot PATCH a subEvent when the new startDate makes the existing childcare.start invalid
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "startDate": "2021-05-17T14:00:00+02:00",
        "childcare": {"start": "15:00", "end": "23:00"}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before the time portion of startDate"

  Scenario: Cannot PATCH a subEvent when the new endDate makes the existing childcare.end invalid
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [
      {
        "id": 0,
        "endDate": "2021-05-17T23:30:00+02:00",
        "childcare": {"start": "15:00", "end": "23:00"}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after the time portion of endDate"

  Scenario: Cannot PATCH a subEvent with childcare.start not before the stored startDate time
    Given I set the variable "startDate" to "2021-05-17T14:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "childcare": {"start": "16:00", "end": "23:00"}}]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before the time portion of startDate"

  Scenario: Cannot PATCH a subEvent with a new startDate that makes the preserved childcare.start invalid
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "startDate": "2021-05-17T15:00:00+02:00"}]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before the time portion of startDate"

  Scenario: Cannot PATCH a subEvent with a new endDate that makes the preserved childcare.end invalid
    Given I set the variable "startDate" to "2021-05-17T16:00:00+02:00"
    And I set the variable "endDate" to "2021-05-17T22:00:00+02:00"
    And I set the variable "childcareStart" to "15:00"
    And I set the variable "childcareEnd" to "23:00"
    And I set the JSON request payload from "events/sub-event-childcare/event-single-with-childcare.json"
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    [{"id": 0, "endDate": "2021-05-17T23:00:00+02:00"}]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after the time portion of endDate"
