<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\Moderation\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface Organizer
{
    public function getId(): UUID;

    public function getMainLanguage(): Language;

    public function getName(): TranslatedTitle;

    public function getUrl(): ?Url;

    public function getDescription(): ?TranslatedDescription;

    public function getEducationalDescription(): ?TranslatedDescription;

    public function getAddress(): ?TranslatedAddress;

    public function getGeoCoordinates(): ?Coordinates;

    public function getLabels(): Labels;

    public function getContactPoint(): ContactPoint;

    public function getImages(): Images;

    public function getWorkflowStatus(): WorkflowStatus;
}
