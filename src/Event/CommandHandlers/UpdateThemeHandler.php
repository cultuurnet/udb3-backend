<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;

final class UpdateThemeHandler implements CommandHandler
{
    private Repository $eventRepository;
    private CategoryResolverInterface $eventCategoryResolver;

    public function __construct(Repository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
        $this->eventCategoryResolver = new EventCategoryResolver();
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateTheme) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        // By only having an id in the UpdateTheme command and only resolving it in the command handler, we can better
        // make sure that the term is actually in the theme domain. Otherwise some code can just dispatch an UpdateTheme
        // command with a random Category without any validation.
        $id = new CategoryID($command->getThemeId());
        $domain = new CategoryDomain('theme');
        $category = $this->eventCategoryResolver->byIdInDomain($id, $domain);
        if (!$category) {
            throw CategoryNotFound::withIdInDomain($id, $domain);
        }

        $event->updateTheme($category);

        $this->eventRepository->save($event);
    }
}
