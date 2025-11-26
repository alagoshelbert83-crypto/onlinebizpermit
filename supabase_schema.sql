-- OnlineBizPermit Database Schema for Supabase (PostgreSQL)
-- Run this in Supabase SQL Editor to create all necessary tables

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'staff', 'admin')),
    phone VARCHAR(20),
    is_approved SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Applications table
CREATE TABLE IF NOT EXISTS applications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    business_name VARCHAR(255) NOT NULL,
    business_address TEXT,
    type_of_business VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'processing')),
    form_details TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    application_type VARCHAR(20) DEFAULT 'New' CHECK (application_type IN ('New', 'Renewal')),
    mode_of_payment VARCHAR(20) DEFAULT 'Annually' CHECK (mode_of_payment IN ('Annually', 'Semi-Annually', 'Quarterly')),
    dti_reg_no VARCHAR(50),
    tin_no VARCHAR(20),
    renewal_date DATE,
    renewal_reminder_sent SMALLINT DEFAULT 0,
    renewal_status VARCHAR(20) DEFAULT 'active' CHECK (renewal_status IN ('active', 'expiring_soon', 'expired', 'renewed')),
    original_application_id INTEGER,
    renewal_count INTEGER DEFAULT 0
);

-- Documents table
CREATE TABLE IF NOT EXISTS documents (
    id SERIAL PRIMARY KEY,
    application_id INTEGER REFERENCES applications(id),
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    message TEXT NOT NULL,
    link VARCHAR(500),
    is_read SMALLINT DEFAULT 0,
    type VARCHAR(50),
    recipient_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Live chats table
CREATE TABLE IF NOT EXISTS live_chats (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Active', 'Closed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id SERIAL PRIMARY KEY,
    chat_id INTEGER REFERENCES live_chats(id),
    sender_id INTEGER,
    sender_role VARCHAR(20) CHECK (sender_role IN ('user', 'staff', 'admin', 'bot')),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff form data table
CREATE TABLE IF NOT EXISTS staff_form_data (
    id SERIAL PRIMARY KEY,
    application_id INTEGER REFERENCES applications(id),
    form_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Renewal notifications table
CREATE TABLE IF NOT EXISTS renewal_notifications (
    id SERIAL PRIMARY KEY,
    application_id INTEGER REFERENCES applications(id),
    notification_type VARCHAR(50) NOT NULL,
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read SMALLINT DEFAULT 0
);

-- User sessions table (for database-backed sessions)
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    session_data TEXT,
    session_expires TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_applications_user_id ON applications(user_id);
CREATE INDEX IF NOT EXISTS idx_applications_status ON applications(status);
CREATE INDEX IF NOT EXISTS idx_documents_application_id ON documents(application_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_live_chats_user_id ON live_chats(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_chat_id ON chat_messages(chat_id);

-- Insert default admin user (optional - change password after creation)
-- Password: admin123 (hashed)
INSERT INTO users (name, email, password, role, is_approved)
VALUES ('Administrator', 'admin@onlinebizpermit.com', '$2y$10$8K1p/5w6QyTmF8Vz5Q9XueJcQw9dVdQ9YpJcQw9dVdQ9YpJcQw9d', 'admin', 1)
ON CONFLICT (email) DO NOTHING;
