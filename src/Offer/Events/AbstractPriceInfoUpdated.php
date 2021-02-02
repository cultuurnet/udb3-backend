<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\PriceInfo\PriceInfo;

abstract class AbstractPriceInfoUpdated extends AbstractEvent
{
    /**
     * @var PriceInfo
     */
    protected $priceInfo;

    final public function __construct(string $itemId, PriceInfo $priceInfo)
    {
        parent::__construct($itemId);
        $this->priceInfo = $priceInfo;
    }

    public function getPriceInfo(): PriceInfo
    {
        return $this->priceInfo;
    }

    public function serialize(): array
    {
        return [
            'item_id' => $this->itemId,
            'price_info' => $this->priceInfo->serialize(),
        ];
    }

    public static function deserialize(array $data): AbstractPriceInfoUpdated
    {
        return new static(
            $data['item_id'],
            PriceInfo::deserialize($data['price_info'])
        );
    }
}
