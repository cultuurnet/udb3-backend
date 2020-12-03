SELECT
  uuid
FROM
  udb3.event_store
WHERE
  aggregate_type = 'place'
GROUP BY
  uuid;
