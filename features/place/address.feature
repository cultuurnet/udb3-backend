Feature: Test place address property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Update the address in dutch
    Given I set the JSON request payload to:
    """
    {
      "addressCountry": "BE",
      "addressLocality": "Brussel",
      "postalCode": "1000",
      "streetAddress": "Nieuwstraat 107"
    }
    """
    When I send a PUT request to "%{placeUrl}/address/nl"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry": "BE",
        "addressLocality": "Brussel",
        "postalCode": "1000",
        "streetAddress": "Nieuwstraat 107"
      }
    }
    """

  Scenario: Update the address in dutch via the legacy endpoint
    Given I set the JSON request payload to:
    """
    {
      "addressCountry": "BE",
      "addressLocality": "Brussel",
      "postalCode": "1000",
      "streetAddress": "Nieuwstraat 107"
    }
    """
    When I send a POST request to "%{placeUrl}/address/nl"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "addressCountry": "BE",
        "addressLocality": "Brussel",
        "postalCode": "1000",
        "streetAddress": "Nieuwstraat 107"
       }
    }
    """

  Scenario: Add an address in french
    Given I set the JSON request payload to:
    """
    {
      "addressCountry": "BE",
      "addressLocality": "Bruxelles",
      "postalCode": "1000",
      "streetAddress": "Rue Nouveau 107"
    }
    """
    When I send a PUT request to "%{placeUrl}/address/fr"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      },
      "fr": {
        "addressCountry": "BE",
        "addressLocality": "Bruxelles",
        "postalCode": "1000",
        "streetAddress": "Rue Nouveau 107"
      }
    }
    """

  Scenario: Add an address in french via legacy endpoint
    Given I set the JSON request payload to:
    """
    {
      "addressCountry": "BE",
      "addressLocality": "Bruxelles",
      "postalCode": "1000",
      "streetAddress": "Rue Nouveau 107"
    }
    """
    When I send a POST request to "%{placeUrl}/address/fr"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "address" should be:
    """
    {
      "nl" : {
        "addressCountry": "BE",
        "addressLocality": "Scherpenheuvel-Zichem",
        "postalCode": "3271",
        "streetAddress": "Hoornblaas 107"
      },
      "fr": {
        "addressCountry": "BE",
        "addressLocality": "Bruxelles",
        "postalCode": "1000",
        "streetAddress": "Rue Nouveau 107"
      }
    }
    """

  Scenario: When the request body is invalid an error is returned
    Given I set the JSON request payload to:
    """
    {
      "addressCountry": "BEL",
      "addressLocality": "Brussel",
      "postalCode": "1000",
      "streetAddress": "Nieuwstraat 107"
    }
    """
    When I send a PUT request to "%{placeUrl}/address/nl"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/addressCountry",
        "error":"Maximum string length is 2, found 3"
      }
    ]
    """
    And I get the place at "%{placeUrl}"
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
