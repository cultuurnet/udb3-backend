Feature: Test the UDB3 Bulk labeling API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"
    And I create a minimal place and save the "url" as "placeUrl"
    And I keep the value of the JSON response at "id" as "placeId"
    And I wait for the place with url "%{placeUrl}" to be indexed
    And I create a minimal permanent event and save the "url" as "eventUrl"

  Scenario: Add Label to multiple
    Given I create a random name of 6 characters
    And I set the JSON request payload to:
    """
    {
      "offers": [
        {
	      "@id": "%{eventUrl}",
		  "@type": "Event"
	    },
	    {
	      "@id": "%{placeUrl}",
		  "@type": "Place"
	    }
	   ],
	  "label": "%{name}"
    }
    """
    When I send a POST request to "/offers/labels/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "multipleCommandId"
    And I wait for the command with id "%{multipleCommandId}" to complete
    And I get the event at "%{eventUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{name}"]
    """
    And I get the place at "%{placeUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{name}"]
    """

  Scenario: Add Label via query
    Given I create a random name of 6 characters
    And I set the JSON request payload to:
    """
    {
      "query": "%{placeId}",
      "label": "%{name}"
    }
    """
    When I send a POST request to "/query/labels/"
    Then the response status should be "200"
    And I keep the value of the JSON response at "commandId" as "QueryCommandId"
    And I wait for the command with id "%{QueryCommandId}" to complete
    And I get the place at "%{placeUrl}"
    And the JSON response at "labels" should be:
    """
    ["%{name}"]
    """
