-- Migration: add latitude and longitude to reports_archive table
-- This aligns the archive table structure with the main reports table

ALTER TABLE reports_archive
  ADD COLUMN latitude DECIMAL(10,7) NULL AFTER image_path,
  ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;

-- Optional: add indexes for spatial queries if desired
-- CREATE INDEX idx_reports_archive_latlng ON reports_archive(latitude, longitude);
