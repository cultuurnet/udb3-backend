<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use DateTime;
use JsonSerializable;
use ValueObjects\StringLiteral\StringLiteral;

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

    public function __construct(
        DateTime $date,
        StringLiteral $description,
        StringLiteral $author = null,
        string $apiKey = null,
        string $api = null
    ) {
        $this->date = clone $date;
        $this->description = $description;
        $this->author = $author;
        $this->apiKey = $apiKey;
        $this->api = $api;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $log = [
            'date' => $this->date->format('c'),
            'description' => $this->description->toNative(),
        ];

        if ($this->author) {
            $log['author'] = $this->author->toNative();
        }

        if ($this->apiKey) {
            $log['apiKey'] = $this->apiKey;
        }

        if ($this->api) {
            $log['api'] = $this->api;
        }

        return $log;
    }
}
