Feature: Test place images property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider v1 user "centraal_beheerder"
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

  Scenario: Create a new place with two valid images
    When I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
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
  Scenario: Create a new place with two valid images and remove them again using an empty list in the JSON
    When I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    And I update the place at "%{placeUrl}" from "places/place-with-required-fields-and-null-or-empty-values.json"
    And I get the place at "%{placeUrl}"
    Then the JSON response should not have "mediaObject"
    Then the JSON response should not have "image"

  Scenario: Update an place by adding two valid images
    When I create a minimal place and save the "url" as "placeUrl"
    And I update the place at "%{placeUrl}" from "places/place-with-images.json"
    And I get the place at "%{placeUrl}"
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

  Scenario: Create a new place with two valid images with overwritten properties
    When I create a place from "places/place-with-images-overwritten.json" and save the "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
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

  Scenario: Update an place with two valid images by overwriting their properties
    When I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    And I update the place at "%{placeUrl}" from "places/place-with-images-overwritten.json"
    And I get the place at "%{placeUrl}"
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

  Scenario: Create a new place with two valid and two non-existing images
    Given I create a random name of 10 characters
    When I set the JSON request payload from "places/place-with-non-existing-images.json"
    And I send a POST request to "/places/"
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

  Scenario: Create a new place with two valid and two invalid images
    Given I create a random name of 10 characters
    When I set the JSON request payload from "places/place-with-invalid-images.json"
    And I send a POST request to "/places/"
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

  Scenario: I can add a single image to a place
    Given I create a minimal place and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "mediaObjectId":"%{imageId1}"
    }
    """
    And I send a POST request to "%{placeUrl}/images"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type": "schema:ImageObject",
        "contentUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "copyrightHolder": "me",
        "description": "logo",
        "inLanguage": "nl",
        "thumbnailUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "id": "%{imageId1}"
      }
    ]
    """

  Scenario: The uuid is checked when adding a single image to a place
    Given I create a minimal place and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "mediaObjectId":"ceci-nest-pas-un-image"
    }
    """
    And I send a POST request to "%{placeUrl}/images"
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

  Scenario: The request body is checked when adding a single image to a place
    Given I create a minimal place and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {}
    """
    And I send a POST request to "%{placeUrl}/images"
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

  Scenario: I can update an image on a place
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "description": "An image of the place",
      "copyrightHolder": "madewithlove"
    }
    """
    And I send a PUT request to "%{placeUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "An image of the place",
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

  Scenario: I can update an image on a place with legacy POST
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "description": "An image of the place",
      "copyrightHolder": "madewithlove"
    }
    """
    And I send a POST request to "%{placeUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "mediaObject" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type":"schema:ImageObject",
        "contentUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "description": "An image of the place",
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

  Scenario: The request body is checked when updating an image on a place
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {}
    """
    And I send a POST request to "%{placeUrl}/images/%{imageId1}"
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

  Scenario: I can update an image that's not part of a place
    Given I create a minimal place and save the "url" as "placeUrl"
    When I set the JSON request payload to:
    """
    {
      "description": "An image of the place",
      "copyrightHolder": "madewithlove"
    }
    """
    And I send a POST request to "%{placeUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response should not have "mediaObject"

  Scenario: I can delete an image from a place
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    And I send a DELETE request to "%{placeUrl}/images/%{imageId1}"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
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

  Scenario: I can select a main image on a place
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
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
    And I send a PUT request to "%{placeUrl}/images/main"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "image" should be:
      """
      "https://images.uitdatabank.dev/%{imageId2}.jpeg"
      """

  Scenario: I can select a main image on a place with legacy POST
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    And I get the place at "%{placeUrl}"
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
    And I send a POST request to "%{placeUrl}/images/main"
    Then the response status should be "204"
    And I get the place at "%{placeUrl}"
    And the JSON response at "image" should be:
      """
      "https://images.uitdatabank.dev/%{imageId2}.jpeg"
      """

  Scenario: The request body is checked when selecting a main image on a place
    Given I create a place from "places/place-with-images.json" and save the "url" as "placeUrl"
    When I set the JSON request payload to:
      """
      {}
      """
    And I send a PUT request to "%{placeUrl}/images/main"
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
    And I get the place at "%{placeUrl}"
    And the JSON response at "image" should be:
    """
    "https://images.uitdatabank.dev/%{imageId1}.jpeg"
    """

  Scenario: A main image can not be selected that's not part of the place
    Given I create a minimal place and save the "url" as "placeUrl"
    When I set the JSON request payload to:
      """
      {
        "mediaObjectId": "%{imageId1}"
      }
      """
    And I send a PUT request to "%{placeUrl}/images/main"
    Then the response status should be "400"
    And the JSON response at "detail" should be:
    """
    "The image with id %{imageId1} is not linked to the resource. Add it first before you can perform an action."
    """
    And I get the place at "%{placeUrl}"
    And the JSON response should not have "image"
