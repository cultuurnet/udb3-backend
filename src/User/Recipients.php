<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class Recipients implements IteratorAggregate
{
    /** @var UserIdentityDetails[] */
    private array $recipients = [];

    public function __construct(UserIdentityDetails ...$recipients)
    {
        foreach ($recipients as $recipient) {
            $this->add($recipient);
        }
    }

    public function add(UserIdentityDetails $recipient): self
    {
        $this->recipients[$recipient->getUserId()] = $recipient;
        return $this;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return Traversable<string, UserIdentityDetails>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->recipients);
    }
}
