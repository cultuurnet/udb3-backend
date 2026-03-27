@sapi3
Feature: Test the Search API v3 calendar summary

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    When I create a minimal place and save the "id" as "uuid_place"
    And I publish the place at "/places/%{uuid_place}"
    And I create an event from "events/event-with-workflow-status-ready-for-validation.json" and save the "id" as "eventId"
    And I wait for the event with url "/events/%{eventId}" to be indexed
    And I am using the Search API v3 base URL

  Scenario: Calendar summaries are not embedded by default
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
    Then the JSON response should not include:
    """
    calendarSummary
    """

  Scenario: I can include various text calendar summaries
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-text                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "text": {
          "xs": "Alle dagen open"
        }
      },
      "fr": {
        "text": {
          "xs": "Ouvert tous les jours"
        }
      },
      "de": {
        "text": {
          "xs": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "text": {
          "xs": "Open every day"
        }
      }
    }
    """
    Then the JSON response at "member/1/calendarSummary" should be:
    """
    {
      "nl": {
        "text": {
          "xs": "Alle dagen open"
        }
      },
      "fr": {
        "text": {
          "xs": "Ouvert tous les jours"
        }
      },
      "de": {
        "text": {
          "xs": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "text": {
          "xs": "Open every day"
        }
      }
    }
    """
    When I send a GET request to "/places" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | lg-text                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "text": {
          "lg": "Alle dagen open"
        }
      },
      "fr": {
        "text": {
          "lg": "Ouvert tous les jours"
        }
      },
      "de": {
        "text": {
          "lg": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "text": {
          "lg": "Open every day"
        }
      }
    }
    """
    When I send a GET request to "/events" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | md-text                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "text": {
          "md": "Alle dagen open"
        }
      },
      "fr": {
        "text": {
          "md": "Ouvert tous les jours"
        }
      },
      "de": {
        "text": {
          "md": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "text": {
          "md": "Open every day"
        }
      }
    }
    """

  Scenario: I can include various html calendar summaries
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-html                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        }
      },
      "fr": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        }
      },
      "de": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        }
      },
      "en": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Open every day</p>"
        }
      }
    }
    """
    Then the JSON response at "member/1/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        }
      },
      "fr": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        }
      },
      "de": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        }
      },
      "en": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Open every day</p>"
        }
      }
    }
    """
    When I send a GET request to "/places" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | lg-html                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        }
      },
      "fr": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        }
      },
      "de": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        }
      },
      "en": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Open every day</p>"
        }
      }
    }
    """
    When I send a GET request to "/events" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | md-html                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        }
      },
      "fr": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        }
      },
      "de": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        }
      },
      "en": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Open every day</p>"
        }
      }
    }
    """

  Scenario: I can combine various calendar summaries
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | lg-text                          |
      | embedCalendarSummaries[] | xs-html                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        },
        "text": {
          "lg": "Alle dagen open"
        }
      },
      "fr": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        },
        "text": {
          "lg": "Ouvert tous les jours"
        }
      },
      "de": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        },
        "text": {
          "lg": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Open every day</p>"
        },
        "text": {
          "lg": "Open every day"
        }
      }
    }
    """
    Then the JSON response at "member/1/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        },
        "text": {
          "lg": "Alle dagen open"
        }
      },
      "fr": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        },
        "text": {
          "lg": "Ouvert tous les jours"
        }
      },
      "de": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        },
        "text": {
          "lg": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "html": {
          "xs": "<p class=\"cf-openinghours\">Open every day</p>"
        },
        "text": {
          "lg": "Open every day"
        }
      }
    }
    """
    When I send a GET request to "/places" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-text                          |
      | embedCalendarSummaries[] | lg-html                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        },
        "text": {
          "xs": "Alle dagen open"
        }
      },
      "fr": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        },
        "text": {
          "xs": "Ouvert tous les jours"
        }
      },
      "de": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        },
        "text": {
          "xs": "Jeden Tag geöffnet"
        }
      },
      "en": {
        "html": {
          "lg": "<p class=\"cf-openinghours\">Open every day</p>"
        },
        "text": {
          "xs": "Open every day"
        }
      }
    }
    """
    When I send a GET request to "/events" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | xs-html                          |
      | embedCalendarSummaries[] | md-html                          |
    Then the JSON response at "member/0/calendarSummary" should be:
    """
    {
      "nl": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Alle dagen open</p>",
          "xs": "<p class=\"cf-openinghours\">Alle dagen open</p>"
        }
      },
      "fr": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>",
          "xs": "<p class=\"cf-openinghours\">Ouvert tous les jours</p>"
        }
      },
      "de": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>",
          "xs": "<p class=\"cf-openinghours\">Jeden Tag geöffnet</p>"
        }
      },
      "en": {
        "html": {
          "md": "<p class=\"cf-openinghours\">Open every day</p>",
          "xs": "<p class=\"cf-openinghours\">Open every day</p>"
        }
      }
    }
    """

  Scenario: I cannot use an unsupported format
    When I send a GET request to "/offers" with parameters:
      | q                        | id:(%{uuid_place} OR %{eventId}) |
      | embedCalendarSummaries[] | md-pdf                           |
    Then the JSON response should be:
    """
    {
      "title": "Not Found",
      "type": "https:\/\/api.publiq.be\/probs\/url\/not-found",
      "status": 404,
      "detail": "Invalid type: pdf. Use one of: text,html"
    }
    """
