Feature: Test creating organizers
  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Create a new organizer with minimal properties
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"

  Scenario: Create a new organizer with missing contact point fields
    Given I create an organizer from "organizers/organizer-contact-point-missing-fields.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "contactPoint" should be:
    """
    {
      "phone": [],
      "email": ["info@publiq.be"],
      "url": []
    }
    """

  Scenario: Create a new organizer with all properties
    Given I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"
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

  @bugfix # https://jira.uitdatabank.be/browse/III-4669
  Scenario: Create a new organizer with all properties and remove them with null values or empty lists in the JSON
    When I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-minimal-with-null-or-empty-values.json"
    And I get the organizer at "%{organizerUrl}"
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

  Scenario: Create a new organizer with an existing url
    Given I create a random name of 10 characters
    And I create an organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    And I set the JSON request payload from "organizers/organizer.json"
    When I send a POST request to "/organizers/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
     "type": "https://api.publiq.be/probs/uitdatabank/duplicate-url",
     "title": "Duplicate URL",
     "status": 400,
     "detail": "The url https://www.%{name}.be (normalized to %{name}.be) is already in use."
    }
    """

  Scenario: Create a new organizer with minimal properties from the legacy schema
    Given I create an organizer from "organizers/legacy/create-organizer-with-required-properties.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"

  Scenario: Create a new organizer with all properties from the legacy schema
    Given I create an organizer from "organizers/legacy/create-organizer-with-all-properties.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"
    And the JSON response at "address" should be:
    """
    {
      "nl": {
        "streetAddress": "Henegouwenkaai 41-43",
        "postalCode": "1080",
        "addressLocality": "Brussel",
        "addressCountry": "BE"
      }
    }
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

  Scenario: Create a new organizer with minimal properties via the old imports path
    Given I import a new organizer from "organizers/organizer-minimal.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"

  Scenario: Create a new organizer with all properties via the old imports path
    Given I import a new organizer from "organizers/organizer.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "@id" should be "%{organizerUrl}"
    And the JSON response at "mainLanguage" should be "nl"
    And the JSON response at "name/nl" should be "%{name}"
    And the JSON response at "url" should be "https://www.%{name}.be"
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