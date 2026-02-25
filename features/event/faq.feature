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
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faq/0/nl/question" should be "Hoe geraak ik er?"
    And the JSON response at "faq/0/nl/answer" should be "Met de bus."
    And the JSON response at "faq/0/en/question" should be "How do I get there?"
    And the JSON response at "faq/0/en/answer" should be "By bus."

  Scenario: Update existing FAQ items on an event
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
      """
      [
        {
          "id": "b4575c68-dc04-4b67-9568-63e5d00d4dde",
          "nl": {
            "question": "Hoe geraak ik er?",
            "answer": "Met de bus."
          }
        }
      ]
      """
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faq/0/nl/answer" should be "Met de bus."
    And I set the JSON request payload to:
      """
      [
        {
          "id": "b4575c68-dc04-4b67-9568-63e5d00d4dde",
          "nl": {
            "question": "Hoe geraak ik er?",
            "answer": "Met de trein."
          }
        }
      ]
      """
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faq/0/nl/answer" should be "Met de trein."

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
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should have "faq"
    And I set the JSON request payload to:
      """
      []
      """
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "faq"

  Scenario: Partially update FAQ items on an event
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
      """
      [
        {
          "id": "aaaaaaaa-0000-0000-0000-000000000001",
          "nl": {
            "question": "Vraag 1",
            "answer": "Antwoord 1"
          }
        },
        {
          "id": "bbbbbbbb-0000-0000-0000-000000000002",
          "nl": {
            "question": "Vraag 2",
            "answer": "Antwoord 2"
          }
        }
      ]
      """
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faq" should have 2 entries
    And I set the JSON request payload to:
      """
      [
        {
          "id": "bbbbbbbb-0000-0000-0000-000000000002",
          "nl": {
            "question": "Vraag 2 bijgewerkt",
            "answer": "Antwoord 2 bijgewerkt"
          }
        },
        {
          "id": "cccccccc-0000-0000-0000-000000000003",
          "nl": {
            "question": "Nieuwe vraag",
            "answer": "Nieuw antwoord"
          }
        }
      ]
      """
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "faq" should have 2 entries
    And the JSON response at "faq/0/nl/question" should be "Vraag 2 bijgewerkt"
    And the JSON response at "faq/1/nl/question" should be "Nieuwe vraag"

  Scenario: Cannot update FAQ with an invalid body
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I set the JSON request payload to:
      """
      [{"nl": {"question": "Hoe geraak ik er?"}}]
      """
    When I send a PUT request to "%{eventUrl}/faq/"
    Then the response status should be "400"
    And the JSON response at "schemaErrors/0/jsonPointer" should be "/0/nl"
    And the JSON response at "schemaErrors/0/error" should be "The required properties (answer) are missing"
