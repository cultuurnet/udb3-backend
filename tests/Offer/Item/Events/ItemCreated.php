<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class ItemCreated implements Serializable
{
    protected string $itemId;

    protected Language $mainLanguage;

    public function __construct(
        string $itemId,
        Language $mainLanguage = null
    ) {
        $this->itemId = $itemId;
        $this->mainLanguage = $mainLanguage ? $mainLanguage : new Language('nl');
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    /**
     * @return self
     */
    public static function deserialize(array $data)
    {
        return new self($data['itemId'], $data['main_language']);
    }

    public function serialize(): array
    {
        return [
            'itemId' => $this->itemId,
            'main_language'=> $this->mainLanguage->getCode(),
        ];
    }
}
