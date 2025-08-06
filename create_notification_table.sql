-- Create notification table
CREATE TABLE notification (
    id INT AUTO_INCREMENT NOT NULL,
    recipient_id INT DEFAULT NULL,
    opportunity_id INT DEFAULT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    INDEX IDX_BF5476CA92F3C70 (recipient_id),
    INDEX IDX_BF5476CA9A34590F (opportunity_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Add foreign key constraints
ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA92F3C70 FOREIGN KEY (recipient_id) REFERENCES user (id);
ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA9A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunity (id); 