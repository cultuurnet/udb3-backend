SELECT
  uuid
FROM
  udb3.event_store
WHERE
  aggregate_type = 'event'
GROUP BY
  uuid;
