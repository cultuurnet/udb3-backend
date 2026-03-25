@api @places
Feature: Test that places do not support opening hours closed days

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Places ignore opening hours closed days in the request
    When I set the JSON request payload to:
    """
    {
      "mainLanguage": "nl",
      "name": {"nl": "Museum met gesloten dagen"},
      "terms": [{"id": "8.6.0.0.0", "label": "Museum", "domain": "eventtype"}],
      "address": {
        "nl": {
          "street": "Teststraat 1",
          "postalCode": "3000",
          "municipality": "Leuven",
          "countryCode": "BE"
        }
      },
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
            "nl": "Gesloten op kerstdag"
          }
        }
      ]
    }
    """
    And I send a POST request to "/places/"
    Then the response status should be "400"
    And the JSON response should have "schemaErrors"
