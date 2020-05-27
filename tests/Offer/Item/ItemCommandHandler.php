<?php

namespace CultuurNet\UDB3\Offer\Item;

use CultuurNet\UDB3\Offer\Item\Commands\AddImage;
use CultuurNet\UDB3\Offer\Item\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteItem;
use CultuurNet\UDB3\Offer\Item\Commands\ImportImages;
use CultuurNet\UDB3\Offer\Item\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Item\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Approve;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Publish;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Reject;
use CultuurNet\UDB3\Offer\Item\Commands\RemoveImage;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateDescription;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateImage;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTheme;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Item\Commands\SelectMainImage;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Offer\Item\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\OfferCommandHandler;

class ItemCommandHandler extends OfferCommandHandler
{
    protected function getAddLabelClassName()
    {
        return AddLabel::class;
    }

    protected function getRemoveLabelClassName()
    {
        return RemoveLabel::class;
    }

    protected function getImportLabelsClassName()
    {
        return ImportLabels::class;
    }

    protected function getAddImageClassName()
    {
        return AddImage::class;
    }

    protected function getUpdateImageClassName()
    {
        return UpdateImage::class;
    }

    protected function getRemoveImageClassName()
    {
        return RemoveImage::class;
    }

    protected function getSelectMainImageClassName()
    {
        return SelectMainImage::class;
    }

    /**
     * @return string
     */
    protected function getImportImagesClassName()
    {
        return ImportImages::class;
    }

    /**
     * @return string
     */
    protected function getUpdateTitleClassName()
    {
        return UpdateTitle::class;
    }

    protected function getUpdateDescriptionClassName()
    {
        return UpdateDescription::class;
    }

    /**
     * @inheritdoc
     */
    protected function getUpdateCalendarClassName()
    {
        return UpdateCalendar::class;
    }

    protected function getUpdateTypicalAgeRangeClassName()
    {
        return UpdateTypicalAgeRange::class;
    }

    protected function getDeleteTypicalAgeRangeClassName()
    {
        return DeleteTypicalAgeRange::class;
    }

    protected function getUpdateOrganizerClassName()
    {
        return UpdateOrganizer::class;
    }

    protected function getDeleteOrganizerClassName()
    {
        return DeleteOrganizer::class;
    }

    protected function getDeleteCurrentOrganizerClassName()
    {
        return DeleteCurrentOrganizer::class;
    }

    protected function getUpdateContactPointClassName()
    {
        return UpdateContactPoint::class;
    }

    protected function getUpdateBookingInfoClassName()
    {
        return UpdateBookingInfo::class;
    }

    protected function getUpdatePriceInfoClassName()
    {
        return UpdatePriceInfo::class;
    }

    protected function getDeleteOfferClassName()
    {
        return DeleteItem::class;
    }

    protected function getPublishClassName()
    {
        return Publish::class;
    }

    protected function getApproveClassName()
    {
        return Approve::class;
    }

    protected function getRejectClassName()
    {
        return Reject::class;
    }

    protected function getFlagAsDuplicateClassName()
    {
        return FlagAsDuplicate::class;
    }

    protected function getFlagAsInappropriateClassName()
    {
        return FlagAsInappropriate::class;
    }

    protected function getUpdateTypeClassName()
    {
        return UpdateType::class;
    }

    protected function getUpdateThemeClassName()
    {
        return UpdateTheme::class;
    }

    protected function getUpdateFacilitiesClassName()
    {
        return UpdateFacilities::class;
    }
}
