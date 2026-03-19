Feature: Test opening hours childcare times

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create a periodic event with childcare times on opening hours
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met kinderopvang"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday", "tuesday", "wednesday"]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "openingHours/0/childcare/start" should be "08:00"
    And the JSON response at "openingHours/0/childcare/end" should be "18:00"

  Scenario: Create a periodic event with childcare only on some opening hours
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met gedeeltelijke kinderopvang"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday", "tuesday", "wednesday"]
        },
        {
          "opens": "10:00",
          "closes": "16:00",
          "dayOfWeek": ["saturday", "sunday"]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "openingHours/0/childcare/start" should be "08:00"
    And the JSON response at "openingHours/0/childcare/end" should be "18:00"
    And the JSON response at "openingHours/1/opens" should be "10:00"
    And the JSON response should not have "openingHours/1/childcare"

  Scenario: Childcare times are cleared when omitted from a PUT calendar update
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
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
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday"]
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

  Scenario: Cannot create an event when childcare.start equals opens
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "09:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHours/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before opens"

  Scenario: Cannot create an event when childcare.start is after opens
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "10:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHours/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before opens"

  Scenario: Cannot create an event when childcare.end equals closes
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "17:00"
          },
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHours/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after closes"

  Scenario: Cannot create an event when childcare.end is before closes
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "16:00"
          },
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHours/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after closes"

  Scenario: Cannot update calendar via PUT when opens is changed to make existing childcare.start invalid
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
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
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "08:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHours/0/childcare/start"
    And the JSON response at "schemaErrors/0/error" should be "childcare.start must be before opens"

  Scenario: Cannot update calendar via PUT when closes is changed to make existing childcare.end invalid
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
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
      "calendarType": "periodic",
      "startDate": "2021-05-01T00:00:00+00:00",
      "endDate": "2021-05-31T00:00:00+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "18:30",
          "childcare": {
            "start": "08:00",
            "end": "18:00"
          },
          "dayOfWeek": ["monday"]
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHours/0/childcare/end"
    And the JSON response at "schemaErrors/0/error" should be "childcare.end must be after closes"
