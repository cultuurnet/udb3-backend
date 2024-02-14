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
    And the JSON response at "completeness" should be 40

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
    And the JSON response at "completeness" should be 60

  Scenario: Create a new organizer with long description
    Given I create an organizer from "organizers/organizer-with-long-description.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "description" should be:
    """
    {
      "nl": "This is a very long description of the organizer, it has more then 200 characters and because of that the description is taken into account for the completeness of the organizer. That makes this string difficult to read and understand."
    }
    """
    And the JSON response at "completeness" should be 55

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
    And the JSON response at "completeness" should be 70

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
    And the JSON response at "completeness" should be 40

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

  Scenario: I should not be able to create an organizer with a very long title
    Given I create a random name of 100 characters and keep it as "name"
    Given I create a random name of 10 characters and keep it as "url"
    # I had to create a new data file, because there is also a check on the length of the URL, which runs first
    Given I set the JSON request payload from "organizers/organizer-minimal-title-separate-from-url.json"
    When I send a POST request to "/organizers/"
    Then the response status should be "400"
    And the response body should be valid JSON
    Then the JSON response should be:
    """
    {
        "type": "https://api.publiq.be/probs/body/invalid-data",
        "title": "Invalid body data",
        "status": 400,
        "schemaErrors": [
            {
                "jsonPointer": "/title",
                "error": "Given string should not be longer than 90 characters."
            }
        ]
    }
    """