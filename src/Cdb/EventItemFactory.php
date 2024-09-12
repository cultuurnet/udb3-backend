<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Data_Keyword;
use CultureFeed_Cdb_Item_Event;
use SimpleXMLElement;

class EventItemFactory implements EventItemFactoryInterface
{
    private string $namespaceUri;

    /**
     * @param string $namespaceUri
     */
    public function __construct($namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    /**
     * @param string $cdbXml
     * @throws \CultureFeed_Cdb_ParseException
     */
    public function createFromCdbXml($cdbXml): CultureFeed_Cdb_Item_Event
    {
        return self::createEventFromCdbXml($this->namespaceUri, $cdbXml);
    }

    public static function createEventFromCdbXml(string $namespaceUri, string $cdbXml): CultureFeed_Cdb_Item_Event
    {
        $udb2SimpleXml = new SimpleXMLElement(
            $cdbXml,
            0,
            false,
            $namespaceUri
        );

        // The event might be wrapped in a <cdbxml> tag.
        if ($udb2SimpleXml->getName() == 'cdbxml' && isset($udb2SimpleXml->event)) {
            $udb2SimpleXml = $udb2SimpleXml->event;
        }

        $event = CultureFeed_Cdb_Item_Event::parseFromCdbXml($udb2SimpleXml);

        if (self::isEventOlderThanSplitKeywordFix($event)) {
            $event = self::splitKeywordTagOnSemiColon($event);
        }

        return $event;
    }

    /**
     * UDB2 contained a bug that allowed for a keyword to have a semicolon.
     */
    private static function splitKeywordTagOnSemiColon(
        CultureFeed_Cdb_Item_Event $event
    ): CultureFeed_Cdb_Item_Event {
        $event = clone $event;

        /**
         * @var CultureFeed_Cdb_Data_Keyword[] $keywords
         */
        $keywords = $event->getKeywords(true);

        foreach ($keywords as $keyword) {
            $individualKeywords = explode(';', $keyword->getValue());

            if (count($individualKeywords) > 1) {
                $event->deleteKeyword($keyword);

                foreach ($individualKeywords as $individualKeyword) {
                    $newKeyword = new CultureFeed_Cdb_Data_Keyword(
                        trim($individualKeyword),
                        $keyword->isVisible()
                    );
                    $event->addKeyword($newKeyword);
                }
            }
        }

        return $event;
    }

    private static function isEventOlderThanSplitKeywordFix(
        CultureFeed_Cdb_Item_Event $event
    ): bool {
        return $event->getLastUpdated() < '2016-03-10T00:00:00';
    }
}
