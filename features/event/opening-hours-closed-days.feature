@api @events
Feature: Test opening hours closed days

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Create a periodic event with single closed day
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met gesloten dag"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-25",
          "description": {
            "nl": "Gesloten op eerste kerstdag",
            "en": "Closed on Christmas Day",
            "fr": "Fermé le jour de Noël"
          }
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "openingHoursClosedDays/0/startDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/0/endDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/0/description/nl" should be "Gesloten op eerste kerstdag"
    And the JSON response at "openingHoursClosedDays/0/description/en" should be "Closed on Christmas Day"
    And the JSON response at "openingHoursClosedDays/0/description/fr" should be "Fermé le jour de Noël"

  Scenario: Create a periodic event with multiple closed days
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met meerdere gesloten dagen"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-01-01",
          "endDate": "2024-01-01",
          "description": {
            "nl": "Gesloten met Nieuwjaarsdag",
            "en": "Closed on New Year's Day",
            "fr": "Fermé le jour de l'an"
          }
        },
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-26",
          "description": {
            "nl": "Gesloten met kerstdagen",
            "en": "Closed on Christmas holidays",
            "fr": "Fermé pendant les vacances de Noël"
          }
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response should have "openingHoursClosedDays"
    And the JSON response at "openingHoursClosedDays/0/startDate" should be "2024-01-01"
    And the JSON response at "openingHoursClosedDays/0/description/nl" should be "Gesloten met Nieuwjaarsdag"
    And the JSON response at "openingHoursClosedDays/0/description/en" should be "Closed on New Year's Day"
    And the JSON response at "openingHoursClosedDays/1/startDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/1/endDate" should be "2024-12-26"
    And the JSON response at "openingHoursClosedDays/1/description/nl" should be "Gesloten met kerstdagen"
    And the JSON response at "openingHoursClosedDays/1/description/en" should be "Closed on Christmas holidays"

  Scenario: Create a permanent event with closed days
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Permanent event met gesloten dag"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "permanent",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-25",
          "description": {
            "nl": "Gesloten: Kerstdag",
            "en": "Closed: Christmas",
            "fr": "Fermé: Noël",
            "de": "Geschlossen: Weihnachten"
          }
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "openingHoursClosedDays/0/startDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/0/endDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/0/description/nl" should be "Gesloten: Kerstdag"
    And the JSON response at "openingHoursClosedDays/0/description/en" should be "Closed: Christmas"
    And the JSON response at "openingHoursClosedDays/0/description/fr" should be "Fermé: Noël"
    And the JSON response at "openingHoursClosedDays/0/description/de" should be "Geschlossen: Weihnachten"

  Scenario: Update periodic event with closed days via PUT /calendar
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
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
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-25",
          "description": {
            "nl": "Gesloten op eerste kerstdag",
            "en": "Closed on Christmas Day",
            "fr": "Fermé le jour de Noël"
          }
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "openingHoursClosedDays/0/startDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/0/endDate" should be "2024-12-25"
    And the JSON response at "openingHoursClosedDays/0/description/nl" should be "Gesloten op eerste kerstdag"
    And the JSON response at "openingHoursClosedDays/0/description/en" should be "Closed on Christmas Day"

  Scenario: Clear closed days when omitted from a PUT calendar update
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event met gesloten dag"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-25",
          "description": {
            "nl": "Gesloten op eerste kerstdag",
            "en": "Closed on Christmas Day"
          }
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
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "openingHoursClosedDays"

  Scenario: Cannot create event when closed day startDate is after endDate
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Invalid closed day"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-24"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "endDate should not be before startDate"

  Scenario: Cannot create periodic event when closed day is before calendar startDate
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Invalid closed day"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-03-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-01-01",
          "endDate": "2024-01-01"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/startDate"
    And the JSON response at "schemaErrors/0/error" should be "startDate should not be before the calendar startDate"

  Scenario: Cannot create periodic event when closed day is after calendar endDate
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Invalid closed day"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2025-01-01",
          "endDate": "2025-01-01"
        }
      ]
    }
    """
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "endDate should not be after the calendar endDate"

  Scenario: Cannot update calendar via PUT when closed day is invalid
    Given I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Periodiek event"},
      "terms": [{"id": "0.50.4.0.0", "label": "Concert", "domain": "eventtype"}],
      "location": {"@id": "%{placeUrl}"},
      "calendarType": "periodic",
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
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
      "startDate": "2024-01-01T00:00:00+00:00",
      "endDate": "2024-12-31T23:59:59+00:00",
      "openingHours": [
        {
          "opens": "09:00",
          "closes": "17:00",
          "dayOfWeek": ["monday"]
        }
      ],
      "openingHoursClosedDays": [
        {
          "startDate": "2024-12-25",
          "endDate": "2024-12-24"
        }
      ]
    }
    """
    And I send a PUT request to "%{eventUrl}/calendar"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/openingHoursClosedDays/0/endDate"
    And the JSON response at "schemaErrors/0/error" should be "endDate should not be before startDate"
