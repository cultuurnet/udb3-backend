<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class WebsiteLinkTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_url_and_a_label()
    {
        $url = new Url('https://google.com');
        $label = new TranslatedWebsiteLabel(new Language('nl'), new WebsiteLabel('Google'));
        $link = new WebsiteLink($url, $label);

        $this->assertEquals($url, $link->getUrl());
        $this->assertEquals($label, $link->getLabel());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_url()
    {
        $url = new Url('https://google.com');
        $label = new TranslatedWebsiteLabel(new Language('nl'), new WebsiteLabel('Google'));
        $link = new WebsiteLink($url, $label);

        $updatedUrl = new Url('https://www.google.com');
        $updatedLink = $link->withUrl($updatedUrl);

        $this->assertNotEquals($link, $updatedLink);
        $this->assertEquals($url, $link->getUrl());
        $this->assertEquals($updatedUrl, $updatedLink->getUrl());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_label()
    {
        $url = new Url('https://google.com');
        $label = new TranslatedWebsiteLabel(new Language('nl'), new WebsiteLabel('Google'));
        $link = new WebsiteLink($url, $label);

        $updatedLabel = $label->withTranslation(new Language('fr'), new WebsiteLabel('Google FR'));
        $updatedLink = $link->withLabel($updatedLabel);

        $this->assertNotEquals($link, $updatedLink);
        $this->assertEquals($label, $link->getLabel());
        $this->assertEquals($updatedLabel, $updatedLink->getLabel());
    }
}
