Feature: Test place description property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update place description Dutch
    When I set the JSON request payload to:
    """
    { "description": "Updated description test_place in Dutch" }
    """
    And I send a PUT request to "%{placeUrl}/description/nl"
    Then the response status should be "204"
    And I set the JSON request payload to:
    """
    { "description": "Updated description test_place in English" }
    """
    And I send a PUT request to "%{placeUrl}/description/en"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "description/nl" should be:
    """
    "Updated description test_place in Dutch"
    """
    And the JSON response at "description/en" should be:
    """
    "Updated description test_place in English"
    """

  @bugfix # Relates to https://jira.uitdatabank.be/browse/III-5150
  # Right now the JSON response returns an empty string when the description is empty, it shouldn't return any value
  Scenario: It can remove a description by sending an empty description
    When I set the JSON request payload to:
    """
    { "description": "Updated description test_place in Dutch" }
    """
    And I send a PUT request to "%{placeUrl}/description/nl"
    Given I set the JSON request payload to:
    """
    { "description": "" }
    """
    And I send a PUT request to "%{placeUrl}/description/nl"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "description/nl" should be:
    """
    ""
    """

  Scenario: Delete a description of a place
    When I send a DELETE request to "%{placeUrl}/description/nl"
    Then the response status should be "204"
    And I send a GET request to "%{placeUrl}"
    Then the response status should be "200"
    And the JSON response should be:
    """
    {
      "@id": "http://host.docker.internal:8000/place/bc9f8c5d-cccd-4f32-9bfa-a0225ef82852",
      "@context": "/contexts/place",
      "mainLanguage": "nl",
      "name": {
          "nl": "Cafe Den Hemel"
      },
      "address": {
          "nl": {
              "addressCountry": "BE",
              "addressLocality": "Scherpenheuvel-Zichem",
              "postalCode": "3271",
              "streetAddress": "Hoornblaas 107"
          }
      },
      "calendarType": "periodic",
      "startDate": "2022-01-01T11:22:33+00:00",
      "endDate": "2032-01-01T11:22:33+00:00",
      "status": {
          "type": "Available"
      },
      "bookingAvailability": {
          "type": "Available"
      },
      "availableTo": "2032-01-01T11:22:33+00:00",
      "terms": [
          {
              "id": "ekdc4ATGoUitCa0e6me6xA",
              "label": "Horeca",
              "domain": "eventtype"
          }
      ],
      "created": "2023-07-24T13:53:13+00:00",
      "modified": "2023-07-28T11:12:38+00:00",
      "creator": "7a583ed3-cbc1-481d-93b1-d80fff0174dd",
      "workflowStatus": "DRAFT",
      "languages": [
          "nl",
          "fr"
      ],
      "completedLanguages": [
          "nl"
      ],
      "playhead": 7,
      "geo": {
          "latitude": 51.0156421,
          "longitude": 5.003877699999999
      },
      "description": {
          "nl": "",
          "fr": "Mijn eerste aanpassing"
      },
      "videos": [
          {
              "id": "6aeb18cb-6b00-4ae6-974d-cff88073d94a",
              "url": "https://www.youtube.com/shorts/pVMldM3PF-o",
              "embedUrl": "https://www.youtube.com/shorts/pVMldM3PF-o",
              "language": "nl",
              "copyrightHolder": "Koen"
          }
      ],
      "typicalAgeRange": "-"
  }
    """