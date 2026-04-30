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
    When I create an event with name "kerst%{labelname} sneeuw%{labelname}" and save the "id" as "termBoostedEvent"
    And I create an event with name "kerst%{labelname} kerst%{labelname}" and save the "id" as "termNaturalEvent"
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
    When I create a place with name "kerst%{labelname} sneeuw%{labelname}" and save the "id" as "termBoostedPlace"
    And I publish the place at "/places/%{termBoostedPlace}"
    When I create a place with name "kerst%{labelname} kerst%{labelname}" and save the "id" as "termNaturalPlace"
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
    When I create an event with name "kerst%{labelname} sneeuw%{labelname}" and save the "id" as "termBoostedOffer"
    When I create a place with name "kerst%{labelname} kerst%{labelname}" and save the "id" as "termNaturalOffer"
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

  # Negative boosting uses the pattern: (term^0.1) OR (NOT term)
  # - Documents WITHOUT the term match the NOT branch and receive a baseline score of ~1.0.
  # - Documents WITH the term match the (term^0.1) branch and receive only a tiny score addition.
  # - Because the ~1.0 baseline exceeds any realistic (term^0.1) contribution, non-matching
  #   documents reliably outscore matching ones after the query is applied.
  #
  # Document names are deliberately chosen so that termNieuwjaarEvent ranks higher naturally
  # (higher TF for kerst due to repetition), but the score gap stays below ~1.0. This ensures
  # the ~1.0 NOT-branch bonus is always enough to flip the order after negative boosting.

  @testIsolation
  Scenario: I can negatively boost events
    Given I create a random labelname of 10 characters
    When I create an event with name "kerst%{labelname} kerst%{labelname} nieuwjaar%{labelname}" and save the "id" as "termNieuwjaarEvent"
    And I create an event with name "kerst%{labelname} feest%{labelname}" and save the "id" as "termKerstEvent"
    When I am using the Search API v3 base URL
    And I wait for 2 results at "/events" with parameters:
      | text | kerst%{labelname} |
    And I send a GET request to "/events" with parameters:
      | text        | kerst%{labelname} |
      | sort[score] | desc              |
    Then the JSON response at "totalItems" should be 2
    And the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termNieuwjaarEvent}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termKerstEvent}",
        "@type": "Event"
      }
    ]
    """
    When I send a GET request to "/events" with parameters:
      | text        | kerst%{labelname}                                          |
      | q           | (nieuwjaar%{labelname}^0.1) OR (NOT nieuwjaar%{labelname}) |
      | sort[score] | desc                                                       |
    Then the JSON response at "member" should be:
    """
    [
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termKerstEvent}",
        "@type": "Event"
      },
      {
        "@id": "http://io.uitdatabank.local:80/events/%{termNieuwjaarEvent}",
        "@type": "Event"
      }
    ]
    """
