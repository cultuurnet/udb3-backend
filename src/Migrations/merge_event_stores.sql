--
-- Create the new event_store table based on the description of the existing events table.
--
CREATE TABLE event_store LIKE events;

--
-- Put an index on the type column of the new event_store.
-- The column that needs an index can not be to long, so limit to 128 characters.
-- If the column would be too long a MySQL warning 1071 occurs.
--
ALTER TABLE event_store MODIFY type VARCHAR(128) NOT NULL;
ALTER TABLE event_store ADD INDEX (type);

--
-- Add an aggregate_type column
--
ALTER TABLE event_store ADD aggregate_type VARCHAR(128) NOT NULL;
ALTER TABLE event_store ADD INDEX (aggregate_type);

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
