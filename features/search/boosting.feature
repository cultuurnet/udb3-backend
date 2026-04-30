@sapi3
Feature: Test the Search API v3 boosting

  # Search terms are suffixed with a random string to ensure they are unique per test run.
  # Without this, documents from previous runs accumulate on the same ES shard and inflate
  # the docFreq for those terms, which lowers IDF and can cause the TF-based score ordering
  # to break unpredictably.

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "id" as "placeId"
    And I publish the place at "/places/%{placeId}"

  @testIsolation
  Scenario: I can positively boost events
    Given I create a random labelname of 10 characters
    When I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "termBoostedEvent"
    And I set the JSON request payload to:
    """
    {"name": "kerst%{labelname} sneeuw%{labelname}"}
    """
    And I send a PUT request to "/events/%{termBoostedEvent}/name/nl"
    When I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "termNaturalEvent"
    And I set the JSON request payload to:
    """
    {"name": "kerst%{labelname} kerst%{labelname}"}
    """
    And I send a PUT request to "/events/%{termNaturalEvent}/name/nl"
    When I am using the Search API v3 base URL
    And I wait for 2 results at "/events" with parameters:
      | text | kerst%{labelname} |
    And I send a GET request to "/events" with parameters:
      | text        | kerst%{labelname}                 |
      | sort[score] | desc                              |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termNaturalEvent}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termBoostedEvent}",
        "@type": "Event"
      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | text        | kerst%{labelname}                              |
      | q           | (sneeuw%{labelname}^10) OR (NOT sneeuw%{labelname}) |
      | sort[score] | desc                                           |
    Then the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termBoostedEvent}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termNaturalEvent}",
        "@type": "Event"
      }
    ]
    """

  @testIsolation
  Scenario: I can positively boost places
    Given I create a random labelname of 10 characters
    When I create a minimal place and save the "id" as "termBoostedPlace"
    And I set the JSON request payload to:
    """
    {"name": "kerst%{labelname} sneeuw%{labelname}"}
    """
    And I send a PUT request to "/places/%{termBoostedPlace}/name/nl"
    And I publish the place at "/places/%{termBoostedPlace}"
    When I create a minimal place and save the "id" as "termNaturalPlace"
    And I set the JSON request payload to:
    """
    {"name": "kerst%{labelname} kerst%{labelname}"}
    """
    And I send a PUT request to "/places/%{termNaturalPlace}/name/nl"
    And I publish the place at "/places/%{termNaturalPlace}"
    When I am using the Search API v3 base URL
    And I wait for 2 results at "/places" with parameters:
      | text | kerst%{labelname} |
    And I send a GET request to "/places" with parameters:
      | text        | kerst%{labelname}                 |
      | sort[score] | desc                              |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/places/%{termNaturalPlace}",
        "@type": "Place"
      },
      {
        "@id": "http://io.uitdatabank.local:80/places/%{termBoostedPlace}",
        "@type": "Place"
      }
    ]
    """
    When I send a GET request to "/places" with parameters:
      | text        | kerst%{labelname}                                   |
      | q           | (sneeuw%{labelname}^10) OR (NOT sneeuw%{labelname}) |
      | sort[score] | desc                                                |
    Then the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/places/%{termBoostedPlace}",
        "@type": "Place"
      },
      {
        "@id": "http://io.uitdatabank.local:80/places/%{termNaturalPlace}",
        "@type": "Place"
      }
    ]
    """

  @testIsolation
  Scenario: I can positively boost offers
    Given I create a random labelname of 10 characters
    When I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "termBoostedOffer"
    And I set the JSON request payload to:
    """
    {"name": "kerst%{labelname} sneeuw%{labelname}"}
    """
    And I send a PUT request to "/events/%{termBoostedOffer}/name/nl"
    When I create a minimal place and save the "id" as "termNaturalOffer"
    And I set the JSON request payload to:
    """
    {"name": "kerst%{labelname} kerst%{labelname}"}
    """
    And I send a PUT request to "/places/%{termNaturalOffer}/name/nl"
    And I publish the place at "/places/%{termNaturalOffer}"
    When I am using the Search API v3 base URL
    And I wait for 2 results at "/offers" with parameters:
      | text | kerst%{labelname} |
    And I send a GET request to "/offers" with parameters:
      | text        | kerst%{labelname}                 |
      | sort[score] | desc                              |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/places/%{termNaturalOffer}",
        "@type": "Place"
      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termBoostedOffer}",
        "@type": "Event"
      }
    ]
    """
    When I send a GET request to "/offers" with parameters:
      | text        | kerst%{labelname}                                   |
      | q           | (sneeuw%{labelname}^10) OR (NOT sneeuw%{labelname}) |
      | sort[score] | desc                                                |
    Then the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termBoostedOffer}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/places/%{termNaturalOffer}",
        "@type": "Place"
      }
    ]
    """
