-- Migration: add latitude/longitude to orders and create rider_locations table
-- Run this once (for example from phpMyAdmin or CLI):
-- mysql -u root -p shop_db < 001_add_coords_and_rider_locations.sql

ALTER TABLE `orders`
  ADD COLUMN `latitude` DOUBLE NULL AFTER `address`,
  ADD COLUMN `longitude` DOUBLE NULL AFTER `latitude`;

CREATE TABLE IF NOT EXISTS `rider_locations` (
  `rider_id` INT NOT NULL PRIMARY KEY,
  `lat` DOUBLE NOT NULL,
  `lng` DOUBLE NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- After running this migration, the application will store geocoded coords with orders
-- and can store/retrieve live rider locations in `rider_locations`.
