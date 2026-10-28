ALTER TABLE booking_requests
    ADD COLUMN estimated_hours DECIMAL(4, 1) NULL AFTER rooms,
    ADD COLUMN cleaner_count TINYINT UNSIGNED NULL AFTER estimated_hours,
    MODIFY COLUMN estimated_total DECIMAL(12, 2) NULL;
