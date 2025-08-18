Feature: Test status updates

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "url" as "eventUrl"

  Scenario: Update status
    When I set the JSON request payload to:
    """
    {"type":"Unavailable","reason":{"nl":"Het event is uitgesteld."}}
    """
    And I send a PUT request to "%{eventUrl}/status"
    Then the response status should be "204"
    And I send a GET request to "%{eventUrl}"
    Then the response status should be "200"
    And the JSON response at "status" should be:
    """
    {"type":"Unavailable","reason":{"nl":"Het event is uitgesteld."}}
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4790
  Scenario: Update status on lessenreeks keeps availableTo on start date
    Given I create a place from "places/place.json" and save the "url" as "placeUrl"
    When I create an event from "events/event-with-eventtype-lessenreeks.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "availableTo" should be "2021-05-17T08:00:00+00:00"

    Given I set the JSON request payload to:
            """
            {"type":"Unavailable","reason":{"nl":"Het event is uitgesteld."}}
            """
    When I send a PUT request to "%{eventUrl}/status"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "availableTo" should be "2021-05-17T08:00:00+00:00"

  Scenario: Update status of single calendar Unavailable with a reason
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-single-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
            [
              {
                "id": 0,
                "status": {
                  "type": "TemporarilyUnavailable",
                  "reason": {
                    "nl": "Het event is tijdelijk uitgesteld."
                  }
                }
              }
            ]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "status" should be:
          """
          {
            "type": "TemporarilyUnavailable",
            "reason": {
              "nl": "Het event is tijdelijk uitgesteld."
            }
          }
          """
    And the JSON response at "subEvent/0/status" should be:
          """
          {
            "type": "TemporarilyUnavailable",
            "reason": {
              "nl": "Het event is tijdelijk uitgesteld."
            }
          }
          """

  Scenario: Update status of 1 sub event to Unavailable with a reason
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-multiple-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
            [
              {
                "id": 0,
                "status": {
                  "type": "Available"
                }
              },
              {
                "id": 1,
                "status": {
                  "type": "Unavailable",
                  "reason": {
                    "nl": "Het event is uitgesteld."
                  }
                }
              }
            ]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "status" should be:
          """
          {"type":"Available"}
          """
    And the JSON response at "subEvent/0/status" should be:
          """
          {"type": "Available"}
          """
    And the JSON response at "subEvent/1/status" should be:
          """
          {
            "type": "Unavailable",
            "reason": {
              "nl": "Het event is uitgesteld."
            }
          }
          """

  Scenario: Update status of 2 sub events to Unavailable
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"
    And I set the JSON request payload from "events/legacy/event-with-multiple-calendar.json"
    When I send a POST request to "/events/"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "uuid_testevent"
    Given I set the JSON request payload to:
          """
            [
              {
                "id": 0,
                "status": {
                  "type": "Unavailable"
                }
              },
              {
                "id": 1,
                "status": {
                  "type": "Unavailable"
                }
              }
            ]
          """
    And I send a PATCH request to "/events/%{uuid_testevent}/subEvents"
    Then the response status should be "204"
    When I send a GET request to "/events/%{uuid_testevent}"
    Then the response status should be "200"
    And the JSON response at "status" should be:
          """
          {"type":"Unavailable"}
          """
    And the JSON response at "subEvent/0/status" should be:
          """
          {"type": "Unavailable"}
          """
    And the JSON response at "subEvent/1/status" should be:
          """
          {"type": "Unavailable"}
          """

  Scenario: Update status of copied event that was originally unavailable
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    When I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "eventId" as "uuid_event"
    And the response body should be valid JSON

    When I set the JSON request payload to:
      """
      [
         {
            "id": 0,
            "status": {
               "type":"Unavailable"
            }
         },
         {
            "id": 1,
            "status": {
               "type":"Unavailable"
            }
         }
      ]
      """
    And I send a PATCH request to "/events/%{uuid_event}/sub-events"
    Then the response status should be "204"

    When I set the JSON request payload to:
        """
        {
          "calendarType": "single",
          "subEvent": [
            {
              "startDate": "2020-06-05T18:00:00+02:00",
              "endDate": "2020-06-05T21:00:00+02:00"
            }
          ]
        }
        """
    And I send a POST request to "/events/%{uuid_event}/copies"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "new_uuid_event"

    When I set the JSON request payload to:
      """
        [
           {
              "id": 0,
              "status": {
                 "type":"Unavailable"
              }
           }
        ]
      """
    And I send a PATCH request to "/events/%{new_uuid_event}/sub-events"
    Then the response status should be "204"

    When I send a GET request to "/events/%{new_uuid_event}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "subEvent/0/status/type" should be "Unavailable"
    And the JSON response at "status/type" should be "Unavailable"

  Scenario: Update status and reason of copied event that was originally unavailable
    Given I set the JSON request payload from "places/place.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "placeId" as "uuid_place"

    When I set the JSON request payload from "events/legacy/event-with-referenced-location.json"
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "eventId" as "uuid_event"
    And the response body should be valid JSON

    When I set the JSON request payload to:
      """
      [
         {
            "id": 0,
            "status": {
               "type":"Unavailable"
            }
         },
         {
            "id": 1,
            "status": {
               "type":"Unavailable"
            }
         }
      ]
      """
    And I send a PATCH request to "/events/%{uuid_event}/sub-events"
    Then the response status should be "204"

    When I set the JSON request payload to:
        """
        {
          "calendarType": "single",
          "subEvent": [
            {
              "startDate": "2020-07-05T18:00:00+02:00",
              "endDate": "2020-07-05T21:00:00+02:00"
            }
          ]
        }
        """
    And I send a POST request to "/events/%{uuid_event}/copies"
    Then the response status should be "201"
    And the response body should be valid JSON
    And I keep the value of the JSON response at "eventId" as "new_uuid_event"

    When I set the JSON request payload to:
      """
        [
           {
              "id": 0,
              "status": {
                 "type":"Unavailable",
                 "reason": {
                    "nl": "Covid"
                 }
              }
           }
        ]
      """
    And I send a PATCH request to "/events/%{new_uuid_event}/sub-events"
    Then the response status should be "204"

    When I send a GET request to "/events/%{new_uuid_event}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "calendarType" should be "single"
    And the JSON response at "startDate" should be "2020-07-05T18:00:00+02:00"
    And the JSON response at "endDate" should be "2020-07-05T21:00:00+02:00"
    And the JSON response at "status/type" should be "Unavailable"
    And the JSON response at "subEvent/0/status/type" should be "Unavailable"
    And the JSON response at "subEvent/0/startDate" should be "2020-07-05T18:00:00+02:00"
    And the JSON response at "subEvent/0/endDate" should be "2020-07-05T21:00:00+02:00"
