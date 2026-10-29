Feature: Test isAiGenerated of an image is kept in sync between all items that use it

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"

    Given I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId"
    And I send and accept "application/json"

    Given I create a minimal place and save the "url" as "placeUrl"
    And I create a minimal permanent event and save the "url" as "eventUrl1"
    And I create a minimal permanent event and save the "url" as "eventUrl2"
    And I create a minimal organizer and save the "url" as "organizerUrl"

    When I set the JSON request payload to:
    """
    {
      "mediaObjectId":"%{imageId}"
    }
    """
    And I send a POST request to "%{eventUrl1}/images"
    Then the response status should be "204"
    And I send a POST request to "%{eventUrl2}/images"
    Then the response status should be "204"

    When I set the JSON request payload to:
    """
    {
      "id":"%{imageId}",
      "language":"nl",
      "description":"logo",
      "copyrightHolder":"me"
    }
    """
    And I send a POST request to "%{organizerUrl}/images"
    Then the response status should be "204"

  Scenario: Updating isAiGenerated of an image on an event updates the image and the other items that use it
    When I set the JSON request payload to:
    """
    {
      "description": "logo",
      "copyrightHolder": "me",
      "isAiGenerated": true
    }
    """
    And I send a PUT request to "%{eventUrl1}/images/%{imageId}"
    Then the response status should be "204"

    When I send a GET request to "/images/%{imageId}"
    Then the response status should be "200"
    And the JSON response at "isAiGenerated" should be "true"
    And the JSON response at "description" should be "logo (Gegenereerd met AI)"

    When I get the event at "%{eventUrl2}"
    Then the JSON response at "mediaObject/0/isAiGenerated" should be "true"
    And the JSON response at "mediaObject/0/description" should be "logo (Gegenereerd met AI)"

    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/isAiGenerated" should be "true"
    And the JSON response at "images/0/description" should be "logo (Gegenereerd met AI)"

  Scenario: Updating isAiGenerated of an image on an organizer updates the image and the other items that use it
    When I set the JSON request payload to:
    """
    {
      "description": "logo",
      "copyrightHolder": "me",
      "isAiGenerated": true
    }
    """
    And I send a PUT request to "%{eventUrl1}/images/%{imageId}"
    Then the response status should be "204"

    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId}",
      "isAiGenerated": false
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"

    When I send a GET request to "/images/%{imageId}"
    Then the response status should be "200"
    And the JSON response at "isAiGenerated" should be "false"
    And the JSON response at "description" should be "logo"

    When I get the event at "%{eventUrl1}"
    Then the JSON response at "mediaObject/0/isAiGenerated" should be "false"
    And the JSON response at "mediaObject/0/description" should be "logo"

    When I get the event at "%{eventUrl2}"
    Then the JSON response at "mediaObject/0/isAiGenerated" should be "false"
    And the JSON response at "mediaObject/0/description" should be "logo"