<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use DateTime;
use JsonSerializable;

class Log implements JsonSerializable
{
    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var String
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
        DateTime $date,
        string $description,
        string $author = null,
        string $apiKey = null,
        string $api = null,
        string $consumerName = null
    ) {
        $this->date = clone $date;
        $this->description = $description;
        $this->author = $author;
        $this->apiKey = $apiKey;
        $this->api = $api;
        $this->consumerName = $consumerName;
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
}
