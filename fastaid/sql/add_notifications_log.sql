CREATE TABLE IF NOT EXISTS notifications_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  channel ENUM('email','sms','push') DEFAULT 'email',
  recipient VARCHAR(100),
  subject VARCHAR(200),
  body TEXT,
  status ENUM('pending','sent','failed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status (status)
);
