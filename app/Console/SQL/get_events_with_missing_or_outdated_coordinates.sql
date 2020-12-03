-- Get all the events that don't have GeoCoordinatesUpdated event.
SELECT
	uuid
FROM
	udb3.event_store
WHERE
    aggregate_type = 'event'
AND
	uuid NOT IN
	(
		SELECT
			uuid
		FROM
			udb3.event_store
		WHERE
			type = 'CultuurNet.UDB3.Event.Events.GeoCoordinatesUpdated'
	)
GROUP BY
	uuid

UNION

-- Get all the events that don't have a GeoCoordinatesUpdated after an update.
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
			type = 'CultuurNet.UDB3.Event.Events.GeoCoordinatesUpdated'
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
			type IN ('CultuurNet.UDB3.Event.Events.EventImportedFromUDB2',
							 'CultuurNet.UDB3.Event.Events.EventUpdatedFromUDB2')
		GROUP BY
			uuid
	) AS pl_cre
ON
	pl.uuid = pl_cre.uuid
WHERE
	pl_cre.max_cre > pl_geo.max_geo
AND
    aggregate_type = 'event';
