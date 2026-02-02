-- Add latitude and longitude columns to program_csr table
ALTER TABLE `program_csr` 
ADD COLUMN `latitude` DECIMAL(10, 8) DEFAULT NULL AFTER `lokasi`,
ADD COLUMN `longitude` DECIMAL(11, 8) DEFAULT NULL AFTER `latitude`;

