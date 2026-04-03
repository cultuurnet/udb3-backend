Feature: Test the UDB3 image API

  Background:
    Given I am using the UDB3 base URL
    And I am using an UiTID v1 API key of consumer "uitdatabank"
    And I am authorized as JWT provider user "centraal_beheerder"
    And I accept "application/json"

   Scenario: Create image via multiform
     Given I set the form data properties to:
        | description     | logo |
        | copyrightHolder | me   |
        | language        | nl   |
     When I upload "file" from path "images/udb.jpg" to "/images/"
     Then the response status should be "201"
     And I keep the value of the JSON response at "@id" as "image_@id"
     And I keep the value of the JSON response at "imageId" as "imageId"

     Given I send and accept "application/json"
     When I send a GET request to "/images/%{imageId}"
     Then the response status should be "200"
     And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{imageId}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{imageId}"
     }
     """

     Given I send and accept "application/json"
     When I send a GET request to "%{image_@id}"
     Then the response status should be "200"
     And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{imageId}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{imageId}"
     }
     """

  Scenario: Create image via multiform without trailing slash in URL
    Given I set the form data properties to:
      | description     | logo |
      | copyrightHolder | me   |
      | language        | nl   |
    When I upload "file" from path "images/udb.jpg" to "/images"
    Then the response status should be "201"
    And I keep the value of the JSON response at "@id" as "image_@id"
    And I keep the value of the JSON response at "imageId" as "imageId"

    Given I send and accept "application/json"
    When I send a GET request to "/images/%{imageId}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{imageId}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{imageId}"
     }
     """

    Given I send and accept "application/json"
    When I send a GET request to "%{image_@id}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{imageId}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{imageId}"
     }
     """

  Scenario: Create image via json body
    Given I send and accept "application/json"
    And I set the JSON request payload to:
    """
    {
      "contentUrl": "http://io.uitdatabank.local/testfiles/publiq.png",
      "description": "afbeelding via Json Body",
      "copyrightHolder": "publiq",
      "inLanguage": "nl"
    }
    """
    And I send a POST request to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "@id" as "image_@id"
    And I keep the value of the JSON response at "imageId" as "imageId"
    When I send a GET request to "/images/%{imageId}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{imageId}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{imageId}.png",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId}.png",
      "description":"afbeelding via Json Body",
      "copyrightHolder":"publiq",
      "inLanguage":"nl",
      "id": "%{imageId}"
     }
     """

  Scenario: Create image with unknown file extension
    Given I send and accept "application/json"
    And I set the JSON request payload to:
    """
    {
      "contentUrl": "http://io.uitdatabank.local/testfiles/publiq",
      "description": "afbeelding via Json Body",
      "copyrightHolder": "publiq",
      "inLanguage": "nl"
    }
    """
    And I send a POST request to "/images/"
    Then the response status should be "201"
    And I keep the value of the JSON response at "@id" as "image_@id"
    And I keep the value of the JSON response at "imageId" as "imageId"
    When I send a GET request to "/images/%{imageId}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{imageId}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{imageId}.png",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{imageId}.png",
      "description":"afbeelding via Json Body",
      "copyrightHolder":"publiq",
      "inLanguage":"nl",
      "id": "%{imageId}"
     }
     """

  Scenario: check for non image types
    Given I send and accept "application/json"
    And I set the JSON request payload to:
    """
    {
      "contentUrl": "http://io.uitdatabank.local/testfiles/textfile",
      "description": "afbeelding via Json Body",
      "copyrightHolder": "publiq",
      "inLanguage": "nl"
    }
    """
    And I send a POST request to "/images/"
    Then the response status should be "400"
    And the JSON response should be:
     """
     {
       "type":"https:\/\/api.publiq.be\/probs\/body\/file-invalid-type",
       "title":"Invalid file type",
       "status":400,
       "detail":"The uploaded file has mime type \"text\/plain\" instead of image\/png,image\/jpeg,image\/gif"
     }
     """

  Scenario: It handles non existing urls
    Given I send and accept "application/json"
    And I set the JSON request payload to:
    """
    {
      "contentUrl": "http://io.uitdatabank.local/testfiles/thisDoesNotExist.png",
      "description": "afbeelding via Json Body",
      "copyrightHolder": "publiq",
      "inLanguage": "nl"
    }
    """
    And I send a POST request to "/images/"
    Then the response status should be "400"
    And the JSON response should be:
     """
     {
       "type":"https:\/\/api.publiq.be\/probs\/body\/file-invalid-type",
       "title":"Invalid file type",
       "status":400,
       "detail":"The file could not be downloaded correctly."
     }
     """

  Scenario: It handles missing data
    Given I send and accept "application/json"
    And I set the JSON request payload to:
    """
    {
      "contentUrl": "http://io.uitdatabank.local/testfiles/publiq.png"
    }
    """
    And I send a POST request to "/images/"
    Then the response status should be "400"
    And the JSON response should be:
     """
     {
       "type":"https:\/\/api.publiq.be\/probs\/body\/invalid-data",
       "title":"Invalid body data",
       "status":400,
       "schemaErrors":[{"jsonPointer":"\/","error":"The required properties (description, copyrightHolder, inLanguage) are missing"}]
     }
     """
