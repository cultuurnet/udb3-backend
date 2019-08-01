<?php

namespace CultuurNet\UDB3\UDB2\Actor\Events;

use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ActorUpdatedEnrichedWithCdbXmlTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $id = new StringLiteral('foo');
        $time = new \DateTimeImmutable();
        $author = new StringLiteral('me@example.com');
        $url = Url::fromNative('http://www.some.url');
        $cdbXml = new StringLiteral(file_get_contents(__DIR__ . '/actor.xml'));
        $cdbXmlNamespaceUri = new StringLiteral(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $event = new ActorUpdatedEnrichedWithCdbXml(
            $id,
            $time,
            $author,
            $url,
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->assertEquals($id, $event->getActorId());
        $this->assertEquals($time, $event->getTime());
        $this->assertEquals($author, $event->getAuthor());
        $this->assertEquals($url, $event->getUrl());
        $this->assertEquals($cdbXml, $event->getCdbXml());
        $this->assertEquals($cdbXmlNamespaceUri, $event->getCdbXmlNamespaceUri());
    }
}
