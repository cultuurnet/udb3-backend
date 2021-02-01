<?php

namespace CultuurNet\UDB3\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ValueObjects\Web\Url;

class IriOfferIdentifierFactoryTest extends TestCase
{
    /**
     * @var string
     */
    private $regex;

    /**
     * @var IriOfferIdentifierFactory
     */
    private $iriOfferIdentifierFactory;

    public function setUp()
    {
        $this->regex = 'https?://foo\.bar/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)';
        $this->iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            $this->regex
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_a_malformed_url()
    {
        $this->expectException(RuntimeException::class);

        $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative('https://du.de/sweet')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_using_an_unsupported_offer_type()
    {
        $this->expectException(RuntimeException::class);

        $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative('https://culudb-silex.dev:8080/kwiet/foo-bar')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_the_offertype_index_is_not_found_in_the_regex()
    {
        $iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            'https?://foo\.bar/(?<offer>[event|place]+)/(?<offerid>[a-zA-Z0-9\-]+)'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular expression pattern should capture group named "offertype"');

        $iriOfferIdentifierFactory->fromIri(
            Url::fromNative('https://foo.bar/place/foo-bar')
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_when_the_offerid_index_is_not_found_in_the_regex()
    {
        $iriOfferIdentifierFactory = new IriOfferIdentifierFactory(
            'https?://foo\.bar/(?<offertype>[event|place]+)/(?<id>[a-zA-Z0-9\-]+)'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular expression pattern should capture group named "offerid"');

        $iriOfferIdentifierFactory->fromIri(
            Url::fromNative('https://foo.bar/place/foo-bar')
        );
    }

    /**
     * @test
     */
    public function it_returns_a_correct_iri_offer_identifier_when_url_is_valid()
    {
        $iriOfferIdentifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative('https://foo.bar/place/1234')
        );

        $expectedIriOfferIdentifier = new IriOfferIdentifier(
            Url::fromNative('https://foo.bar/place/1234'),
            '1234',
            OfferType::PLACE()
        );

        $this->assertEquals($expectedIriOfferIdentifier, $iriOfferIdentifier);
    }
}
