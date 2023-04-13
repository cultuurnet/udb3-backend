<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\Editor\WorkflowEditor;
use DateTime;
use EasyRdf\Graph;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_DESCRIPTION = 'dcterms:description';

    public function __construct(
        MainLanguageRepository $mainLanguageRepository,
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $granularEvents = $payload instanceof ConvertsToGranularEvents ? $payload->toGranularEvents() : [];
        $events = [$payload, ...$granularEvents];

        $uri = $this->iriGenerator->iri($domainMessage->getId());
        $graph = $this->graphRepository->get($uri);

        GraphEditor::for($graph)->setGeneralProperties(
            $uri,
            self::TYPE_ACTIVITEIT,
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $uri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $uri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $uri, $graph),
            Published::class => fn ($e) => $this->handlePublished($e, $uri, $graph),
            Approved::class => fn ($e) => $this->handleApproved($uri, $graph),
            Rejected::class => fn ($e) => $this->handleRejected($uri, $graph),
            FlaggedAsDuplicate::class => fn ($e) => $this->handleRejected($uri, $graph),
            FlaggedAsInappropriate::class => fn ($e) => $this->handleRejected($uri, $graph),
            EventDeleted::class => fn ($e) => $this->handleDeleted($uri, $graph),
            DescriptionUpdated::class => fn ($e) => $this->handleDescriptionUpdated($e, $uri, $graph),
        ];

        foreach ($events as $event) {
            foreach ($eventClassToHandler as $class => $handler) {
                if ($event instanceof $class) {
                    $handler($event);
                }
            }
        }
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, string $uri): void
    {
        $this->mainLanguageRepository->save($uri, new Language($event->getMainLanguage()->getCode()));
    }

    private function handleTitleUpdated(TitleUpdated $event, string $uri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'));

        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $uri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handlePublished(Published $event, string $uri, Graph $graph): void
    {
        WorkflowEditor::for($graph)->publish($uri, $event->getPublicationDate()->format(DateTime::ATOM));

        $this->graphRepository->save($uri, $graph);
    }

    private function handleApproved(string $uri, Graph $graph): void
    {
        WorkflowEditor::for($graph)->approve($uri);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleRejected(string $uri, Graph $graph): void
    {
        WorkflowEditor::for($graph)->reject($uri);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleDeleted(string $uri, Graph $graph): void
    {
        WorkflowEditor::for($graph)->delete($uri);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleDescriptionUpdated(DescriptionUpdated $event, string $uri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'));

        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_DESCRIPTION,
            $event->getDescription()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($uri, $graph);
    }
}
