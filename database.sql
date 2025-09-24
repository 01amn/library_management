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

-- Additional dummy books/movies for dashboards
INSERT INTO books (title, author, isbn, publication_year, category, item_type, total_copies, available_copies) VALUES
('The Silent Patient', 'Alex Michaelides', '9781250301697', 2019, 'Thriller', 'book', 6, 6),
('Becoming', 'Michelle Obama', '9781524763138', 2018, 'Biography', 'book', 5, 5),
('Sapiens', 'Yuval Noah Harari', '9780062316110', 2011, 'History', 'book', 4, 4),
('Educated', 'Tara Westover', '9780399590504', 2018, 'Biography', 'book', 3, 3),
('The Martian', 'Andy Weir', '9780553418026', 2014, 'Science Fiction', 'book', 5, 5),
('Deep Work', 'Cal Newport', '9781455586691', 2016, 'Self-Help', 'book', 4, 4),
('The Pragmatic Programmer', 'Andrew Hunt', '9780201616224', 1999, 'Science', 'book', 5, 5),
('Clean Code', 'Robert C. Martin', '9780132350884', 2008, 'Science', 'book', 5, 5),
('Thinking, Fast and Slow', 'Daniel Kahneman', '9780374533557', 2011, 'Science', 'book', 4, 4),
('Dune', 'Frank Herbert', '9780441013593', 1965, 'Science Fiction', 'book', 6, 6),
('The Name of the Wind', 'Patrick Rothfuss', '9780756404741', 2007, 'Fantasy', 'book', 4, 4),
('Gone Girl', 'Gillian Flynn', '9780307588371', 2012, 'Mystery', 'book', 3, 3),
('The Da Vinci Code', 'Dan Brown', '9780307474278', 2003, 'Thriller', 'book', 6, 6),
('The Alchemist', 'Paulo Coelho', '9780061122415', 1988, 'Fiction', 'book', 7, 7),
('Atomic Habits', 'James Clear', '9780735211292', 2018, 'Self-Help', 'book', 8, 8),
('Interstellar', 'Christopher Nolan', 'MOV00003', 2014, 'Sci-Fi', 'movie', 2, 2),
('The Godfather', 'Francis Ford Coppola', 'MOV00004', 1972, 'Drama', 'movie', 2, 2),
('Parasite', 'Bong Joon-ho', 'MOV00005', 2019, 'Drama', 'movie', 1, 1),
('Spirited Away', 'Hayao Miyazaki', 'MOV00006', 2001, 'Fantasy', 'movie', 2, 2),
('The Dark Knight', 'Christopher Nolan', 'MOV00007', 2008, 'Action', 'movie', 3, 3);