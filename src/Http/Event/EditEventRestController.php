<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Event\EventEditingServiceInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Http\OfferRestBaseController;

class EditEventRestController extends OfferRestBaseController
{
    /**
     * The event editor
     * @var EventEditingServiceInterface
     */
    protected $editor;

    /**
     * @var IriGeneratorInterface
     */
    protected $iriGenerator;

    /**
     * @var CalendarJSONDeserializer
     */
    protected $calendarDeserializer;

    /**
     * Constructs a RestController.
     *
     * @param EventEditingServiceInterface $eventEditor
     *   The event editor.
     */
    public function __construct(
        EventEditingServiceInterface $eventEditor,
        MediaManagerInterface $mediaManager
    ) {
        parent::__construct($eventEditor, $mediaManager);
    }
}
