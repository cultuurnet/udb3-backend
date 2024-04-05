<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class KinepolisParserTest extends TestCase
{
    /*
     * @var DateParser|MockObject
     */
    private KinepolisParser $parser;

    public function setUp(): void
    {
        $dateParser = $this->createMock(DateParser::class);

        $dateParser->method('processDates')
            ->with([], 120)
            ->willReturn([]);
        $this->parser = new KinepolisParser(
            [
                15 => '123',
            ],
            [
                'KKOR' => '123',
                'SL' => '456',
            ],
            $dateParser
        );
    }

    /**
     * @test
     */
    public function first_test(): void
    {
        $result = $this->parser->getParsedMovies(Json::decodeAssociatively(file_get_contents(__DIR__ . '/samples/example.json')));

        var_dump($result);
        $this->assertEquals(
            [],
            $result
        );
    }
}
