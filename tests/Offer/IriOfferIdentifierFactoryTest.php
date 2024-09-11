<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IriOfferIdentifierFactoryTest extends TestCase
{
    private string $regex;

    private IriOfferIdentifierFactory $iriOfferIdentifierFactory;

    public function setUp(): void
    {
        $this->regex = 'https?://foo\.bar/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)';
        $this->iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            $this->regex
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_malformed_url(): void
    {
        $this->expectException(RuntimeException::class);

        $this->iriOfferIdentifierFactory->fromIri(
            new Url('https://du.de/sweet')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_an_unsupported_offer_type(): void
    {
        $this->expectException(RuntimeException::class);

        $this->iriOfferIdentifierFactory->fromIri(
            new Url('https://culudb-silex.dev:8080/kwiet/foo-bar')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_the_offertype_index_is_not_found_in_the_regex(): void
    {
        $iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            'https?://foo\.bar/(?<offer>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular expression pattern should capture group named "offertype"');

        $iriOfferIdentifierFactory->fromIri(
            new Url('https://foo.bar/place/foo-bar')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_the_offerid_index_is_not_found_in_the_regex(): void
    {
        $iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            'https?://foo\.bar/(?<offertype>[event|place]+)/(?<id>[a-zA-Z0-9\-]+)'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular expression pattern should capture group named "offerid"');

        $iriOfferIdentifierFactory->fromIri(
            new Url('https://foo.bar/place/foo-bar')
        );
    }

    /**
     * @test
     */
    public function it_returns_a_correct_iri_offer_identifier_when_url_is_valid(): void
    {
        $iriOfferIdentifier = $this->iriOfferIdentifierFactory->fromIri(
            new Url('https://foo.bar/place/1234')
        );

        $expectedIriOfferIdentifier = new IriOfferIdentifier(
            new Url('https://foo.bar/place/1234'),
            '1234',
            OfferType::place()
        );

        $this->assertEquals($expectedIriOfferIdentifier, $iriOfferIdentifier);
    }
}
