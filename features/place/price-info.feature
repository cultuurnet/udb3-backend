@api @places
Feature: Test place priceInfo property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update a place price info via the legacy camelCase path
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "priceInfo" should be:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10,
       "priceCurrency": "EUR"
      }
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4769
  Scenario: Update a place price info without a currency via the kebab-case path
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/price-info"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "priceInfo" should be:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10,
       "priceCurrency": "EUR"
      }
    ]
    """

  Scenario: Try updating a place price info with duplicate tariff
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Kinderen",
         "fr": "Enfants",
         "en": "Children",
         "de": "Kinder"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Kinderen",
         "fr": "Enfants",
         "en": "Children",
         "de": "Kinder"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Kinderen\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/nl"
        },
        {
          "error": "Tariff name \"Enfants\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/fr"
        },
        {
          "error": "Tariff name \"Children\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/en"
        },
        {
          "error": "Tariff name \"Kinder\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/de"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try Updating a place price with duplicate in 1 language
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Kinderen",
         "fr": "Enfants",
         "en": "Children",
         "de": "Kinder"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Kleuters",
         "fr": "Enfants",
         "en": "Preschoolers",
         "de": "Vorschulkinder"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Enfants\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/fr"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try Updating a place price with duplicate with different spacing
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Early Birds"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Early Birds "
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Early Birds\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/nl"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating a place price as a string
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Kinderen",
         "fr": "Enfants",
         "en": "Children",
         "de": "Kinder"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Kinderen",
         "fr": "Enfants",
         "en": "Children",
         "de": "Kinder"
       },
       "price": "Vijf",
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "The data (string) must match the type: number",
          "jsonPointer": "/2/price"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating a place without a base tariff
    When I set the JSON request payload to:
    """
    [
      {
       "category": "tariff",
       "name": {
         "nl": "Kinderen",
         "fr": "Enfants",
         "en": "Children",
         "de": "Kinder"
       },
       "price": 10,
       "priceCurrency": "EUR"
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Senioren",
         "fr": "Anciens",
         "en": "Elderly",
         "de": "Alter"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "At least 1 array items must match schema",
          "jsonPointer": "/"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Create an place with correct tariff names
    Given I set the JSON request payload from "places/place-with-correct-tariffs.json"
    When I send a POST request to "/places/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "id" as "placeId"
    And I send a GET request to "places/%{placeId}"
    Then the response status should be "200"
    And the JSON response at "priceInfo" should be:
    """
    [
      {
        "category":"base",
        "name":{
          "nl":"Basistarief",
          "fr":"Tarif de base",
          "en":"Base tariff",
          "de":"Basisrate"
        },
        "price":5,
        "priceCurrency":"EUR"
      },
      {
        "category":"tariff",
        "name":{
          "nl":"Kinderen"
        },
        "price":1,
        "priceCurrency":"EUR"
      },
      {
        "category":"tariff",
        "name":{
          "nl":"Senioren"
        },
        "price":3,
        "priceCurrency":"EUR"
      }
    ]
    """

  Scenario: Update place with group price category
    When I set the JSON request payload to:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 300,
       "priceCurrency": "EUR",
       "groupPrice": true
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Individuen",
         "fr": "Individus",
         "en": "Individuals",
         "de": "Einzelpersonen"
       },
       "price": 20,
       "priceCurrency": "EUR",
       "groupPrice": true
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Leraren",
         "fr": "Enseignants",
         "en": "Teachers",
         "de": "Lehrer"
       },
       "price": 150,
       "priceCurrency": "EUR",
       "groupPrice": false
      }
    ]
    """
    And I send a PUT request to "%{placeUrl}/priceInfo"
    Then the response status should be "204"
    When I get the place at "%{placeUrl}"
    Then the JSON response at "priceInfo" should be:
    """
    [
      {
       "category": "base",
       "name": {
         "nl": "Basistarief",
         "fr": "Tarif de base",
         "en": "Base tariff",
         "de": "Basisrate"
       },
       "price": 300,
       "priceCurrency": "EUR",
       "groupPrice": true
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Individuen",
         "fr": "Individus",
         "en": "Individuals",
         "de": "Einzelpersonen"
       },
       "price": 20,
       "priceCurrency": "EUR",
       "groupPrice": true
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Leraren",
         "fr": "Enseignants",
         "en": "Teachers",
         "de": "Lehrer"
       },
       "price": 150,
       "priceCurrency": "EUR"
      }
    ]
    """

  Scenario: Try creating an place with duplicate tariff names
    Given I set the JSON request payload from "places/place-with-duplicate-tariff-names.json"
    When I send a POST request to "/places/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Kinderen\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/nl"
        },
        {
          "error": "Tariff name \"Kinderen\" must be unique.",
          "jsonPointer": "/priceInfo/3/name/nl"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """
