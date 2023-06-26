<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait CuratorSteps
{
    /**
     * @Given I create a news article and save the id as :variableName
     */
    public function iCreateANewsArticleAndSaveTheIdAs(string $variableName): void
    {
        $this->iCreateARandomNameOfCharacters(12);

        $article = [
            'headline' => 'Curator API migrated',
            'inLanguage' => 'nl',
            'text' => 'Op 6 december 2021 besloot publiq om de curator API te migreren.',
            'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
            'publisher' => 'BILL',
            'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            'url' => 'https://www.publiq.be/blog/%{name}',
        ];

        $response = $this->getHttpClient()->postJSON(
            '/news-articles/',
            $this->variableState->replaceVariables(Json::encode($article))
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs('id', $variableName);
    }
}
