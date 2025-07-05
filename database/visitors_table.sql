-- Visitors table for campus visitor management
CREATE TABLE IF NOT EXISTS visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_name VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    purpose VARCHAR(100) NOT NULL,
    person_to_visit VARCHAR(100) NOT NULL,
    department VARCHAR(100) NULL,
    id_number VARCHAR(50) NULL,
    notes TEXT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for visitors table
CREATE INDEX idx_visitors_created_at ON visitors(created_at);
CREATE INDEX idx_visitors_status ON visitors(status);
CREATE INDEX idx_visitors_purpose ON visitors(purpose);
CREATE INDEX idx_visitors_department ON visitors(department);

-- Insert sample visitors for testing
INSERT INTO visitors (visitor_name, telephone, email, purpose, person_to_visit, department, id_number) VALUES
('John Smith', '+250788123456', 'john.smith@email.com', 'Meeting', 'Dr. Jane Doe', 'Academic Affairs', 'ID123456'),
('Mary Johnson', '+250788123457', 'mary.johnson@email.com', 'Interview', 'HR Manager', 'Administration', 'ID123457'),
('David Wilson', '+250788123458', 'david.wilson@email.com', 'Delivery', 'IT Department', 'IT Department', 'ID123458'); 