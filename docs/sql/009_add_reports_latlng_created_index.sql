-- Add composite index for faster map queries that filter by latitude/longitude
-- and order by recency. Run this once in your MySQL database.

-- For large tables, this may take time; plan a maintenance window if needed.
CREATE INDEX idx_reports_latlng_created ON reports (latitude, longitude, created_at);
