Feature: Test event images property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v2 user "centraal_beheerder"
    And I send and accept "application/json"

    Given I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId1"
    And I keep the value of the JSON response at "@id" as "imageUrl1"

    Given I set the form data properties to:
      | description     | logo2 |
      | copyrightHolder | me2   |
      | language        | nl   |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId2"
    And I keep the value of the JSON response at "@id" as "imageUrl2"

    Given I create a minimal place and save the "url" as "placeUrl"

  Scenario: Create a new event with two valid images
    When I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "logo",
        "copyrightHolder": "me",
        "inLanguage": "nl",
        "id": "%{imageId1}"
      },
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "logo2",
        "copyrightHolder": "me2",
        "inLanguage": "nl",
        "id": "%{imageId2}"
      }
    ]
    """

  @bugfix # https://jira.uitdatabank.be/browse/III-4669
  Scenario: Create a new event with two valid images and remove them with an empty mediaObject list in the JSON
    When I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    And I update the event at "%{eventUrl}" from "events/event-minimal-permanent-with-null-or-empty-values.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response should not have "mediaObject"
    And the JSON response should not have "image"

  Scenario: Update an event by adding two valid images
    When I create a minimal permanent event and save the "url" as "eventUrl"
    And I update the event at "%{eventUrl}" from "events/event-with-images.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "logo",
        "copyrightHolder": "me",
        "inLanguage": "nl",
        "id": "%{imageId1}"
      },
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "logo2",
        "copyrightHolder": "me2",
        "inLanguage": "nl",
        "id": "%{imageId2}"
      }
    ]
    """

  Scenario: Create a new event with two valid images with overwritten properties
    When I create an event from "events/event-with-images-overwritten.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "OVERWRITTEN DESCRIPTION",
        "copyrightHolder": "OVERWRITTEN COPYRIGHTHOLDER",
        "inLanguage": "de",
        "id": "%{imageId1}"
      },
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "OVERWRITTEN DESCRIPTION 2",
        "copyrightHolder": "OVERWRITTEN COPYRIGHTHOLDER 2",
        "inLanguage": "fr",
        "id": "%{imageId2}"
      }
    ]
    """

  Scenario: Update an event with two valid images by overwriting their properties
    When I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    And I update the event at "%{eventUrl}" from "events/event-with-images-overwritten.json"
    And I get the event at "%{eventUrl}"
    Then the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "OVERWRITTEN DESCRIPTION",
        "copyrightHolder": "OVERWRITTEN COPYRIGHTHOLDER",
        "inLanguage": "de",
        "id": "%{imageId1}"
      },
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "OVERWRITTEN DESCRIPTION 2",
        "copyrightHolder": "OVERWRITTEN COPYRIGHTHOLDER 2",
        "inLanguage": "fr",
        "id": "%{imageId2}"
      }
    ]
    """

  Scenario: Create a new event with two valid and two non-existing images
    Given I create a random name of 10 characters
    When I set the JSON request payload from "events/event-with-non-existing-images.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/body/invalid-data"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "/mediaObject/0/@id",
        "error": "Image with @id \"%{baseUrl}/images/00000000-0000-0000-0000-000000000000\" (id \"00000000-0000-0000-0000-000000000000\") does not exist."
      },
      {
        "jsonPointer": "/mediaObject/2/@id",
        "error": "Image with @id \"%{baseUrl}/images/10000000-0000-0000-0000-000000000000\" (id \"10000000-0000-0000-0000-000000000000\") does not exist."
      }
    ]
    """

  Scenario: Create a new event with two valid and two invalid images
    Given I create a random name of 10 characters
    When I set the JSON request payload from "events/event-with-invalid-images.json"
    And I send a POST request to "/events/"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/body/invalid-data"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "/mediaObject/0/@id",
        "error": "Image with @id \"%{baseUrl}/images/invalid\" does not exist."
      },
      {
        "jsonPointer": "/mediaObject/2/@id",
        "error": "Image with @id \"%{baseUrl}/images/invalid\" does not exist."
      }
    ]
    """

  Scenario: I can add a single image to an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "mediaObjectId":"%{imageId1}"
    }
    """
    And I send a POST request to "%{eventUrl}/images"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "id": "%{imageId1}",
        "@type": "schema:ImageObject",
        "contentUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "copyrightHolder": "me",
        "description": "logo",
        "inLanguage": "nl",
        "thumbnailUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg"
      }
    ]
    """

  Scenario: The uuid is checked when adding a single image to an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "mediaObjectId":"ceci-nest-pas-un-image"
    }
    """
    And I send a POST request to "%{eventUrl}/images"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/mediaObjectId",
        "error":"The data must match the 'uuid' format"
       }
    ]
    """

  Scenario: The request body is checked when adding a single image to an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {}
    """
    And I send a POST request to "%{eventUrl}/images"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/",
        "error":"The required properties (mediaObjectId) are missing"
      }
    ]
    """

  Scenario: I can update an image on an event
    Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "description": "An image of the event venue",
      "copyrightHolder": "madewithlove"
    }
    """
    And I send a PUT request to "%{eventUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "An image of the event venue",
        "copyrightHolder": "madewithlove",
        "inLanguage": "nl",
        "id": "%{imageId1}"
      },
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "logo2",
        "copyrightHolder": "me2",
        "inLanguage": "nl",
        "id": "%{imageId2}"
      }
    ]
    """

  Scenario: I can update an image on an event with legacy POST
    Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "description": "An image of the event venue",
      "copyrightHolder": "madewithlove"
    }
    """
    And I send a POST request to "%{eventUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "An image of the event venue",
        "copyrightHolder": "madewithlove",
        "inLanguage": "nl",
        "id": "%{imageId1}"
      },
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "logo2",
        "copyrightHolder": "me2",
        "inLanguage": "nl",
        "id": "%{imageId2}"
      }
    ]
    """

  Scenario: The request body is checked when updating an image on an event
    Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {}
    """
    And I send a POST request to "%{eventUrl}/images/%{imageId1}"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/",
        "error":"The required properties (description, copyrightHolder) are missing"
      }
    ]
    """

  Scenario: I can update an image that's not part of an event
    Given I create a minimal permanent event and save the "url" as "eventUrl"
    When I set the JSON request payload to:
    """
    {
      "description": "An image of the event venue",
      "copyrightHolder": "madewithlove"
    }
    """
    And I send a POST request to "%{eventUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response should not have "mediaObject"

  Scenario: I can delete an image from an event
    Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    And I send a DELETE request to "%{eventUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId2}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId2}.jpeg",
        "description": "logo2",
        "copyrightHolder": "me2",
        "inLanguage": "nl",
        "id": "%{imageId2}"
      }
    ]
    """

    Scenario: I can select a main image on an event
      Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
      And I get the event at "%{eventUrl}"
      And the JSON response at "image" should be:
      """
      "https://images.uitdatabank.dev/%{imageId1}.jpeg"
      """
      When I set the JSON request payload to:
      """
      {
        "mediaObjectId": "%{imageId2}"
      }
      """
      And I send a PUT request to "%{eventUrl}/images/main"
      Then the response status should be "204"
      And I get the event at "%{eventUrl}"
      And the JSON response at "image" should be:
      """
      "https://images.uitdatabank.dev/%{imageId2}.jpeg"
      """

  Scenario: I can select a main image on an event with legacy POST
    Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    And I get the event at "%{eventUrl}"
    And the JSON response at "image" should be:
      """
      "https://images.uitdatabank.dev/%{imageId1}.jpeg"
      """
    When I set the JSON request payload to:
      """
      {
        "mediaObjectId": "%{imageId2}"
      }
      """
    And I send a POST request to "%{eventUrl}/images/main"
    Then the response status should be "204"
    And I get the event at "%{eventUrl}"
    And the JSON response at "image" should be:
      """
      "https://images.uitdatabank.dev/%{imageId2}.jpeg"
      """

  Scenario: The request body is checked when selecting a main image on an event
    Given I create an event from "events/event-with-images.json" and save the "url" as "eventUrl"
    When I set the JSON request payload to:
      """
      {}
      """
    And I send a PUT request to "%{eventUrl}/images/main"
    Then the response status should be "400"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer":"\/",
        "error":"The required properties (mediaObjectId) are missing"
      }
    ]
    """
    And I get the event at "%{eventUrl}"
    And the JSON response at "image" should be:
    """
    "https://images.uitdatabank.dev/%{imageId1}.jpeg"
    """

    Scenario: A main image is not selected when it is not part of the event
      Given I create a minimal permanent event and save the "url" as "eventUrl"
      When I set the JSON request payload to:
      """
      {
        "mediaObjectId": "%{imageId1}"
      }
      """
      And I send a PUT request to "%{eventUrl}/images/main"
      Then the response status should be "400"
      And the JSON response at "detail" should be:
      """
      "The image with id %{imageId1} is not linked to the resource. Add it first before you can perform an action."
      """
      And I get the event at "%{eventUrl}"
      And the JSON response should not have "image"
