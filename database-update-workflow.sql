USE bexxy_cleaning;

ALTER TABLE booking_requests
    ADD COLUMN internal_notes TEXT NOT NULL AFTER notes,
    MODIFY COLUMN status ENUM('pending', 'contacted', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending';
