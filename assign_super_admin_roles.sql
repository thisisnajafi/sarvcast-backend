-- SQL script to assign super_admin role to specific users
-- Run this script when your database is available

UPDATE users 
SET role = 'super_admin', 
    updated_at = NOW()
WHERE phone_number IN ('09136708883', '09138333293');

-- Verify the changes
SELECT id, phone_number, first_name, last_name, role, status, updated_at
FROM users
WHERE phone_number IN ('09136708883', '09138333293');

