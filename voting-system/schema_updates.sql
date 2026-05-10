-- 1. Update `newaccountregistration` table
ALTER TABLE `newaccountregistration` 
  DROP COLUMN `password`, 
  DROP COLUMN `retype_password`,
  ADD COLUMN `password_hash` VARCHAR(255) NOT NULL AFTER `email`,
  ADD COLUMN `voters_id_path` VARCHAR(255) DEFAULT NULL AFTER `voters_id_number`;

-- 2. Update `voting_system` table
ALTER TABLE `voting_system`
  ADD COLUMN `voted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL,
  ADD COLUMN `user_agent` VARCHAR(255) DEFAULT NULL,
  ADD UNIQUE INDEX `unique_username` (`username`);

-- 3. Create `candidates` table
CREATE TABLE IF NOT EXISTS `candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `party` VARCHAR(100) NOT NULL,
  `photo_url` VARCHAR(255) DEFAULT NULL
);

-- Insert dummy candidates
INSERT INTO `candidates` (`name`, `party`, `photo_url`) VALUES
('Person A', 'Party Alpha', 'photo/candidate_a.jpg'),
('Person B', 'Party Beta', 'photo/candidate_b.jpg'),
('Person C', 'Party Gamma', 'photo/candidate_c.jpg');

-- 4. Create `voting_config` table
CREATE TABLE IF NOT EXISTS `voting_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `election_name` VARCHAR(255) NOT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL
);

-- Insert default config (adjust dates as needed)
INSERT INTO `voting_config` (`election_name`, `start_time`, `end_time`) VALUES
('General Election 2026', '2026-05-01 00:00:00', '2026-05-31 23:59:59');

-- 5. Create `login_attempts` table
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Create `password_resets` table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Ensure UNIQUE constraint on `voters_id_number`
ALTER TABLE `newaccountregistration` ADD UNIQUE INDEX `unique_voters_id_number` (`voters_id_number`);
