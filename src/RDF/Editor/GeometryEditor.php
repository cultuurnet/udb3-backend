<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class GeometryEditor
{
    private const TYPE_GEOMETRIE = 'locn:Geometry';

    private const PROPERTY_LOCATIE_GEOMETRIE = 'locn:geometry';
    private const PROPERTY_GEOMETRIE_GML = 'geosparql:asGML';

    public function setCoordinates(Resource $resource, Coordinates $coordinates): void
    {
        $gmlTemplate = '<gml:Point srsName=\'http://www.opengis.net/def/crs/OGC/1.3/CRS84\'><gml:coordinates>%s, %s</gml:coordinates></gml:Point>';
        $gmlCoordinate = sprintf($gmlTemplate, $coordinates->getLongitude()->toFloat(), $coordinates->getLatitude()->toFloat());

        $geometryResource = $resource->getGraph()->newBNode([self::TYPE_GEOMETRIE]);
        $resource->add(self::PROPERTY_LOCATIE_GEOMETRIE, $geometryResource);

        $geometryResource->set(self::PROPERTY_GEOMETRIE_GML, new Literal($gmlCoordinate, null, 'geosparql:gmlLiteral'));
    }
}
