Feature: Test updating places

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update required properties of place via legacy major-info endpoint
    When I set the JSON request payload to:
    """
    {
      "name": "Updated title",
      "type": {
        "id": "OyaPaf64AEmEAYXHeLMAtA",
        "label": "Zaal of expohal"
      },
      "address": {
        "addressCountry": "BE",
        "addressLocality": "Leuven",
        "postalCode": "3000",
        "streetAddress": "Bondgenotenlaan 1"
      },
      "calendar": {
        "type": "periodic",
        "startDate": "2020-01-26T11:11:11+01:00",
        "endDate": "2020-01-27T12:12:12+01:00"
      }
    }
    """
    And I send a PUT request to "%{placeUrl}/majorInfo"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "name/nl" should be "Updated title"
    And the JSON response at "calendarType" should be "periodic"
    And the JSON response at "terms/0/id" should be "OyaPaf64AEmEAYXHeLMAtA"
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry": "BE",
        "addressLocality": "Leuven",
        "postalCode": "3000",
        "streetAddress": "Bondgenotenlaan 1"
      }
    }
    """

  Scenario: Update a place with extra fields via complete overwrite
    When I update the place at "%{placeUrl}" from "places/place-with-all-fields.json"
    And I get the place at "%{placeUrl}"
    Then the JSON response at "name" should be:
    """
    {
      "nl":"%{name}"
    }
    """
    And the JSON response at "terms" should be:
    """
    [
      {
        "id": "Yf4aZBfsUEu2NsQqsprngw",
        "domain": "eventtype",
        "label": "Cultuur- of ontmoetingscentrum"
      }
    ]
    """
    And the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "workflowStatus" should be "DRAFT"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "status" should be:
    """
    {
      "type": "Unavailable",
      "reason": {
        "nl": "We zijn nog steeds gesloten."
      }
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "priceInfo" should be:
    """
    [
      {
        "category": "base",
        "price": 10.5,
        "priceCurrency": "EUR",
        "name": {
          "nl": "Basistarief",
          "de": "Basisrate",
          "en": "Base tariff",
          "fr": "Tarif de base"
        }
      }
    ]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "016 10 20 30"
      ],
      "email": [
        "info@denhemel.be"
      ],
      "url": [
        "https://www.denhemel.be"
      ]
    }
    """
    And the JSON response at "bookingInfo" should be:
    """
    {
      "phone": "016 10 20 30",
      "email": "booking@denhemel.be",
      "url": "https://www.denhemel.be/booking",
      "urlLabel": {
        "nl": "Bestel hier je tickets"
      },
      "availabilityStarts": "2020-05-17T22:00:00+00:00",
      "availabilityEnds": "2028-05-17T22:00:00+00:00"
    }
    """
    And the JSON response at "videos/0" should be:
    """
    {
      "id": "b504cf44-9ab8-4641-9934-38d1cc67242c",
      "url": "https://www.youtube.com/watch?v=cEItmb_a20D",
      "embedUrl": "https://www.youtube.com/embed/cEItmb_a20D",
      "language": "nl",
      "copyrightHolder": "Den Hemel"
    }
    """
    And the JSON response at "videos/1" should be:
    """
    {
      "id": "5c549a24-bb97-4f83-8ea5-21a6d56aff72",
      "url": "https://vimeo.com/98765432",
      "embedUrl": "https://player.vimeo.com/video/98765432",
      "language": "nl",
      "copyrightHolder": "Copyright afgehandeld door Vimeo"
    }
    """
    And the JSON response at "labels" should be:
    """
    [
      "public-visible"
    ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [
      "public-invisible"
    ]
    """

  Scenario: Update a place with extra fields using legacy imports path
    Given I create a place from "places/place.json" and save the "id" as "placeId"
    When I set the JSON request payload from "places/place-with-all-fields.json"
    And I send a PUT request to "/imports/places/%{placeId}"
    Then the response status should be "200"
    When I send a GET request to "places/%{placeId}"
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "name" should be:
    """
    {
      "nl":"%{name}"
    }
    """
    And the JSON response at "terms" should be:
    """
    [
      {
        "id": "Yf4aZBfsUEu2NsQqsprngw",
        "domain": "eventtype",
        "label": "Cultuur- of ontmoetingscentrum"
      }
    ]
    """
    And the JSON response at "terms/0/id" should be "Yf4aZBfsUEu2NsQqsprngw"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      }
    }
    """
    And the JSON response at "workflowStatus" should be "READY_FOR_VALIDATION"
    And the JSON response at "calendarType" should be "permanent"
    And the JSON response at "status" should be:
    """
    {
      "type": "Unavailable",
      "reason": {
        "nl": "We zijn nog steeds gesloten."
      }
    }
    """
    And the JSON response at "bookingAvailability" should be:
    """
    {
      "type": "Available"
    }
    """
    And the JSON response at "priceInfo" should be:
    """
    [
      {
        "category": "base",
        "price": 10.5,
        "priceCurrency": "EUR",
        "name": {
          "nl": "Basistarief",
          "de": "Basisrate",
          "en": "Base tariff",
          "fr": "Tarif de base"
        }
      }
    ]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "016 10 20 30"
      ],
      "email": [
        "info@denhemel.be"
      ],
      "url": [
        "https://www.denhemel.be"
      ]
    }
    """
    And the JSON response at "bookingInfo" should be:
    """
    {
      "phone": "016 10 20 30",
      "email": "booking@denhemel.be",
      "url": "https://www.denhemel.be/booking",
      "urlLabel": {
        "nl": "Bestel hier je tickets"
      },
      "availabilityStarts": "2020-05-17T22:00:00+00:00",
      "availabilityEnds": "2028-05-17T22:00:00+00:00"
    }
    """
    And the JSON response at "videos/0" should be:
    """
    {
      "id": "b504cf44-9ab8-4641-9934-38d1cc67242c",
      "url": "https://www.youtube.com/watch?v=cEItmb_a20D",
      "embedUrl": "https://www.youtube.com/embed/cEItmb_a20D",
      "language": "nl",
      "copyrightHolder": "Den Hemel"
    }
    """
    And the JSON response at "videos/1" should be:
    """
    {
      "id": "5c549a24-bb97-4f83-8ea5-21a6d56aff72",
      "url": "https://vimeo.com/98765432",
      "embedUrl": "https://player.vimeo.com/video/98765432",
      "language": "nl",
      "copyrightHolder": "Copyright afgehandeld door Vimeo"
    }
    """
    And the JSON response at "labels" should be:
    """
    [
      "public-visible"
    ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [
      "public-invisible"
    ]
    """
