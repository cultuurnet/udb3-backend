Feature: Test nil location

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Get the nil location
    When I send a GET request to "/places/00000000-0000-0000-0000-000000000000"
    Then the response status should be "200"
    And the JSON response at "@type" should be "Place"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name" should be:
    """
    {
      "nl": "Online"
    }
    """
    And the JSON response at "terms" should be:
    """
    [
      {
        "id":"0.8.0.0.0",
        "label":"Openbare ruimte",
        "domain":"eventtype"
      }
    ]
    """
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "status" should be:
    """
    {
      "type":"Available"
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type":"Available"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry":"BE",
        "addressLocality":"___",
        "postalCode":"0000",
        "streetAddress":"___"
      }
    }
    """
    And the JSON response at "typicalAgeRange" should be "-"
