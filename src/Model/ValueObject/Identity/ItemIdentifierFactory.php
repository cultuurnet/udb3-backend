<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use InvalidArgumentException;

final class ItemIdentifierFactory
{
    private string $regex;

    public function __construct(string $regex)
    {
        $this->regex = '@^' . $regex . '$@';
    }

    public function fromUrl(Url $url): ItemIdentifier
    {
        preg_match(
            $this->regex,
            $url->toString(),
            $matches
        );

        if (count($matches) === 0) {
            throw new InvalidArgumentException(
                'The given URL does not match the pattern for an event, place or organizer.'
            );
        }

        if (!array_key_exists('itemType', $matches)) {
            throw new InvalidArgumentException(
                'Regular expression pattern should capture group named "itemType"'
            );
        }

        if (!array_key_exists('itemId', $matches)) {
            throw new InvalidArgumentException(
                'Regular expression pattern should capture group named "itemId"'
            );
        }

        return new ItemIdentifier(
            $url,
            $matches['itemId'],
            new ItemType($matches['itemType'])
        );
    }
}
