Feature: Test organizer images property

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
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
      | language        | nl    |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "imageId2"
    And I keep the value of the JSON response at "@id" as "imageUrl2"

  Scenario: Create a new organizer with two valid images
    When I create an organizer from "organizers/organizer-with-images.json" and save the "url" as "organizerUrl"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images" should be:
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

  Scenario: Update an organizer by adding two valid images
    When I create a minimal organizer and save the "url" as "organizerUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-with-images.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images" should be:
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

  Scenario: Create a new organizer with two valid images with overwritten properties
    When I create an organizer from "organizers/organizer-with-images-overwritten.json" and save the "url" as "organizerUrl"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images" should be:
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

  Scenario: Update an organizer with two valid images by overwriting their properties
    When I create an organizer from "organizers/organizer-with-images.json" and save the "url" as "organizerUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-with-images-overwritten.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images" should be:
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

  @bugfix # https://jira.uitdatabank.be/browse/III-4669
  Scenario: Create a new organizer with two valid images and remove them again with an empty images list in JSON
    When I create an organizer from "organizers/organizer-with-images.json" and save the "url" as "organizerUrl"
    And I update the organizer at "%{organizerUrl}" from "organizers/organizer-minimal-with-null-or-empty-values.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "images"
    And the JSON response should not have "mainImage"

  Scenario: Create a new organizer with two valid and two non-existing images
    Given I create a random name of 10 characters
    When I set the JSON request payload from "organizers/organizer-with-non-existing-images.json"
    And I send a POST request to "/organizers/"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/body/invalid-data"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "/images/0/@id",
        "error": "Image with @id \"%{baseUrl}/images/00000000-0000-0000-0000-000000000000\" (id \"00000000-0000-0000-0000-000000000000\") does not exist."
      },
      {
        "jsonPointer": "/images/2/@id",
        "error": "Image with @id \"%{baseUrl}/images/10000000-0000-0000-0000-000000000000\" (id \"10000000-0000-0000-0000-000000000000\") does not exist."
      }
    ]
    """

  Scenario: Create a new organizer with two valid and two invalid images
    Given I create a random name of 10 characters
    When I set the JSON request payload from "organizers/organizer-with-invalid-images.json"
    And I send a POST request to "/organizers/"
    Then the response status should be "400"
    And the JSON response at "type" should be "https://api.publiq.be/probs/body/invalid-data"
    And the JSON response at "schemaErrors" should be:
    """
    [
      {
        "jsonPointer": "/images/0/@id",
        "error": "Image with @id \"%{baseUrl}/images/invalid\" does not exist."
      },
      {
        "jsonPointer": "/images/2/@id",
        "error": "Image with @id \"%{baseUrl}/images/invalid\" does not exist."
      }
    ]
    """

  Scenario: Add image to organizer via images endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "id":"%{imageId1}",
      "language":"en",
      "description":"A nice logo",
      "copyrightHolder":"publiq vzw"
    }
    """
    And I send a POST request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images" should be:
    """
    [
      {
        "@id": "%{imageUrl1}",
        "@type": "schema:ImageObject",
        "id": "%{imageId1}",
        "contentUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "copyrightHolder": "publiq vzw",
        "description": "A nice logo",
        "inLanguage": "en",
        "thumbnailUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg"
      }
    ]
    """
    And the JSON response at "mainImage" should be:
    """
      "https://images.uitdatabank.dev/%{imageId1}.jpeg"
    """

  Scenario: Try to set main image on organizer with no images via images endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "imageId":"%{imageId1}"
    }
    """
    And I send a PUT request to "%{organizerUrl}/images/main"
    Then the response status should be "400"
    And the JSON response at "detail" should be:
      """
      "The image with id %{imageId1} is not linked to the resource. Add it first before you can perform an action."
      """
    When I get the organizer at "%{organizerUrl}"
    And the JSON response should not have "mainImage"

  Scenario: Trying to set main image on organizer with no images and deprecated mediaObjectId format via images endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "mediaObjectId":"%{imageId1}"
    }
    """
    And I send a PUT request to "%{organizerUrl}/images/main"
    Then the response status should be "400"
    And the JSON response at "detail" should be:
      """
      "The image with id %{imageId1} is not linked to the resource. Add it first before you can perform an action."
      """
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response should not have "mainImage"

  Scenario: Update image of an organizer via images endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "id":"%{imageId1}",
      "language":"en",
      "description":"A nice image",
      "copyrightHolder":"publiq"
    }
    """
    And I send a POST request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "language":"nl",
      "description":"Aangepaste beschrijving",
      "copyrightHolder":"Aangepaste rechtenhouder"
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response at "images" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{imageId1}",
        "@type": "schema:ImageObject",
        "id": "%{imageId1}",
        "contentUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg",
        "copyrightHolder":"Aangepaste rechtenhouder",
        "description": "Aangepaste beschrijving",
        "inLanguage": "nl",
        "thumbnailUrl": "https://images.uitdatabank.dev/%{imageId1}.jpeg"
      }
    ]
    """

  Scenario: Remove image from organizer via images endpoint
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I send a DELETE request to "%{organizerUrl}/images/%{imageId1}"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    And the JSON response should not have "images"
    And the JSON response should not have "mainImage"

  Scenario: Add an AI generated image to an organizer via images endpoint
    Given I set the form data properties to:
      | description     | ai logo |
      | copyrightHolder | me      |
      | language        | nl      |
      | isAiGenerated   | true    |
    When I upload "file" from path "images/udb.jpg" to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "imageId" as "aiImageId"
    Given I create a minimal organizer and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    {
      "id":"%{aiImageId}"
    }
    """
    And I send a POST request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images" should be:
    """
    [
      {
        "@id": "%{baseUrl}/images/%{aiImageId}",
        "@type": "schema:ImageObject",
        "id": "%{aiImageId}",
        "contentUrl": "https://images.uitdatabank.dev/%{aiImageId}.ai.jpeg",
        "copyrightHolder": "me",
        "description": "ai logo (Gegenereerd met AI)",
        "inLanguage": "nl",
        "thumbnailUrl": "https://images.uitdatabank.dev/%{aiImageId}.ai.jpeg",
        "isAiGenerated": true
      }
    ]
    """

  Scenario: Update isAiGenerated of an organizer image via images endpoint and keep it when not given
    Given I create an organizer from "organizers/organizer-with-images.json" and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "isAiGenerated": true
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/isAiGenerated" should be "true"
    And the JSON response should not have "images/1/isAiGenerated"

    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "description": "Aangepaste beschrijving"
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/description" should be "Aangepaste beschrijving (Gegenereerd met AI)"
    And the JSON response at "images/0/isAiGenerated" should be "true"

    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "isAiGenerated": false
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/isAiGenerated" should be "false"

  Scenario: isAiGenerated of images is kept when updating an organizer without isAiGenerated
    Given I create an organizer from "organizers/organizer-with-ai-generated-images.json" and save the "url" as "organizerUrl"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/isAiGenerated" should be "true"
    And the JSON response at "images/1/isAiGenerated" should be "false"
    When I update the organizer at "%{organizerUrl}" from "organizers/organizer-with-images.json"
    And I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/isAiGenerated" should be "true"
    And the JSON response at "images/1/isAiGenerated" should be "false"

  Scenario: The AI generated suffix is added to and removed from the organizer image description when isAiGenerated changes
    Given I create an organizer from "organizers/organizer-with-images.json" and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "isAiGenerated": true
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/description" should be "logo (Gegenereerd met AI)"
    And the JSON response at "images/0/isAiGenerated" should be "true"

    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "description": "logo (Gegenereerd met AI)",
      "isAiGenerated": true
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/description" should be "logo (Gegenereerd met AI)"

    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "isAiGenerated": false
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/description" should be "logo"
    And the JSON response at "images/0/isAiGenerated" should be "false"

  Scenario: The image file is renamed when isAiGenerated of an organizer image changes
    Given I create an organizer from "organizers/organizer-with-images.json" and save the "url" as "organizerUrl"
    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "isAiGenerated": true
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/contentUrl" should be "https://images.uitdatabank.dev/%{imageId1}.ai.jpeg"
    And the JSON response at "images/0/thumbnailUrl" should be "https://images.uitdatabank.dev/%{imageId1}.ai.jpeg"
    And the JSON response at "mainImage" should be "https://images.uitdatabank.dev/%{imageId1}.ai.jpeg"
    When I send a GET request to "/images/%{imageId1}"
    Then the JSON response at "contentUrl" should be "https://images.uitdatabank.dev/%{imageId1}.ai.jpeg"

    When I set the JSON request payload to:
    """
    [{
      "id":"%{imageId1}",
      "isAiGenerated": false
    }]
    """
    And I send a PATCH request to "%{organizerUrl}/images"
    Then the response status should be "204"
    When I get the organizer at "%{organizerUrl}"
    Then the JSON response at "images/0/contentUrl" should be "https://images.uitdatabank.dev/%{imageId1}.jpeg"
    And the JSON response at "images/0/thumbnailUrl" should be "https://images.uitdatabank.dev/%{imageId1}.jpeg"
    And the JSON response at "mainImage" should be "https://images.uitdatabank.dev/%{imageId1}.jpeg"
    When I send a GET request to "/images/%{imageId1}"
    Then the JSON response at "contentUrl" should be "https://images.uitdatabank.dev/%{imageId1}.jpeg"
