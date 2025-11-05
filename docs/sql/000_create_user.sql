-- for login system
-- Run in your MySQL (XAMPP) targeting the `user_db` database.


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    mobile VARCHAR(20) UNIQUE
);