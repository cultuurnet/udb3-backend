-- Get all the places that don't have GeoCoordinatesUpdated event.
SELECT
	uuid
FROM
	udb3.event_store
WHERE
    aggregate_type = 'place'
AND
	uuid NOT IN
	(
		SELECT
			uuid
		FROM
			udb3.event_store
		WHERE
			type = 'CultuurNet.UDB3.Place.Events.GeoCoordinatesUpdated'
	)
GROUP BY
	uuid

UNION

-- Get all the places that don't have a GeoCoordinatesUpdated after an update.
SELECT
	DISTINCT (pl.uuid)
FROM
	udb3.event_store AS pl
INNER JOIN
	(
		SELECT
			uuid, MAX(id) AS max_geo
		FROM
			udb3.event_store
		WHERE
			type = 'CultuurNet.UDB3.Place.Events.GeoCoordinatesUpdated'
		GROUP BY
			uuid
	) AS pl_geo
ON
	pl.uuid = pl_geo.uuid
INNER JOIN
	(
		SELECT
			uuid, MAX(id) AS max_cre
		FROM
			udb3.event_store
		WHERE
			type IN ('CultuurNet.UDB3.Place.Events.PlaceCreated',
							 'CultuurNet.UDB3.Place.Events.MajorInfoUpdated',
							 'CultuurNet.UDB3.Place.Events.AddressUpdated',
							 'CultuurNet.UDB3.Place.Events.PlaceImportedFromUDB2',
							 'CultuurNet.UDB3.Place.Events.PlaceUpdatedFromUDB2')
		GROUP BY
			uuid
	) AS pl_cre
ON
	pl.uuid = pl_cre.uuid
WHERE
	pl_cre.max_cre > pl_geo.max_geo
AND
    aggregate_type = 'place';
