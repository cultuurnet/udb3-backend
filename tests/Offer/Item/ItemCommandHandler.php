<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item;

use CultuurNet\UDB3\Offer\Item\Commands\AddImage;
use CultuurNet\UDB3\Offer\Item\Commands\ImportImages;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Approve;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Publish;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Reject;
use CultuurNet\UDB3\Offer\Item\Commands\RemoveImage;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateDescription;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateImage;
use CultuurNet\UDB3\Offer\Item\Commands\SelectMainImage;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\OfferCommandHandler;

final class ItemCommandHandler extends OfferCommandHandler
{
    protected function getAddImageClassName(): string
    {
        return AddImage::class;
    }

    protected function getUpdateImageClassName(): string
    {
        return UpdateImage::class;
    }

    protected function getRemoveImageClassName(): string
    {
        return RemoveImage::class;
    }

    protected function getSelectMainImageClassName(): string
    {
        return SelectMainImage::class;
    }

    protected function getImportImagesClassName(): string
    {
        return ImportImages::class;
    }

    protected function getUpdateDescriptionClassName(): string
    {
        return UpdateDescription::class;
    }

    protected function getUpdateTypicalAgeRangeClassName(): string
    {
        return UpdateTypicalAgeRange::class;
    }

    protected function getDeleteTypicalAgeRangeClassName(): string
    {
        return DeleteTypicalAgeRange::class;
    }

    protected function getUpdateContactPointClassName(): string
    {
        return UpdateContactPoint::class;
    }

    protected function getUpdateBookingInfoClassName(): string
    {
        return UpdateBookingInfo::class;
    }

    protected function getPublishClassName(): string
    {
        return Publish::class;
    }

    protected function getApproveClassName(): string
    {
        return Approve::class;
    }

    protected function getRejectClassName(): string
    {
        return Reject::class;
    }

    protected function getFlagAsDuplicateClassName(): string
    {
        return FlagAsDuplicate::class;
    }

    protected function getFlagAsInappropriateClassName(): string
    {
        return FlagAsInappropriate::class;
    }
}
