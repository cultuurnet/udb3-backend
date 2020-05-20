<?php

namespace CultuurNet\UDB3\Offer\Item\Events;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Language;

class ItemCreated implements SerializableInterface
{
    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var Language
     */
    protected $mainLanguage;

    /**
     * @param string $itemId
     * @param Language $mainLanguage
     */
    public function __construct(
        $itemId,
        Language $mainLanguage = null
    ) {
        $this->itemId = $itemId;
        $this->mainLanguage = $mainLanguage ? $mainLanguage : new Language('nl');
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return Language
     */
    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }

    /**
     * @param array $data
     * @return static
     */
    public static function deserialize(array $data)
    {
        return new static($data['itemId'], $data['main_language']);
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'itemId' => $this->itemId,
            'main_language'=> $this->mainLanguage->getCode(),
        ];
    }
}
