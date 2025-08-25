Feature: Test event attendanceMode property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create an event without attendanceMode results in offline mode
    When I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "offline"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Create an event with offline attendanceMode
    When I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "offline"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Create an event with online attendanceMode
    When I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location

  Scenario: Create an event with online attendanceMode and online url
    When I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location
    And the JSON response at "onlineUrl" should be "https://www.publiq.be/livestream"

  Scenario: Update the online url of an event with complete overwrite
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/attendance-mode/event-with-attendance-mode-online-and-other-online-url.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location
    And the JSON response at "onlineUrl" should be "https://www.madewithlove.be/livestream"

  Scenario: Create an event with online attendanceMode but missing online location
    When I create an event from "events/attendance-mode/event-with-attendance-mode-online-but-missing-location.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location

  Scenario: Create an event with mixed attendanceMode
    When I create an event from "events/attendance-mode/event-with-attendance-mode-mixed.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "mixed"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Update event via complete overwrite from offline to online attendanceMode
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/attendance-mode/event-with-attendance-mode-online.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location

  Scenario: Update event via complete overwrite from offline to mixed attendanceMode
    Given I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/attendance-mode/event-with-attendance-mode-mixed.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "mixed"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Update event via complete overwrite from online to offline attendanceMode
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/attendance-mode/event-with-attendance-mode-offline.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "offline"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Create an event with mixed attendanceMode and update to offline
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-mixed.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "mixed"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "offline",
      "location": "%{placeUrl}"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "attendanceMode" should be "offline"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Create an event with online attendanceMode and update to offline
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "offline",
      "location": "%{placeUrl}"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "attendanceMode" should be "offline"
    And the JSON response at "location/@id" should be "%{placeUrl}"

  Scenario: Create an event with offline attendanceMode and update to online
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "offline"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "online"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location

  Scenario: Create an event with mixed attendanceMode and update to online
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-mixed.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "mixed"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "online"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location

  Scenario: Create an event with mixed attendanceMode and update the onlineUrl
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-mixed.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response should not have "onlineUrl"

    Given I set the JSON request payload to:
    """
    {
      "onlineUrl": "https://www.publiq.be/livestream"
    }
    """
    When I send a PUT request to "%{eventUrl}/online-url"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "onlineUrl" should be "https://www.publiq.be/livestream"

  Scenario: Update the existing onlineUrl with a complete overwrite
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "eventUrl"
    When I update the event at "%{eventUrl}" from "events/attendance-mode/event-with-attendance-mode-online.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "onlineUrl"

  Scenario: Update the existing onlineUrl
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    And the JSON response at "onlineUrl" should be "https://www.publiq.be/livestream"

    Given I set the JSON request payload to:
    """
    {
      "onlineUrl": "https://www.madewithlove.be/livestream"
    }
    """
    When I send a PUT request to "%{eventUrl}/online-url"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "onlineUrl" should be "https://www.madewithlove.be/livestream"

  Scenario: Delete onlineUrl when setting attendanceMode to offline
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    And the JSON response at "attendanceMode" should be "online"
    And the JSON response at "onlineUrl" should be "https://www.publiq.be/livestream"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "offline",
      "location": "%{placeUrl}"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "attendanceMode" should be "offline"
    And the JSON response should not have "onlineUrl"

  Scenario: Delete an existing onlineUrl
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online-and-online-url.json" and save the "url" as "eventUrl"
    When I send a DELETE request to "%{eventUrl}/online-url"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "onlineUrl"

  Scenario: Deleting an non-existing onlineUrl has no effect
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "url" as "eventUrl"
    When I send a DELETE request to "%{eventUrl}/online-url"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "onlineUrl"

  Scenario: Try adding real location ton online event
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"
    And the JSON response at "location/@id" is an online location

    Given I create a place from "places/place.json" and save the "placeId" as "placeId"
    When I send a PUT request to "%{eventUrl}/location/%{placeId}"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "detail": "Cannot update the location of an online event to a physical location. Set the attendanceMode to mixed or offline first. For more information check the documentation of the update attendance mode endpoint. See: https://publiq.stoplight.io/docs/uitdatabank/b3A6NTUwMDY3NjA-attendance-mode-update",
      "status": 400,
      "title": "Attendance mode not supported",
      "type": "https://api.publiq.be/probs/uitdatabank/attendance-mode-not-supported"
    }
    """

  Scenario: Try updating location with online location
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "offline"
    And the JSON response at "location/@id" should be "%{placeUrl}"

    When I send a PUT request to "%{eventUrl}/location/00000000-0000-0000-0000-000000000000"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "detail": "Cannot update the location of an offline or mixed event to a nil location. Set the attendanceMode to online instead. For more information check the documentation of the update attendance mode endpoint. See: https://publiq.stoplight.io/docs/uitdatabank/b3A6NTUwMDY3NjA-attendance-mode-update",
      "status": 400,
      "title": "Attendance mode not supported",
      "type": "https://api.publiq.be/probs/uitdatabank/attendance-mode-not-supported"
    }
    """

  Scenario: Try creating an event with online attendanceMode but real location
    Given I set the JSON request payload from "events/attendance-mode/event-with-attendance-mode-online-and-real-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Attendance mode \"online\" needs to have an online location.",
          "jsonPointer": "/attendanceMode"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try creating an event with offline attendanceMode but online location
    Given I set the JSON request payload from "events/attendance-mode/event-with-attendance-mode-offline-and-online-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Attendance mode \"offline\" needs to have a real location.",
          "jsonPointer": "/attendanceMode"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try creating an event with mixed attendanceMode but online location
    Given I set the JSON request payload from "events/attendance-mode/event-with-attendance-mode-mixed-and-online-location.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Attendance mode \"mixed\" needs to have a real location.",
          "jsonPointer": "/attendanceMode"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating online event to offline without location
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "offline"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "A location is required when changing an online event to mixed or offline",
          "jsonPointer": "/"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating online event to mixed without location
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "online"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "mixed"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "A location is required when changing an online event to mixed or offline",
          "jsonPointer": "/"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating offline event to online with location
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "url" as "eventUrl"
    When I get the event at "%{eventUrl}"
    Then the JSON response at "attendanceMode" should be "offline"

    Given I set the JSON request payload to:
    """
    {
      "attendanceMode": "online",
      "location": "%{placeUrl}"
    }
    """
    When I send a PUT request to "%{eventUrl}/attendance-mode"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Additional object properties are not allowed: location",
          "jsonPointer": "/"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try creating an event with offline attendanceMode and online url
    Given I set the JSON request payload from "events/attendance-mode/event-with-attendance-mode-offline-and-online-url.json"
    When I send a POST request to "/events"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "An onlineUrl can not be used in combination with an offline attendanceMode.",
          "jsonPointer": "/onlineUrl"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating the onlineUrl of an offline event
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-offline.json" and save the "url" as "eventUrl"

    And I set the JSON request payload to:
    """
    {
      "onlineUrl": "https://www.publiq.be/livestream"
    }
    """
    When I send a PUT request to "%{eventUrl}/online-url"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "An onlineUrl can not be used in combination with an offline attendanceMode.",
          "jsonPointer": "/onlineUrl"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating onlineUrl with wrong url format
    Given I create an event from "events/attendance-mode/event-with-attendance-mode-online.json" and save the "url" as "eventUrl"

    And I set the JSON request payload to:
    """
    {
      "onlineUrl": "rtp://www.publiq.be/livestream"
    }
    """
    When I send a PUT request to "%{eventUrl}/online-url"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The string should match pattern: ^http[s]?:\\/\\/\\w",
          "jsonPointer": "/onlineUrl"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """
