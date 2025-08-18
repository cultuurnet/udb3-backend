Feature: Test the UDB3 labels API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

  Scenario: Make label invisible
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    When I patch the label with id "%{uuid}" and command "MakeInvisible"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
    And the JSON response at "visibility" should be "invisible"

  Scenario: Make label visible
    Given I create an invisible label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    When I patch the label with id "%{uuid}" and command "MakeVisible"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
    And the JSON response at "visibility" should be "visible"

  Scenario: Make label private
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    When I patch the label with id "%{uuid}" and command "MakePrivate"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
    And the JSON response at "privacy" should be "private"

  Scenario: Make label public
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I patch the label with id "%{uuid}" and command "MakePrivate"
    When I patch the label with id "%{uuid}" and command "MakePublic"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
    And the JSON response at "privacy" should be "public"

  Scenario: Exclude a label
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    When I patch the label with id "%{uuid}" and command "Exclude"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
    And the JSON response at "excluded" should be true

  Scenario: Include an excluded label
    Given I create a label with a random name of 10 characters
    And I keep the value of the JSON response at "uuid" as "uuid"
    And I patch the label with id "%{uuid}" and command "Exclude"
    When I patch the label with id "%{uuid}" and command "Include"
    And I send a GET request to "/labels/%{uuid}"
    Then the response status should be "200"
    And the JSON response at "excluded" should be false
