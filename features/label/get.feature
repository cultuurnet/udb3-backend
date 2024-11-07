Feature: Test the UDB3 labels API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"

  Scenario: Get single label by name
    When I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I send a GET request to "/labels/%{uuid}"
    And I keep the value of the JSON response at "name" as "name"
    And I send a GET request to "/labels/%{name}"
    Then the response status should be "200"
    And the JSON response at "visibility" should be "visible"
    And the JSON response at "privacy" should be "public"
    And the JSON response at "excluded" should be false

  Scenario: Get all labels with a specific name
    When I send a GET request to "/labels/" with parameters:
       | limit   | 10      |
       | query   | special |
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "totalItems" should be 4
    And the JSON response at "member/0/name" should be "special-label"
    And the JSON response at "member/1/name" should be "special_label"
    And the JSON response at "member/2/name" should be "special_label*"
    And the JSON response at "member/3/name" should be "special_label#"

  Scenario: Hide excluded labels if suggestion is true
    When I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I send a GET request to "/labels/%{uuid}"
    And I keep the value of the JSON response at "name" as "name"
    And I patch the label with id "%{uuid}" and command "Exclude"
    And I send a GET request to "/labels/" with parameters:
      | limit      | 10      |
      | query      | %{name} |
      | suggestion | true    |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 0

  Scenario: Show excluded labels if suggestion is false
    When I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I send a GET request to "/labels/%{uuid}"
    And I keep the value of the JSON response at "name" as "name"
    And I patch the label with id "%{uuid}" and command "Exclude"
    And I send a GET request to "/labels/" with parameters:
      | limit      | 10      |
      | query      | %{name} |
      | suggestion | false    |
    Then the response status should be "200"
    And the JSON response at "totalItems" should be 1

  Scenario: Show the labels sorted by closest match
    When I send a GET request to "/labels/" with parameters:
      | limit      | 10      |
      | query      | walk    |
      | suggestion | true    |
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "totalItems" should be 4
    And the JSON response at "member/0/name" should be "walk"
    And the JSON response at "member/1/name" should be "walking tour"
    And the JSON response at "member/2/name" should be "forest walk"
    And the JSON response at "member/3/name" should be "city walk"

  @bugfix # https://jira.uitdatabank.be/browse/III-4734
  Scenario: Search labels as suggestions
    When I send a GET request to "/labels/" with parameters:
      | limit      | 10      |
      | query      | special |
      | suggestion | true    |
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "totalItems" should be 2
    And the JSON response at "member/0/name" should be "special_label"
    And the JSON response at "member/1/name" should be "special-label"

  @bugfix # https://jira.uitdatabank.be/browse/III-5006
  Scenario: Search labels with offset beyond result window with at least one result
    When I send a GET request to "/labels/" with parameters:
      | start   | 999999999999999999999999999999  |
      | query   | special |
    Then the response status should be "200"
    And the response body should be valid JSON
    And the JSON response at "member" should be:
    """
    []
    """
