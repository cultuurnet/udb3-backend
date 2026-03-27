@api @events
Feature: Test opening hours adjusted for events

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create periodic event with opening hours adjusted
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
{
    "mainLanguage": "nl",
    "name": {
        "nl": "%{name}"
    },
    "terms": [
        {
            "id": "0.50.6.0.0.0.0.0.0.0"
        }
    ],
    "calendarType": "periodic",
    "startDate": "2026-01-01T00:00:00+00:00",
    "endDate": "2026-12-31T23:59:59+00:00",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday"
            ]
        }
    ],
    "openingHoursAdjusted": [
        {
            "startDate": "2026-12-21",
            "endDate": "2026-12-26",
            "description": {
                "nl": "Kerstvakantie",
                "fr": "fêtes de Noël"
            },
            "openingHours": [
                {
                    "opens": "13:00",
                    "closes": "15:00",
                    "dayOfWeek": [
                        "friday",
                        "saturday",
                        "sunday"
                    ]
                }
            ]
        },
        {
            "startDate": "2026-12-27",
            "endDate": "2027-01-03",
            "openingHours": [
                {
                    "opens": "14:00",
                    "closes": "16:00",
                    "dayOfWeek": [
                        "saturday",
                        "sunday"
                    ]
                }
            ]
        }
    ]
}
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjusted"
    And the JSON response at "openingHoursAdjusted" should have 2 items
    And the JSON response at "openingHoursAdjusted/0/startDate" should be "2026-12-21"
    And the JSON response at "openingHoursAdjusted/0/endDate" should be "2026-12-26"
    And the JSON response at "openingHoursAdjusted/0/description/nl" should be "Kerstvakantie"
    And the JSON response at "openingHoursAdjusted/0/description/fr" should be "fêtes de Noël"
    And the JSON response at "openingHoursAdjusted/0/openingHours" should have 1 item
    And the JSON response at "openingHoursAdjusted/1/startDate" should be "2026-12-27"
    And the JSON response at "openingHoursAdjusted/1/endDate" should be "2027-01-03"

  Scenario: Create permanent event with opening hours adjusted
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
{
    "mainLanguage": "nl",
    "name": {
        "nl": "%{name}"
    },
    "terms": [
        {
            "id": "0.50.6.0.0.0.0.0.0.0"
        }
    ],
    "calendarType": "permanent",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday",
                "saturday",
                "sunday"
            ]
        }
    ],
    "openingHoursAdjusted": [
        {
            "startDate": "2026-12-21",
            "endDate": "2027-01-03",
            "description": {
                "nl": "Kerstvakantie"
            },
            "openingHours": [
                {
                    "opens": "13:00",
                    "closes": "15:00",
                    "dayOfWeek": [
                        "friday",
                        "saturday",
                        "sunday"
                    ]
                }
            ]
        }
    ]
}
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjusted"
    And the JSON response at "openingHoursAdjusted" should have 1 item
    And the JSON response at "openingHoursAdjusted/0/startDate" should be "2026-12-21"
    And the JSON response at "openingHoursAdjusted/0/endDate" should be "2027-01-03"
    And the JSON response at "openingHoursAdjusted/0/description/nl" should be "Kerstvakantie"

  Scenario: Update event calendar to add opening hours adjusted
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
{
    "mainLanguage": "nl",
    "name": {
        "nl": "%{name}"
    },
    "terms": [
        {
            "id": "0.50.6.0.0.0.0.0.0.0"
        }
    ],
    "calendarType": "periodic",
    "startDate": "2026-01-01T00:00:00+00:00",
    "endDate": "2026-12-31T23:59:59+00:00",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday"
            ]
        }
    ]
}
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "@id" as "eventId"
    And I set the JSON request payload to:
    """
{
    "calendarType": "periodic",
    "startDate": "2026-01-01T00:00:00+00:00",
    "endDate": "2026-12-31T23:59:59+00:00",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday"
            ]
        }
    ],
    "openingHoursAdjusted": [
        {
            "startDate": "2026-12-21",
            "endDate": "2026-12-26",
            "openingHours": [
                {
                    "opens": "13:00",
                    "closes": "15:00",
                    "dayOfWeek": [
                        "friday",
                        "saturday",
                        "sunday"
                    ]
                }
            ]
        }
    ]
}
    """
    And I send a PUT request to "/events/%{eventId}/calendar"
    Then the response status should be "204"
    And I get the event at "/events/%{eventId}"
    Then the JSON response should have "openingHoursAdjusted"
    And the JSON response at "openingHoursAdjusted" should have 1 item
    And the JSON response at "openingHoursAdjusted/0/startDate" should be "2026-12-21"

  Scenario: Clear opening hours adjusted by updating calendar without the field
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
{
    "mainLanguage": "nl",
    "name": {
        "nl": "%{name}"
    },
    "terms": [
        {
            "id": "0.50.6.0.0.0.0.0.0.0"
        }
    ],
    "calendarType": "periodic",
    "startDate": "2026-01-01T00:00:00+00:00",
    "endDate": "2026-12-31T23:59:59+00:00",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday"
            ]
        }
    ],
    "openingHoursAdjusted": [
        {
            "startDate": "2026-12-21",
            "endDate": "2026-12-26",
            "openingHours": [
                {
                    "opens": "13:00",
                    "closes": "15:00",
                    "dayOfWeek": [
                        "friday"
                    ]
                }
            ]
        }
    ]
}
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "@id" as "eventId"
    And I get the event at "/events/%{eventId}"
    Then the JSON response should have "openingHoursAdjusted"
    And I set the JSON request payload to:
    """
{
    "calendarType": "periodic",
    "startDate": "2026-01-01T00:00:00+00:00",
    "endDate": "2026-12-31T23:59:59+00:00",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday"
            ]
        }
    ]
}
    """
    And I send a PUT request to "/events/%{eventId}/calendar"
    Then the response status should be "204"
    And I get the event at "/events/%{eventId}"
    Then the JSON response should not have "openingHoursAdjusted"

  Scenario: Opening hours adjusted with childcare
    When I create a random name of 10 characters
    And I set the JSON request payload to:
    """
{
    "mainLanguage": "nl",
    "name": {
        "nl": "%{name}"
    },
    "terms": [
        {
            "id": "0.50.6.0.0.0.0.0.0.0"
        }
    ],
    "calendarType": "periodic",
    "startDate": "2026-01-01T00:00:00+00:00",
    "endDate": "2026-12-31T23:59:59+00:00",
    "openingHours": [
        {
            "opens": "09:00",
            "closes": "17:00",
            "dayOfWeek": [
                "monday",
                "tuesday",
                "wednesday",
                "thursday",
                "friday"
            ]
        }
    ],
    "openingHoursAdjusted": [
        {
            "startDate": "2026-12-21",
            "endDate": "2026-12-26",
            "openingHours": [
                {
                    "opens": "13:00",
                    "closes": "15:00",
                    "dayOfWeek": [
                        "friday"
                    ],
                    "childcare": {
                        "start": "13:30",
                        "end": "14:30"
                    }
                }
            ]
        }
    ]
}
    """
    And I send a POST request to "/events/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response should have "openingHoursAdjusted"
    And the JSON response at "openingHoursAdjusted/0/openingHours/0/childcare/start" should be "13:30"
    And the JSON response at "openingHoursAdjusted/0/openingHours/0/childcare/end" should be "14:30"
