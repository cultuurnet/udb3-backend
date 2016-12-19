--
-- Insert the existing events from the previous stores order by recorded_on in the new event_store.
--
INSERT INTO
	event_store (uuid, playhead, payload, metadata, recorded_on, type, aggregate_type)
SELECT
  uuid,
  playhead,
  payload,
  metadata,
  recorded_on,
  type,
  aggregate_type
FROM (
    SELECT *, 'event' AS aggregate_type FROM events
    UNION ALL
    SELECT *, 'label' AS aggregate_type FROM labels
    UNION ALL
    SELECT *, 'media_object' AS aggregate_type FROM media_objects
    UNION ALL
    SELECT *, 'organizer' AS aggregate_type FROM organizers
    UNION ALL
    SELECT *, 'place' AS aggregate_type FROM places
    UNION ALL
    SELECT *, 'role' AS aggregate_type FROM roles
    UNION ALL
    SELECT *, 'variation' AS aggregate_type FROM variations
)
AS
	event_stores
ORDER BY
	event_stores.recorded_on
