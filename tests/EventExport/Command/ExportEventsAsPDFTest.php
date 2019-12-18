<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsPDFTest extends TestCase
{
    /**
     * @var ExportEventsAsPDF
     */
    private $export;

    public function setUp()
    {
        $this->export = new ExportEventsAsPDF(
            new EventExportQuery('*.*'),
            'vlieg',
            'http://foo.bar/logo.svg',
            new Title('title'),
            WebArchiveTemplate::TIPS()
        );
    }

    /**
     * @test
     */
    public function it_includes_a_query()
    {
        $query = new EventExportQuery('*.*');
        $this->assertEquals($query, $this->export->getQuery());
    }

    /**
     * @test
     */
    public function it_includes_a_brand()
    {
        $query = 'vlieg';
        $this->assertEquals($query, $this->export->getBrand());
    }

    /**
     * @test
     */
    public function it_includes_a_title()
    {
        $query = new Title('title');
        $this->assertEquals($query, $this->export->getTitle());
    }

    /**
     * @test
     */
    public function it_includes_a_template()
    {
        $template = WebArchiveTemplate::TIPS();
        $this->assertEquals($template, $this->export->getTemplate());
    }

    /**
     * @test
     */
    public function it_allows_to_specify_a_notification_email_address()
    {
        $email = new EmailAddress('john@doe.com');
        $newExport = $this->export->withEmailNotificationTo($email);

        $this->assertEquals($email, $newExport->getAddress());

        $this->assertNotSame($this->export, $newExport);
    }

    /**
     * @test
     */
    public function it_allows_to_specify_a_subtitle()
    {
        $subtitle = new Subtitle('Some subtitle');
        $newExport = $this->export->withSubtitle($subtitle);

        $this->assertEquals($subtitle, $newExport->getSubtitle());

        $this->assertNotSame($this->export, $newExport);
    }

    /**
     * @test
     */
    public function it_allows_to_specify_a_footer()
    {
        $footer = new Footer('footer text');
        $newExport = $this->export->withFooter($footer);

        $this->assertEquals($footer, $newExport->getFooter());

        $this->assertNotSame($this->export, $newExport);
    }

    /**
     * @test
     */
    public function it_allows_to_specify_a_publisher()
    {
        $publisher = new Publisher('publisher text');
        $newExport = $this->export->withPublisher($publisher);

        $this->assertEquals($publisher, $newExport->getPublisher());

        $this->assertNotSame($this->export, $newExport);
    }

    /**
     * @test
     */
    public function it_allows_to_specify_a_selection_of_events_to_include()
    {
        $selection = [
            'some-id',
            'another-id'
        ];
        $newExport = $this->export->withSelection($selection);

        $this->assertEquals($selection, $newExport->getSelection());

        $this->assertNotSame($this->export, $newExport);
    }
}
