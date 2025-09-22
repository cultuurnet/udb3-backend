Feature: Test organizer address property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal organizer and save the "url" as "organizerUrl"

  Scenario: Update organizer address in default language `nl` and then remove the address via address endpoints
    Given I set the JSON request payload to:
    """
    {"streetAddress":"Kerkstraat 2","addressLocality":"Leuven","postalCode":"3000","addressCountry":"BE"}
    """
    When I send a PUT request to "%{organizerUrl}/address/nl"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "address/nl/streetAddress" should be "Kerkstraat 2"
    And the JSON response at "address/nl/addressLocality" should be "Leuven"
    And the JSON response at "address/nl/postalCode" should be "3000"
    And the JSON response at "address/nl/addressCountry" should be "BE"
    When I send a DELETE request to "%{organizerUrl}/address"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "address"

  Scenario: Update organizer address in non-default language `fr` via address endpoint
    Given I set the JSON request payload to:
    """
    {"streetAddress":"Rue de l'Église 2","addressLocality":"Louvain","postalCode":"3000-FR","addressCountry":"FR"}
    """
    When I send a PUT request to "%{organizerUrl}/address/fr"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "address/fr/streetAddress" should be "Rue de l'Église 2"
    And the JSON response at "address/fr/addressLocality" should be "Louvain"
    And the JSON response at "address/fr/postalCode" should be "3000-FR"
    And the JSON response at "address/fr/addressCountry" should be "FR"

  Scenario: Update organizer address with missing language param via address endpoint
    Given I set the JSON request payload to:
    """
    {"streetAddress":"Nieuwstraat 10","addressLocality":"Brussel","postalCode":"1000","addressCountry":"NL"}
    """
    When I send a PUT request to "%{organizerUrl}/address/"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "address/nl/streetAddress" should be "Nieuwstraat 10"
    And the JSON response at "address/nl/addressLocality" should be "Brussel"
    And the JSON response at "address/nl/postalCode" should be "1000"
    And the JSON response at "address/nl/addressCountry" should be "NL"

  Scenario: Trying to update the address of a non-existing organizer via address endpoint
    Given I set the JSON request payload to:
    """
    {"streetAddress":"Nieuwstraat 10","addressLocality":"Brussel","postalCode":"1000","addressCountry":"NL"}
    """
    When I send a PUT request to "/organizers/139357a0-5e28-4c02-8fe5-d14b9f5791b7/address/nl"
    Then the response status should be "404"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/url/not-found",
     "title": "Not Found",
     "status": 404,
     "detail": "The Organizer with id \"139357a0-5e28-4c02-8fe5-d14b9f5791b7\" was not found."
    }
    """