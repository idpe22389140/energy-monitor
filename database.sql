-- ============================================================
-- Energy Monitoring System - Βάση Δεδομένων
-- Πανεπιστήμιο Δυτικής Αττικής
-- Τμήμα Μηχανικών Βιομηχανικής Σχεδίασης και Παραγωγής
-- ============================================================

CREATE DATABASE IF NOT EXISTS energy_monitor
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE energy_monitor;

-- -----------------------------------------------------------
-- Πίνακας 1: zones (Ζώνες εργοστασίου)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS zones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------
-- Πίνακας 2: machines (Μηχανήματα)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS machines (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    zone_id         INT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    model           VARCHAR(100),
    rated_power_kw  DECIMAL(8,2) NOT NULL COMMENT 'Ονομαστική ισχύς σε kW',
    status          ENUM('active','inactive','maintenance') DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Πίνακας 3: energy_readings (Μετρήσεις κατανάλωσης)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS energy_readings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    machine_id  INT NOT NULL,
    kwh         DECIMAL(10,3) NOT NULL COMMENT 'Κατανάλωση σε kWh',
    recorded_at DATETIME NOT NULL,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------
-- Δεδομένα: Ζώνες εργοστασίου
-- -----------------------------------------------------------
INSERT INTO zones (name, description) VALUES
('Γραμμή Παραγωγής Α', 'Κύρια γραμμή - Τμήμα συναρμολόγησης'),
('Γραμμή Παραγωγής Β', 'Δευτερεύουσα γραμμή - Τμήμα συσκευασίας'),
('Αποθήκη',            'Χώρος αποθήκευσης και logistics'),
('Γραφεία',            'Διοικητικοί χώροι και υπολογιστές');

-- -----------------------------------------------------------
-- Δεδομένα: Μηχανήματα
-- -----------------------------------------------------------
INSERT INTO machines (zone_id, name, model, rated_power_kw, status) VALUES
(1, 'CNC Φρέζα #1',          'HAAS VF-2',       15.00, 'active'),
(1, 'Ρομποτικός Βραχίονας',  'KUKA KR 10',       8.50, 'active'),
(1, 'Πρέσα Υδραυλική',       'SCHULER 200T',    22.00, 'active'),
(2, 'Μηχανή Συσκευασίας',    'BOSCH Pack 201',   5.50, 'active'),
(2, 'Μεταφορική Ταινία',     'FlexLink XB',      2.20, 'active'),
(3, 'Reach Truck',            'Still MX-X',      12.00, 'active'),
(3, 'Σύστημα Ψύξης',         'Carrier 30XW',    35.00, 'active'),
(4, 'HVAC Γραφείων',          'Daikin VRV',      18.00, 'active'),
(4, 'UPS - Server Room',      'APC Galaxy 5000', 10.00, 'active');

-- -----------------------------------------------------------
-- Δεδομένα: Μετρήσεις (τελευταίες 7 ημέρες)
-- -----------------------------------------------------------
INSERT INTO energy_readings (machine_id, kwh, recorded_at, notes) VALUES
(1, 45.200, NOW() - INTERVAL 6 DAY, 'Κανονική λειτουργία'),
(2, 18.750, NOW() - INTERVAL 6 DAY, NULL),
(3, 88.000, NOW() - INTERVAL 6 DAY, 'Πλήρης παραγωγή'),
(4, 12.100, NOW() - INTERVAL 6 DAY, NULL),
(7, 140.500,NOW() - INTERVAL 6 DAY, 'Υψηλή θερμοκρασία'),
(8, 72.000, NOW() - INTERVAL 6 DAY, NULL),
(1, 42.100, NOW() - INTERVAL 5 DAY, NULL),
(2, 20.300, NOW() - INTERVAL 5 DAY, NULL),
(3, 91.500, NOW() - INTERVAL 5 DAY, 'Πλήρης παραγωγή'),
(7, 138.200,NOW() - INTERVAL 5 DAY, NULL),
(1, 50.000, NOW() - INTERVAL 4 DAY, 'Υπερωρίες'),
(3, 95.000, NOW() - INTERVAL 4 DAY, 'Μέγιστη παραγωγή'),
(7, 145.000,NOW() - INTERVAL 4 DAY, 'Καλοκαιρινό peak'),
(1, 38.500, NOW() - INTERVAL 3 DAY, NULL),
(3, 78.000, NOW() - INTERVAL 3 DAY, 'Μειωμένη παραγωγή'),
(7, 130.000,NOW() - INTERVAL 3 DAY, NULL),
(1, 44.800, NOW() - INTERVAL 2 DAY, NULL),
(2, 19.600, NOW() - INTERVAL 2 DAY, NULL),
(3, 85.500, NOW() - INTERVAL 2 DAY, NULL),
(7, 142.000,NOW() - INTERVAL 2 DAY, NULL),
(1, 46.300, NOW() - INTERVAL 1 DAY, NULL),
(3, 89.000, NOW() - INTERVAL 1 DAY, NULL),
(7, 136.500,NOW() - INTERVAL 1 DAY, NULL),
(1, 22.100, NOW(),                  'Μισή μέρα'),
(3, 44.500, NOW(),                  'Μισή μέρα'),
(7, 68.000, NOW(),                  'Τρέχουσα μέτρηση');
