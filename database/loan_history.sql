-- Loan History Table for Computer Lending System
-- This table tracks all computer lending and returning activities

CREATE TABLE IF NOT EXISTS loan_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    lender_id INT NOT NULL, -- Student who owns the device
    borrower_id INT NOT NULL, -- Student who borrowed the device
    loan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    status ENUM('borrowed', 'returned') DEFAULT 'borrowed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (lender_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_loan_history_device_id ON loan_history(device_id);
CREATE INDEX idx_loan_history_lender_id ON loan_history(lender_id);
CREATE INDEX idx_loan_history_borrower_id ON loan_history(borrower_id);
CREATE INDEX idx_loan_history_status ON loan_history(status);
CREATE INDEX idx_loan_history_loan_date ON loan_history(loan_date); 