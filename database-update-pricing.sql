USE bexxy_cleaning;

ALTER TABLE booking_requests
    ADD COLUMN estimated_total DECIMAL(12, 0) NULL AFTER extra_services,
    ADD COLUMN currency_code CHAR(3) NULL AFTER estimated_total;
