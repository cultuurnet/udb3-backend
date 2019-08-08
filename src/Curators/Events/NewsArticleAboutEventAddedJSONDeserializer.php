<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Events;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;

final class NewsArticleAboutEventAddedJSONDeserializer extends JSONDeserializer
{
    public static function getContentType(): StringLiteral
    {
        return new StringLiteral('application/vnd.cultuurnet.curators-api.events.news-article-about-event-added+json');
    }

    public function deserialize(StringLiteral $json)
    {
        $json = parent::deserialize($json);

        if (!isset($json->newsArticleId)) {
            throw new MissingValueException('newsArticleId is missing');
        }

        if (!isset($json->eventId)) {
            throw new MissingValueException('eventId is missing');
        }

        return new NewsArticleAboutEventAdded($json->newsArticleId, $json->eventId);
    }
}
