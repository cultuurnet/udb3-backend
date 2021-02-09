<?php

namespace CultuurNet\UDB3\History;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use DateTime;
use JsonSerializable;

class Log implements JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var String|null
     */
    private $author;

    /**
     * @var String
     */
    private $description;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $api;

    /**
     * @var string
     */
    private $consumerName;

    public function __construct(
        string $id,
        DateTime $date,
        string $description,
        string $author = null,
        string $apiKey = null,
        string $api = null,
        string $consumerName = null
    ) {
        $this->id = $id;
        $this->date = clone $date;
        $this->description = $description;
        $this->author = $author;
        $this->apiKey = $apiKey;
        $this->api = $api;
        $this->consumerName = $consumerName;
    }

    public function getUniqueKey(): string
    {
        return $this->id . '_' . $this->date->format('c');
    }

    public function withoutAuthor(): Log
    {
        $log = clone $this;
        $log->author = null;
        return $log;
    }

    public function withAuthor(string $author): Log
    {
        $log = clone $this;
        $log->author = $author;
        return $log;
    }

    public function withDate(DateTime $dateTime): Log
    {
        $log = clone $this;
        $log->date = $dateTime;
        return $log;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $log = [
            'date' => $this->date->format('c'),
            'description' => $this->description,
        ];

        if ($this->author) {
            $log['author'] = $this->author;
        }

        if ($this->apiKey) {
            $log['apiKey'] = $this->apiKey;
        }

        if ($this->api) {
            $log['api'] = $this->api;
        }

        if ($this->consumerName) {
            $log['consumerName'] = $this->consumerName;
        }

        return $log;
    }

    public static function createFromDomainMessage(
        DomainMessage $domainMessage,
        $description,
        ?string $idSuffix = null
    ): Log {
        $id = $domainMessage->getId() . '_' . $domainMessage->getPlayhead();
        if ($idSuffix !== null) {
            $id .= '_' . $idSuffix;
        }

        $date = DateTime::createFromFormat(
            BroadwayDateTime::FORMAT_STRING,
            $domainMessage->getRecordedOn()->toString()
        );

        $metadata = $domainMessage->getMetadata()->serialize();

        $author = $metadata['user_nick'] ?? null;
        $apiKey = $metadata['auth_api_key'] ?? null;
        $api = $metadata['api'] ?? null;
        $consumer = $metadata['consumer']['name'] ?? null;

        // In the past the api key was sometimes stored, but incorrectly as an empty array due to a bug.
        // In those cases we just treat it as null.
        if (is_array($apiKey)) {
            $apiKey = null;
        }

        return new Log($id, $date, $description, $author, $apiKey, $api, $consumer);
    }
}
