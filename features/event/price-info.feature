Feature: Test event priceInfo property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

    Given I am using the UDB3 base URL
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create an event from "events/event-minimal-permanent.json" and save the "url" as "eventUrl"

  Scenario: Updating an event with correct price info via the legacy camelCase path
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
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{eventUrl}/priceInfo"
    Then the response status should be "204"
    When I get the place at "%{eventUrl}"
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
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4769
  Scenario: Updating an event with correct price info without a currency via the kebab-case path
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
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5
      }
    ]
    """
    And I send a PUT request to "%{eventUrl}/price-info"
    Then the response status should be "204"
    When I get the place at "%{eventUrl}"
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
      },
      {
       "category": "tariff",
       "name": {
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """

  Scenario: Try updating an event without a base tariff
    When I set the JSON request payload to:
    """
    [
      {
       "category": "tariff",
       "name": {
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{eventUrl}/priceInfo"
    Then the response status should be "400"
    Then the JSON response should be:
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

  Scenario: Try updating an event without a duplicate tariff
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
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
       },
       {
       "category": "tariff",
       "name": {
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{eventUrl}/priceInfo"
    Then the response status should be "400"
    Then the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Korting\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/nl"
        },
        {
          "error": "Tariff name \"Reduction\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/fr"
        },
        {
          "error": "Tariff name \"Reduction\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/en"
        },
        {
          "error": "Tariff name \"Rabatt\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/de"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try updating an event without a duplicate tariff in different spacing
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
         "nl": "Korting",
         "fr": "Reduction",
         "en": "Reduction",
         "de": "Rabatt"
       },
       "price": 5,
       "priceCurrency": "EUR"
       },
       {
       "category": "tariff",
       "name": {
         "nl": "Korting ",
         "fr": "Reduction ",
         "en": "Reduction ",
         "de": "Rabatt "
       },
       "price": 3,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{eventUrl}/priceInfo"
    Then the response status should be "400"
    Then the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Korting\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/nl"
        },
        {
          "error": "Tariff name \"Reduction\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/fr"
        },
        {
          "error": "Tariff name \"Reduction\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/en"
        },
        {
          "error": "Tariff name \"Rabatt\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/de"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try creating an event duplicate tariff names
    Given I set the JSON request payload from "events/price-info/event-with-duplicate-tariff-names.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "Tariff name \"Reductie\" must be unique.",
          "jsonPointer": "/priceInfo/2/name/nl"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try creating an event without a base tariff
    Given I set the JSON request payload from "events/price-info/event-without-base-tariff.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "At least 1 array items must match schema",
          "jsonPointer": "/priceInfo"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Try creating an event without a tariff in the mainlanguage
    Given I set the JSON request payload from "events/price-info/event-without-main-language.json"
    When I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "schemaErrors": [
        {
          "error": "A value in the mainLanguage (nl) is required.",
          "jsonPointer": "/priceInfo/1/name"
        },
        {
          "error": "A value in the mainLanguage (nl) is required.",
          "jsonPointer": "/priceInfo/2/name"
        }
      ],
      "status": 400,
      "title": "Invalid body data",
      "type": "https://api.publiq.be/probs/body/invalid-data"
    }
    """

  Scenario: Update event without a base tariff
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
    And I send a PUT request to "%{eventUrl}/priceInfo"
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

  Scenario: Update event with an UiTPAS price category
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
       "price": 5,
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
      },
      {
       "category": "uitpas",
       "name": {
         "nl": "UiTPAS Regio Gent"
       },
       "price": 5,
       "priceCurrency": "EUR"
      }
    ]
    """
    And I send a PUT request to "%{eventUrl}/priceInfo"
    Then the response status should be "204"
    When I get the event at "%{eventUrl}"
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
