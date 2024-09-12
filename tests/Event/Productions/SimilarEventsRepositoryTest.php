<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

class SimilarEventsRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private SimilarEventsRepository $repository;

    private DBALProductionRepository $productionsRepository;

    private SkippedSimilarEventsRepository $skippedRepository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->repository = new SimilarEventsRepository($this->getConnection());

        $this->productionsRepository = new DBALProductionRepository($this->getConnection());

        $this->skippedRepository = new SkippedSimilarEventsRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_throws_when_there_are_no_suggestions(): void
    {
        $this->expectException(SuggestionsNotFound::class);
        $this->repository->findNextSuggestion();
    }

    /**
     * @test
     */
    public function it_throws_when_there_are_no_suggestions_because_they_are_all_in_the_same_productions_already(): void
    {
        $production = $this->givenProduction(['879b282d-56fc-4ef6-a2b6-aebeb8c66a8a', '04456137-19c4-464b-9c51-272af9f689d8']);

        $suggestionInProduction = new Suggestion($production->getEventIds()[0], $production->getEventIds()[1], 0.80);
        $this->repository->add($suggestionInProduction);

        $this->expectException(SuggestionsNotFound::class);
        $this->repository->findNextSuggestion();
    }

    /**
     * @test
     */
    public function it_returns_a_suggestion(): void
    {
        $expected = new Suggestion('3ab86064-045c-42cf-b0c9-24710467031d', '04456137-19c4-464b-9c51-272af9f689d8', 0.75);
        $this->repository->add($expected);
        $actual = $this->repository->findNextSuggestion();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_returns_the_suggestion_with_the_highest_similarity(): void
    {
        $this->repository->add(new Suggestion('3ab86064-045c-42cf-b0c9-24710467031d', '04456137-19c4-464b-9c51-272af9f689d8', 0.75));

        $expected = new Suggestion('879b282d-56fc-4ef6-a2b6-aebeb8c66a8a', '04456137-19c4-464b-9c51-272af9f689d8', 0.80);
        $this->repository->add($expected);

        $actual = $this->repository->findNextSuggestion();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_never_returns_a_suggestion_of_which_both_events_are_in_the_same_production_already(): void
    {
        $production = $this->givenProduction(['879b282d-56fc-4ef6-a2b6-aebeb8c66a8a', '04456137-19c4-464b-9c51-272af9f689d8']);

        $suggestionInProduction = new Suggestion($production->getEventIds()[0], $production->getEventIds()[1], 0.80);
        $suggestionNoneInProduction = new Suggestion('3ab86064-045c-42cf-b0c9-24710467031d', '60a7c41d-2ca0-4f1a-8f6c-64a384fe7c3a', 0.75);

        $this->repository->add($suggestionInProduction);
        $this->repository->add($suggestionNoneInProduction);

        $actual = $this->repository->findNextSuggestion();
        $this->assertEquals($suggestionNoneInProduction, $actual);
    }

    /**
     * @test
     */
    public function it_returns_a_suggestion_of_which_one_event_is_in_a_production(): void
    {
        $production = $this->givenProduction(['879b282d-56fc-4ef6-a2b6-aebeb8c66a8a', '04456137-19c4-464b-9c51-272af9f689d8']);

        $suggestionInProduction = new Suggestion($production->getEventIds()[0], $production->getEventIds()[1], 0.80);
        $suggestionOnlyOneInProduction = new Suggestion('3ab86064-045c-42cf-b0c9-24710467031d', $production->getEventIds()[1], 0.75);

        $this->repository->add($suggestionInProduction);
        $this->repository->add($suggestionOnlyOneInProduction);

        $actual = $this->repository->findNextSuggestion();
        $this->assertEquals($suggestionOnlyOneInProduction, $actual);
    }

    /**
     * @test
     */
    public function it_never_returns_a_suggestion_of_a_skipped_pair_of_events(): void
    {
        $skippedPair = $this->givenSkippedPair('879b282d-56fc-4ef6-a2b6-aebeb8c66a8a', '04456137-19c4-464b-9c51-272af9f689d8');
        $skippedPairWithDifferentOrder = $this->givenSkippedPair('d0659a06-4c17-4f72-8f12-747fb1ee8b10', 'e7605f5b-9f8e-438d-9c53-f991a7e9ae36');

        $skippedSuggestion = new Suggestion($skippedPair->getEventOne(), $skippedPair->getEventTwo(), 0.80);
        $skippedSuggestionWithDifferentOrder = new Suggestion($skippedPairWithDifferentOrder->getEventTwo(), $skippedPairWithDifferentOrder->getEventOne(), 0.90);

        $suggestion = new Suggestion('3ab86064-045c-42cf-b0c9-24710467031d', '60a7c41d-2ca0-4f1a-8f6c-64a384fe7c3a', 0.75);

        $this->repository->add($skippedSuggestion);
        $this->repository->add($skippedSuggestionWithDifferentOrder);
        $this->repository->add($suggestion);

        $actual = $this->repository->findNextSuggestion();
        $this->assertEquals($suggestion, $actual);
    }

    private function givenProduction(array $events): Production
    {
        $production = Production::createEmpty('foo');

        foreach ($events as $event) {
            $production = $production->addEvent($event);
        }

        $this->productionsRepository->add($production);

        return $production;
    }

    private function givenSkippedPair(string $eventOne, string $eventTwo): SimilarEventPair
    {
        $pair = new SimilarEventPair($eventOne, $eventTwo);
        $this->skippedRepository->add($pair);
        return $pair;
    }
}
