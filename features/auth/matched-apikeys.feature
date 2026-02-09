Feature: Test authentication with apiKeys matched to clientIds

  Background:
    Given I am using the UDB3 base URL
    And I am not using an UiTID v1 API key
    And I send and accept "application/json"

  Scenario: I can create offers with an apiKey matched to a clientId that has the entry scope
    Given I am using an UiTID v1 API key of consumer "apiKeyMatchedToClientIdWithEntryScope"
    And I am authorized as JWT provider user "invoerder_1"
    And I create a minimal place and save the "url" as "placeUrl"
    And the response status should be "201"
    When I create a minimal permanent event and save the "url" as "eventUrl"
    Then the response status should be "201"

  Scenario: I cannot create offers with an apiKey matched to a clientId that only has the search scope
    Given I am using an UiTID v1 API key of consumer "apiKeyMatchedToClientIdWithSearchScope"
    And I am authorized as JWT provider user "invoerder_1"
    And I set the JSON request payload from "places/place-with-required-fields.json"
    When I send a POST request to "/places/"
    And the response status should be "403"
    And the JSON response should be:
    """
    {
      "type": "https:\/\/api.publiq.be\/probs\/auth\/forbidden",
      "title": "Forbidden",
      "status": 403,
      "detail": "Given API key is not authorized to use Entry API."
    }
    """
