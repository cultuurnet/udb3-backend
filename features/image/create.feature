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
     And I keep the value of the JSON response at "imageId" as "image_id"

     Given I send and accept "application/json"
     When I send a GET request to "/images/%{image_id}"
     Then the response status should be "200"
     And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{image_id}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{image_id}"
     }
     """

     Given I send and accept "application/json"
     When I send a GET request to "%{image_@id}"
     Then the response status should be "200"
     And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{image_id}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{image_id}"
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
    And I keep the value of the JSON response at "imageId" as "image_id"

    Given I send and accept "application/json"
    When I send a GET request to "/images/%{image_id}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{image_id}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{image_id}"
     }
     """

    Given I send and accept "application/json"
    When I send a GET request to "%{image_@id}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{image_id}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{image_id}.jpeg",
      "description":"logo",
      "copyrightHolder":"me",
      "inLanguage":"nl",
      "id": "%{image_id}"
     }
     """

  Scenario: Create image via json body
    Given I send and accept "application/json"
    And I set the JSON request payload to:
    """
    {
      "contentUrl": "https://images-acc-uitdatabank.imgix.net/324cc291-7d28-48c2-9da6-84dbc00b3757.png",
      "description": "afbeelding via Json Body",
      "copyrightHolder": "publiq",
      "inLanguage": "nl"
    }
    """
    And I send a POST request to "/images/"
    And show me the unparsed response
    Then the response status should be "201"
    And I keep the value of the JSON response at "@id" as "image_@id"
    And I keep the value of the JSON response at "imageId" as "image_id"
    When I send a GET request to "/images/%{image_id}"
    Then the response status should be "200"
    And the JSON response should be:
     """
     {
      "@id": "%{baseUrl}/images/%{image_id}",
      "@type":"schema:ImageObject",
      "contentUrl":"https://images.uitdatabank.dev/%{image_id}.png",
      "thumbnailUrl":"https://images.uitdatabank.dev/%{image_id}.png",
      "description":"afbeelding via Json Body",
      "copyrightHolder":"publiq",
      "inLanguage":"nl",
      "id": "%{image_id}"
     }
     """
