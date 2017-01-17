--
-- Query to get the conflicting uuids, this uuids which are re-used for different aggregate types.
--
SELECT
  uuid,
  count(*) AS total
FROM
  (
    SELECT DISTINCT(uuid) FROM events
    UNION ALL
    SELECT DISTINCT(uuid) FROM labels
    UNION ALL
    SELECT DISTINCT(uuid) FROM media_objects
    UNION ALL
    SELECT DISTINCT(uuid) FROM organizers
    UNION ALL
    SELECT DISTINCT(uuid) FROM places
    UNION ALL
    SELECT DISTINCT(uuid) FROM roles
    UNION ALL
    SELECT DISTINCT(uuid) FROM variations
  )
    AS
  union_all
GROUP BY
  uuid
HAVING
  total > 1
