<?php

namespace CultuurNet\UDB3\Actor;

class ActorImportedFromUDB2 extends ActorEvent
{
    /**
     * @var string
     */
    protected $cdbXml;

    /**
     * @var string
     */
    protected $cdbXmlNamespaceUri;

    final public function __construct(string $actorId, string $cdbXml, string $cdbXmlNamespaceUri)
    {
        parent::__construct($actorId);
        $this->cdbXml = $cdbXml;
        $this->cdbXmlNamespaceUri = $cdbXmlNamespaceUri;
    }

    public function getCdbXml(): string
    {
        return $this->cdbXml;
    }

    /**
     * @return string
     */
    public function getCdbXmlNamespaceUri(): string
    {
        return $this->cdbXmlNamespaceUri;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'cdbxml' => $this->cdbXml,
            'cdbXmlNamespaceUri' => $this->cdbXmlNamespaceUri,
        );
    }

    public static function deserialize(array $data): ActorImportedFromUDB2
    {
        $data += array(
            'cdbXmlNamespaceUri' => \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2'),
        );
        return new static(
            $data['actor_id'],
            $data['cdbxml'],
            $data['cdbXmlNamespaceUri']
        );
    }
}
