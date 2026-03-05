Feature: Test event FAQ

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a place from "places/place.json" and save the "url" as "placeUrl"

  Scenario: Add FAQ items to an event
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de bus."
        },
        "en": {
          "question": "How do I get there?",
          "answer": "By bus."
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faqs/0/nl/question" should be "Hoe geraak ik er?"
    And the JSON response at "faqs/0/nl/answer" should be "Met de bus."
    And the JSON response at "faqs/0/en/question" should be "How do I get there?"
    And the JSON response at "faqs/0/en/answer" should be "By bus."

  Scenario: Update existing FAQ items on an event
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de bus."
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faqs/0/nl/answer" should be "Met de bus."
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de trein."
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faqs/0/nl/answer" should be "Met de trein."

  Scenario: Remove all FAQ items by sending an empty list
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de bus."
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should have "faqs"
    And I set the JSON request payload to:
    """
    []
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "faqs"

  Scenario: Partially update FAQ items on an event
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Vraag 1",
          "answer": "Antwoord 1"
        }
      },
      {
        "nl": {
          "question": "Vraag 2",
          "answer": "Antwoord 2"
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faqs" should have 2 entries
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Vraag 1",
          "answer": "Antwoord 1"
        }
      },
      {
        "nl": {
          "question": "Vraag 2 bijgewerkt",
          "answer": "Antwoord 2 bijgewerkt"
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faqs" should have 2 entries
    And the JSON response at "faqs/0/nl/question" should be "Vraag 1"
    And the JSON response at "faqs/0/nl/answer" should be "Antwoord 1"
    And the JSON response at "faqs/1/nl/question" should be "Vraag 2 bijgewerkt"
    And the JSON response at "faqs/1/nl/answer" should be "Antwoord 2 bijgewerkt"

  Scenario: The internal id of a faq should not be projected
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "nl": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de bus."
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faqs" should be:
    """
    [
      {
        "nl": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de bus."
        }
      }
    ]
    """

  Scenario: Cannot update FAQ with a missing answer
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [{"nl": {"question": "Hoe geraak ik er?"}}]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/nl"
    And the JSON response at "schemaErrors/0/error" should be "The required properties (answer) are missing"

  Scenario: Cannot update FAQ with a missing question
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [{"nl": {"answer": "Met de trein."}}]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/nl"
    And the JSON response at "schemaErrors/0/error" should be "The required properties (question) are missing"

  Scenario: Cannot update FAQ with a missing language
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {
        "": {
          "question": "Hoe geraak ik er?",
          "answer": "Met de bus."
        }
      }
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "400"
    And the JSON response should be:
     """
    {
       "type":"https:\/\/api.publiq.be\/probs\/body\/invalid-data",
       "title":"Invalid body data",
       "status":400,
       "schemaErrors":[
          {
             "jsonPointer":"\/0",
             "error":"The required properties (nl) are missing"
          },
          {
             "jsonPointer":"\/0",
             "error":"The required properties (fr) are missing"
          },
          {
             "jsonPointer":"\/0",
             "error":"The required properties (de) are missing"
          },
          {
             "jsonPointer":"\/0",
             "error":"The required properties (en) are missing"
          }
       ]
    }
    """

  Scenario: Cannot update FAQ with more than 30 items
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
    """
    [
      {"nl": {"question": "v01?", "answer": "a01!"}},
      {"nl": {"question": "v02?", "answer": "a02!"}},
      {"nl": {"question": "v03?", "answer": "a03!"}},
      {"nl": {"question": "v04?", "answer": "a04!"}},
      {"nl": {"question": "v05?", "answer": "a05!"}},
      {"nl": {"question": "v06?", "answer": "a06!"}},
      {"nl": {"question": "v07?", "answer": "a07!"}},
      {"nl": {"question": "v08?", "answer": "a08!"}},
      {"nl": {"question": "v09?", "answer": "a09!"}},
      {"nl": {"question": "v10?", "answer": "a10!"}},
      {"nl": {"question": "v11?", "answer": "a11!"}},
      {"nl": {"question": "v12?", "answer": "a12!"}},
      {"nl": {"question": "v13?", "answer": "a13!"}},
      {"nl": {"question": "v14?", "answer": "a14!"}},
      {"nl": {"question": "v15?", "answer": "a15!"}},
      {"nl": {"question": "v16?", "answer": "a16!"}},
      {"nl": {"question": "v17?", "answer": "a17!"}},
      {"nl": {"question": "v18?", "answer": "a18!"}},
      {"nl": {"question": "v19?", "answer": "a19!"}},
      {"nl": {"question": "v20?", "answer": "a20!"}},
      {"nl": {"question": "v21?", "answer": "a21!"}},
      {"nl": {"question": "v22?", "answer": "a22!"}},
      {"nl": {"question": "v23?", "answer": "a23!"}},
      {"nl": {"question": "v24?", "answer": "a24!"}},
      {"nl": {"question": "v25?", "answer": "a25!"}},
      {"nl": {"question": "v26?", "answer": "a26!"}},
      {"nl": {"question": "v27?", "answer": "a27!"}},
      {"nl": {"question": "v28?", "answer": "a28!"}},
      {"nl": {"question": "v29?", "answer": "a29!"}},
      {"nl": {"question": "v30?", "answer": "a30!"}},
      {"nl": {"question": "v31?", "answer": "a31!"}}
    ]
    """
    When I send a PUT request to "%{eventUrl}/faqs/"
    Then the response status should be "400"
    And the JSON response should be:
    """
    {
      "type": "https:\/\/api.publiq.be\/probs\/body\/invalid-data",
      "title": "Invalid body data",
      "status": 400,
      "schemaErrors": [
        {
          "jsonPointer":"\/",
          "error": "Array should have at most 30 items, 31 found"
        }
      ]
    }
    """
