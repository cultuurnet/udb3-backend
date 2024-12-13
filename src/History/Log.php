<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\History;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use DateTime;
use JsonSerializable;

class Log implements JsonSerializable
{
    private string $id;

    private DateTime $date;

    private ?string $author;

    private string $description;

    private ?string $apiKey;

    private ?string $clientId;

    private ?string $clientName;

    private ?string $api;

    private ?string $consumerName;

    private function __construct(
        string $id,
        DateTime $date,
        string $description,
        string $author = null,
        string $apiKey = null,
        string $clientId = null,
        string $clientName = null,
        string $api = null,
        string $consumerName = null
    ) {
        $this->id = $id;
        $this->date = clone $date;
        $this->description = $description;
        $this->author = $author;
        $this->apiKey = $apiKey;
        $this->clientId = $clientId;
        $this->clientName = $clientName;
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

    public function jsonSerialize(): array
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

        if ($this->clientId) {
            $log['clientId'] = $this->clientId;
        }

        if ($this->clientName) {
            $log['clientName'] = $this->clientName;
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
        string $description,
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

        $author = $metadata['user_id'] ?? null;
        $apiKey = $metadata['auth_api_key'] ?? null;
        $clientId = $metadata['auth_api_client_id'] ?? null;
        $clientName = $metadata['auth_api_client_name'] ?? null;
        $api = $metadata['api'] ?? null;
        $consumer = $metadata['consumer']['name'] ?? null;

        // In the past the api key was sometimes stored, but incorrectly as an empty array due to a bug.
        // In those cases we just treat it as null.
        if (is_array($apiKey)) {
            $apiKey = null;
        }

        return new Log($id, $date, $description, $author, $apiKey, $clientId, $clientName, $api, $consumer);
    }
}
