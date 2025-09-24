-- Database for Library Management System

CREATE DATABASE IF NOT EXISTS library_management;
USE library_management;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Memberships table
CREATE TABLE IF NOT EXISTS memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    membership_type ENUM('6 months', '1 year', '2 years') NOT NULL DEFAULT '6 months',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(20) NOT NULL,
    publication_year INT,
    category VARCHAR(50) NOT NULL,
    item_type ENUM('book', 'movie') NOT NULL DEFAULT 'book',
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date DATE NOT NULL,
    return_date DATE NOT NULL,
    actual_return_date DATE,
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    fine_paid BOOLEAN DEFAULT FALSE,
    remarks TEXT,
    status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id)
);

-- Insert default admin user
INSERT INTO users (username, password, full_name, email, user_type) 
VALUES ('admin', '$2y$10$8WxmVFNDxYZNX.iLU/SOIeS/v/Yn1bxLO.yJgXHLQnTGHNQsLIp3a', 'Admin User', 'admin@library.com', 'admin');

-- Insert default regular user
INSERT INTO users (username, password, full_name, email, user_type) 
VALUES ('user', '$2y$10$8WxmVFNDxYZNX.iLU/SOIeS/v/Yn1bxLO.yJgXHLQnTGHNQsLIp3a', 'Regular User', 'user@library.com', 'user');

-- Insert sample books
INSERT INTO books (title, author, isbn, publication_year, category, item_type, total_copies, available_copies) VALUES
('To Kill a Mockingbird', 'Harper Lee', '9780061120084', 1960, 'Fiction', 'book', 5, 5),
('1984', 'George Orwell', '9780451524935', 1949, 'Fiction', 'book', 3, 3),
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 1925, 'Fiction', 'book', 4, 4),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 1813, 'Romance', 'book', 2, 2),
('The Hobbit', 'J.R.R. Tolkien', '9780547928227', 1937, 'Fantasy', 'book', 3, 3),
('Inception', 'Christopher Nolan', 'MOV00001', 2010, 'Sci-Fi', 'movie', 2, 2),
('The Shawshank Redemption', 'Frank Darabont', 'MOV00002', 1994, 'Drama', 'movie', 1, 1);