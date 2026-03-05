<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateFaqs;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\FaqsUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faq;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaq;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class UpdateFaqsHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateFaqsHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_faqs(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $faqs = $this->createFaqs();

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId)])
            ->when(new UpdateFaqs($eventId, $faqs))
            ->then([new FaqsUpdated($eventId, $faqs)]);
    }

    /**
     * @test
     */
    public function it_replaces_existing_faqs(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $initialFaqs = $this->createFaqs();
        $updatedFaqs = $this->createFaqs('Waar kan ik parkeren?', 'Aan de ingang.', 'b4575c68-dc04-4b67-9568-63e5d00d4dde');

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new FaqsUpdated($eventId, $initialFaqs)])
            ->when(new UpdateFaqs($eventId, $updatedFaqs))
            ->then([new FaqsUpdated($eventId, $updatedFaqs)]);
    }

    /**
     * @test
     */
    public function it_ignores_updating_faqs_when_they_are_the_same(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $faqs = $this->createFaqs();

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new FaqsUpdated($eventId, $faqs)])
            ->when(new UpdateFaqs($eventId, $faqs))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_clearing_all_faqs(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';
        $faqs = $this->createFaqs();

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId), new FaqsUpdated($eventId, $faqs)])
            ->when(new UpdateFaqs($eventId, new Faqs()))
            ->then([new FaqsUpdated($eventId, new Faqs())]);
    }

    private function createFaqs(
        string $question = 'Hoe geraak ik er?',
        string $answer = 'Met de bus.',
        string $id = 'a1b2c3d4-0000-0000-0000-000000000001',
    ): Faqs {
        return (new Faqs())->with(
            new TranslatedFaq(
                new Language('nl'),
                new Faq(
                    new Uuid($id),
                    new Question($question),
                    new Answer($answer)
                )
            )
        );
    }

    private function getEventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
