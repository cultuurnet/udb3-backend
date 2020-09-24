<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\ReadModel;

use Broadway\ReadModel\ReadModelInterface;
use stdClass;

class JsonDocument implements ReadModelInterface
{
    protected $id;
    protected $body;

    public function __construct($id, $rawBody = '{}')
    {
        $this->id = $id;
        $this->body = $rawBody;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getBody(): stdClass
    {
        return (object) json_decode($this->body);
    }

    public function getRawBody()
    {
        return $this->body;
    }

    /**
     * @param stdClass $body
     * @return static
     */
    public function withBody($body)
    {
        return new self($this->id, json_encode($body));
    }

    /**
     * @param callable $fn
     * @return static
     */
    public function apply(callable $fn)
    {
        $body = $fn($this->getBody());
        return $this->withBody($body);
    }
}
