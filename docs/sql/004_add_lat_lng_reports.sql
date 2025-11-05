-- Migration: add latitude and longitude to reports table

ALTER TABLE reports
  ADD COLUMN latitude DECIMAL(10,7) NULL AFTER image_path,
  ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;

-- Add indexes for spatial queries if desired
-- CREATE INDEX idx_reports_latlng ON reports(latitude, longitude);
