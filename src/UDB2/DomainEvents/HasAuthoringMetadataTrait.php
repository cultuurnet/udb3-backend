<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use ValueObjects\StringLiteral\StringLiteral;

trait HasAuthoringMetadataTrait
{
    /**
     * @var \DateTimeImmutable
     */
    protected $time;

    /**
     * @var StringLiteral
     */
    protected $author;

    private function setTime(\DateTimeImmutable $time)
    {
        $this->time = $time;
    }

    private function setAuthor(StringLiteral $author)
    {
        $this->author = $author;
    }

    /**
     * @return StringLiteral
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'time' => $this->getTime()->format(\DateTime::ISO8601),
            'author' => (string) $this->getAuthor(),
        ];
    }
}
