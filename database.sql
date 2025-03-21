-- Create database
CREATE DATABASE IF NOT EXISTS movie_db;
USE movie_db;

-- Movies table
CREATE TABLE IF NOT EXISTS movies (
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    genre VARCHAR(100) NOT NULL,
    duration INT NOT NULL,
    rating DECIMAL(3,1),
    trailer_url VARCHAR(255),
    review TEXT,
    poster_url VARCHAR(255),
    release_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Actors table
CREATE TABLE IF NOT EXISTS actors (
    actor_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE
);

-- Crew table
CREATE TABLE IF NOT EXISTS crew (
    crew_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE
);

-- Songs table
CREATE TABLE IF NOT EXISTS songs (
    song_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT,
    name VARCHAR(255) NOT NULL,
    singer VARCHAR(255),
    music_director VARCHAR(255),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password) VALUES ('admin', '$2y$10$8K1p/bMmqskj0CWUoD7H2.4Wp9CWUoD7H2.4Wp9CWUoD7H2.');