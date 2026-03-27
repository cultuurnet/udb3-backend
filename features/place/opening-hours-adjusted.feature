@api @places
Feature: Test that places do not support opening hours adjusted

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Places ignore opening hours adjusted in the request
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
            "id": "Yf4aZBfsUEu2NsQqsprngw"
        }
    ],
    "address": {
        "nl": {
            "streetAddress": "Teststraat 1",
            "postalCode": "3000",
            "addressLocality": "Leuven",
            "addressCountry": "BE"
        }
    },
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
    And I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
    Then the JSON response should not have "openingHoursAdjusted"
