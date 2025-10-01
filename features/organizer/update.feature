Feature: Test updating organizers via complete overwrite

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Update an organizer with extra fields via complete overwrite
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I update the organizer at "%{organizerUrl}" from "organizers/organizer.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"
    And the JSON response at "description" should be:
    """
    {
      "nl": "Dutch description",
      "fr": "French description",
      "de": "German description",
      "en": "English description"
    }
    """
    And the JSON response at "educationalDescription" should be:
    """
    {
      "nl": "Dutch educational description",
      "fr": "French educational description",
      "de": "German educational description",
      "en": "English educational description"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "streetAddress": "Henegouwenkaai 41-43",
        "postalCode": "1080",
        "addressLocality": "Brussel",
        "addressCountry": "BE"
      },
      "fr": {
        "streetAddress": "Quai du Hainaut 41-43",
        "postalCode": "1080",
        "addressLocality": "Bruxelles",
        "addressCountry": "BE"
      }
    }
    """
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "123",
        "456"
      ],
      "email": [
        "mock@publiq.be"
      ],
      "url": [
        "https://www.publiq.be",
        "https://www.madewithlove.be"
      ]
    }
    """

    When I update the organizer at "%{organizerUrl}" from "organizers/organizer-updated.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name} UPDATED"
    And the JSON response at "url" should be "https://www.%{name}-updated.be"
    And the JSON response at "description" should be:
    """
    {
      "nl": "Dutch description UPDATED",
      "fr": "French description UPDATED",
      "de": "German description UPDATED",
      "en": "English description UPDATED"
    }
    """
    And the JSON response at "educationalDescription" should be:
    """
    {
      "nl": "Dutch educational description UPDATED",
      "fr": "French educational description UPDATED",
      "de": "German educational description UPDATED",
      "en": "English educational description UPDATED"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "streetAddress": "Henegouwenkaai 41-43",
        "postalCode": "1080",
        "addressLocality": "Brussel",
        "addressCountry": "BE"
      },
      "fr": {
        "streetAddress": "Quai du Hainaut 41-43 UPDATED",
        "postalCode": "1080 UPDATED",
        "addressLocality": "Bruxelles UPDATED",
        "addressCountry": "NL"
      }
    }
    """
    And the JSON response at "labels" should be:
    """
    [ "foo-updated" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "123",
        "456 UPDATED"
      ],
      "email": [
        "mock@publiq.be",
        "updated@publiq.be"
      ],
      "url": [
        "https://www.publiq.be",
        "https://www.updated.be"
      ]
    }
    """

  Scenario: Update an organizer with extra fields via complete overwrite via old imports path
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I update the organizer at "%{organizerUrl}" from "organizers/organizer.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"
    And the JSON response at "description" should be:
    """
    {
      "nl": "Dutch description",
      "fr": "French description",
      "de": "German description",
      "en": "English description"
    }
    """
    And the JSON response at "educationalDescription" should be:
    """
    {
      "nl": "Dutch educational description",
      "fr": "French educational description",
      "de": "German educational description",
      "en": "English educational description"
    }
    """
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "streetAddress": "Henegouwenkaai 41-43",
        "postalCode": "1080",
        "addressLocality": "Brussel",
        "addressCountry": "BE"
      },
      "fr": {
        "streetAddress": "Quai du Hainaut 41-43",
        "postalCode": "1080",
        "addressLocality": "Bruxelles",
        "addressCountry": "BE"
      }
    }
    """
    And the JSON response at "labels" should be:
    """
    [ "public-visible" ]
    """
    And the JSON response at "hiddenLabels" should be:
    """
    [ "public-invisible" ]
    """
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [
        "123",
        "456"
      ],
      "email": [
        "mock@publiq.be"
      ],
      "url": [
        "https://www.publiq.be",
        "https://www.madewithlove.be"
      ]
    }
    """

  Scenario: Remove an organizer's optional properties via complete overwrite
    Given I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    When I update the organizer at "%{organizerUrl}" from "organizers/organizer-minimal.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "description"
    And the JSON response should not have "address"
    And the JSON response should not have "labels"
    And the JSON response should not have "hiddenLabels"
    And the JSON response at "contactPoint" should be:
    """
    {
      "phone": [],
      "email": [],
      "url": []
    }
    """

  Scenario: Trying to update an organizer that does not exist
    Given I am authorized as JWT provider user "invoerder_lgm"
    And I set the JSON request payload to:
    """
        {"name": "madewithlove"}
    """
    And I send a PUT request to "/organizers/139357a0-5e28-4c02-8fe5-d14b9f5791b7/name/nl"
    And the response status should be "404"
