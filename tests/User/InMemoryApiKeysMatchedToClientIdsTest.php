<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use CultuurNet\UDB3\User\Exceptions\UnmatchedApiKey;
use PHPUnit\Framework\TestCase;

final class InMemoryApiKeysMatchedToClientIdsTest extends TestCase
{
    private ApiKeysMatchedToClientIds $apiKeysMatchedToClientIds;


    public function setUp(): void
    {
        $this->apiKeysMatchedToClientIds = new InMemoryApiKeysMatchedToClientIds(
            ['existing_api_key' => 'existing_client_id']
        );
    }

    /**
     * @test
     */
    public function it_matches_api_keys(): void
    {
        $this->assertEquals(
            'existing_client_id',
            $this->apiKeysMatchedToClientIds->getClientId('existing_api_key')
        );
    }

    /**
     * @test
     */
    public function it_throws_when_it_cannot_find_a_match(): void
    {
        $this->expectException(UnmatchedApiKey::class);
        $this->expectExceptionMessage('unkown_api_key could not be matched to a clientId.');

        $this->apiKeysMatchedToClientIds->getClientId('unkown_api_key');
    }
}
