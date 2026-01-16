-- USE proiect_test;
-- decomenteaza linia de mai sus daca lucrezi local, lasa comentat pentru hosting (InfinityFree)

-- Insert roles
INSERT INTO roles (name, description) VALUES
('admin', 'System administrator with full access'),
('doctor', 'Medical doctor with activity management permissions'),
('nurse', 'Nursing staff with activity viewing and participation'),
('staff', 'General hospital staff with limited access')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert departments
INSERT INTO departments (name, description) VALUES
('Cardiology', 'Heart and cardiovascular care department'),
('Emergency', 'Emergency medicine and trauma care'),
('Surgery', 'Surgical procedures and operations'),
('Pediatrics', 'Medical care for children and adolescents'),
('Radiology', 'Medical imaging and diagnostic services')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert sample users (password_hash is for 'password123')
INSERT INTO users (username, email, password_hash, role_id, department_id, first_name, last_name) VALUES
('admin', 'admin@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu', 
 (SELECT id FROM roles WHERE name = 'admin'), NULL, 'System', 'Administrator'),
('dr.smith', 'dr.smith@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu',
 (SELECT id FROM roles WHERE name = 'doctor'), (SELECT id FROM departments WHERE name = 'Cardiology'), 'John', 'Smith'),
('nurse.jones', 'nurse.jones@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu',
 (SELECT id FROM roles WHERE name = 'nurse'), (SELECT id FROM departments WHERE name = 'Emergency'), 'Mary', 'Jones'),
('staff.doe', 'staff.doe@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu',
 (SELECT id FROM roles WHERE name = 'staff'), (SELECT id FROM departments WHERE name = 'Surgery'), 'Jane', 'Doe')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Insert sample activities
INSERT INTO activities (title, description, department_id, created_by, status, scheduled_date) VALUES
('Cardiac Surgery Preparation', 'Prepare operating room and equipment for scheduled cardiac surgery procedure.', 
 (SELECT id FROM departments WHERE name = 'Cardiology'),
 (SELECT id FROM users WHERE username = 'dr.smith'),
 'planned', DATE_ADD(NOW(), INTERVAL 2 DAY)),
('Emergency Response Training', 'Monthly emergency response protocol training session for all staff.', 
 (SELECT id FROM departments WHERE name = 'Emergency'),
 (SELECT id FROM users WHERE username = 'nurse.jones'),
 'in_progress', DATE_ADD(NOW(), INTERVAL 1 DAY)),
('Pediatric Ward Round', 'Daily morning rounds to check on pediatric patients and update treatment plans.', 
 (SELECT id FROM departments WHERE name = 'Pediatrics'),
 (SELECT id FROM users WHERE username = 'dr.smith'),
 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Radiology Equipment Maintenance', 'Scheduled maintenance and calibration of MRI and CT scanning equipment.', 
 (SELECT id FROM departments WHERE name = 'Radiology'),
 (SELECT id FROM users WHERE username = 'staff.doe'),
 'planned', DATE_ADD(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE title = VALUES(title);
