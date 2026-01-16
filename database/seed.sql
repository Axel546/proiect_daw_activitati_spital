-- USE proiect_test;
-- decomenteaza linia de mai sus daca lucrezi local, lasa comentat pentru hosting (InfinityFree)

-- Inserare roluri
INSERT INTO roles (name, description) VALUES
('admin', 'System administrator with full access'),
('doctor', 'Medical doctor with activity management permissions'),
('nurse', 'Nursing staff with activity viewing and participation'),
('staff', 'General hospital staff with limited access')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Inserare departamente
INSERT INTO departments (name, description) VALUES
('Cardiologie', 'Departament de ingrijire cardiaca si cardiovasculara'),
('Urgente', 'Medicina de urgenta si ingrijire pentru traume'),
('Chirurgie', 'Proceduri chirurgicale si operatii'),
('Pediatrie', 'Ingrijire medicala pentru copii si adolescenti'),
('Radiologie', 'Servicii de imagistic medicala si diagnostic'),
('Ginecologie', 'Ingrijire medicala pentru femei si ginecologie'),
('Endocrinologie', 'Ingrijire medicala pentru probleme ale sistemului endocrin'),
('Neurologie', 'Ingrijire medicala pentru sistemul nervos'),
('Oncologie', 'Ingrijire medicala pentru cancer'),
('Ortopedie', 'Ingrijire medicala pentru sistemul osteoarticular'),
('Urologie', 'Ingrijire medicala pentru sistemul urinar')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Inserare utilizatori de test (password_hash este pentru 'password123')
INSERT INTO users (username, email, password_hash, role_id, department_id, first_name, last_name) VALUES
('admin', 'admin@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu', 
 (SELECT id FROM roles WHERE name = 'admin'), NULL, 'System', 'Administrator'),
('dr.smith', 'dr.smith@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu',
 (SELECT id FROM roles WHERE name = 'doctor'), (SELECT id FROM departments WHERE name = 'Cardiologie'), 'John', 'Smith'),
('nurse.jones', 'nurse.jones@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu',
 (SELECT id FROM roles WHERE name = 'nurse'), (SELECT id FROM departments WHERE name = 'Urgente'), 'Mary', 'Jones'),
('staff.doe', 'staff.doe@hospital.local', '$2y$10$26Ts4cP25tG2xRdxevkBSOELnpmTXqSeOUITIY3Lm9wBk0i9Tnnhu',
 (SELECT id FROM roles WHERE name = 'staff'), (SELECT id FROM departments WHERE name = 'Chirurgie'), 'Jane', 'Doe')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Inserare activitati de test
INSERT INTO activities (title, description, department_id, created_by, status, scheduled_date) VALUES
('Pregatire Chirurgie Cardiaca', 'Pregatirea salii de operatie si a echipamentului pentru procedura de chirurgie cardiaca programata.', 
 (SELECT id FROM departments WHERE name = 'Cardiologie'),
 (SELECT id FROM users WHERE username = 'dr.smith'),
 'planned', DATE_ADD(NOW(), INTERVAL 2 DAY)),
('Antrenament Raspuns Urgente', 'Sesiune lunara de antrenament pentru protocolul de raspuns la urgente pentru tot personalul.', 
 (SELECT id FROM departments WHERE name = 'Urgente'),
 (SELECT id FROM users WHERE username = 'nurse.jones'),
 'in_progress', DATE_ADD(NOW(), INTERVAL 1 DAY)),
('Rond Pediatrie', 'Ronduri zilnice dimineata pentru verificarea pacientilor pediatri si actualizarea planurilor de tratament.', 
 (SELECT id FROM departments WHERE name = 'Pediatrie'),
 (SELECT id FROM users WHERE username = 'dr.smith'),
 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Intretinere Echipament Radiologie', 'Intretinere programata si calibrare a echipamentelor de scanare MRI si CT.', 
 (SELECT id FROM departments WHERE name = 'Radiologie'),
 (SELECT id FROM users WHERE username = 'staff.doe'),
 'planned', DATE_ADD(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE title = VALUES(title);
