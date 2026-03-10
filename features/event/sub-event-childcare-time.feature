Feature: Test SubEvent childcareStartTime and childcareEndTime

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create an event with childcareStartTime and childcareEndTime on a single calendar subEvent
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event met kinderopvang"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "15:00",
          "childcareEndTime": "23:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/childcareStartTime" should be "15:00"
    And the JSON response at "subEvent/0/childcareEndTime" should be "23:00"

  Scenario: Create an event with only childcareStartTime on a subEvent
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event met kinderopvang start"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "15:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/childcareStartTime" should be "15:00"
    And the JSON response should not include:
    """
    "childcareEndTime"
    """

  Scenario: Create an event with childcare times on a multiple calendar subEvent
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Multiple event met kinderopvang"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "multiple",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "15:00",
          "childcareEndTime": "23:00"
        },
        {
          "startDate": "2021-05-18T16:00:00+00:00",
          "endDate": "2021-05-18T22:00:00+00:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/childcareStartTime" should be "15:00"
    And the JSON response at "subEvent/0/childcareEndTime" should be "23:00"
    And the JSON response at "subEvent/1/startDate" should be "2021-05-18T16:00:00+00:00"
    And the JSON response at "subEvent/1/endDate" should be "2021-05-18T22:00:00+00:00"

  Scenario: Update childcare times on a subEvent via PATCH
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00"
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
        "childcareStartTime": "15:00",
        "childcareEndTime": "23:00"
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "subEvent/0/childcareStartTime" should be "15:00"
    And the JSON response at "subEvent/0/childcareEndTime" should be "23:00"

  Scenario: Childcare times are cleared when omitted from PATCH
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "15:00",
          "childcareEndTime": "23:00"
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
        "status": {"type": "Available"}
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not include:
    """
    "childcareStartTime"
    """
    And the JSON response should not include:
    """
    "childcareEndTime"
    """

  Scenario: Childcare times are cleared when omitted from a PUT calendar update
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "15:00",
          "childcareEndTime": "23:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    And the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00"
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not include:
    """
    "childcareStartTime"
    """
    And the JSON response should not include:
    """
    "childcareEndTime"
    """

  Scenario: Cannot create an event when childcareStartTime equals the startDate time
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "16:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcareStartTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareStartTime must be before the time portion of startDate"

  Scenario: Cannot create an event when childcareStartTime is after the startDate time
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "17:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcareStartTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareStartTime must be before the time portion of startDate"

  Scenario: Cannot create an event when childcareEndTime equals the endDate time
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareEndTime": "22:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcareEndTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareEndTime must be after the time portion of endDate"

  Scenario: Cannot create an event when childcareEndTime is before the endDate time
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareEndTime": "21:00"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/subEvent/0/childcareEndTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareEndTime must be after the time portion of endDate"

  Scenario: Cannot PATCH a subEvent when the new startDate makes the existing childcareStartTime invalid
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareStartTime": "15:00"
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
        "startDate": "2021-05-17T14:00:00+00:00",
        "childcareStartTime": "15:00"
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcareStartTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareStartTime must be before the time portion of startDate"

  Scenario: Cannot PATCH a subEvent when the new endDate makes the existing childcareEndTime invalid
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00",
      "subEvent": [
        {
          "startDate": "2021-05-17T16:00:00+00:00",
          "endDate": "2021-05-17T22:00:00+00:00",
          "childcareEndTime": "23:00"
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
        "endDate": "2021-05-17T23:30:00+00:00",
        "childcareEndTime": "23:00"
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcareEndTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareEndTime must be after the time portion of endDate"

  Scenario: Cannot PATCH a subEvent with childcareStartTime not before the stored startDate time
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "single",
      "startDate": "2021-05-17T16:00:00+00:00",
      "endDate": "2021-05-17T22:00:00+00:00"
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
        "childcareStartTime": "16:00"
      }
    ]
    """
    And I send a PATCH request to "%{eventUrl}/subEvents"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/childcareStartTime"
    And the JSON response at "schemaErrors/0/error" should be "childcareStartTime must be before the time portion of startDate"
