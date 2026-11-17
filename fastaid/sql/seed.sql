INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('Admin User', 'admin@fastaid.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '000-000-0000'),
('Sarah Miller', 'sarah@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '555-0101'),
('David Chen', 'david@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '555-0102'),
('Dr. James Park', 'james@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer', '555-0201'),
('Maria Gomez', 'maria@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'volunteer', '555-0202');

INSERT INTO volunteers (user_id, certification_type, certificate_number, years_experience, verified_status, is_available, latitude, longitude, dnd_start, dnd_end) VALUES
(3, 'First Aid', 'CERT-9999', 5, 'approved', 1, 40.730, -73.990, '00:00', '00:00'),
(4, 'EMT-B', 'CERT-1234', 7, 'approved', 1, 40.720, -74.010, '00:00', '00:00'),
(5, 'CPR', 'CERT-5678', 3, 'pending', 0, 40.710, -73.995, '22:00', '07:00');

INSERT INTO requests (id, patient_id, emergency_type, description, latitude, longitude, address, conscious_breathing, status) VALUES
('r101', 2, 'Bleeding', 'Deep cut on forearm, bleeding steadily.', 40.730, -73.990, '123 Main St', 1, 'requested'),
('r102', 2, 'First Aid', 'Sprained ankle after fall.', 40.730, -73.990, '123 Main St', 1, 'completed'),
('r103', 2, 'Choking', 'Adult choking on food, conscious.', 40.730, -73.990, '123 Main St', 1, 'requested');

INSERT INTO assignments (request_id, volunteer_id) VALUES
('r102', 3);
