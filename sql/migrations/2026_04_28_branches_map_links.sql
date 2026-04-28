-- Migration: add map / Waze deep-links to branches
-- Apply on existing installs. Safe to re-run.

ALTER TABLE branches
  ADD COLUMN google_map_link VARCHAR(500) DEFAULT NULL AFTER google_map_embed,
  ADD COLUMN waze_link       VARCHAR(500) DEFAULT NULL AFTER google_map_link;
