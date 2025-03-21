# MovieInfo - Movie Information Website

A dynamic and interactive movie information website built with PHP and MySQL that allows users to explore movie details, cast information, and watch trailers. The project includes both user-facing features and an admin dashboard for content management.

## 🎬 Features

### For Users
- **Movie Catalog**: Browse through a collection of movies with detailed information
- **Search Functionality**: Search for movies by title, genre, or other criteria
- **Detailed Movie Pages** including:
  - Basic movie information (title, genre, duration, rating)
  - Release date and plot summary
  - Cast and crew details with photos
  - Movie songs and soundtrack information
  - YouTube trailer integration
  - OTT platform availability and direct links
- **Responsive Design**: Works seamlessly on desktop and mobile devices

### For Administrators
- **Secure Admin Dashboard**
- **Movie Management**:
  - Add new movies
  - Edit existing movie details
  - Upload movie posters
  - Manage cast and crew information
- **Content Management**:
  - Add/Edit movie details
  - Manage cast and crew information
  - Update OTT platform availability
  - Add/Edit movie songs

## 🚀 Technologies Used

- **Frontend**:
  - HTML5
  - CSS3
  - JavaScript
  - Font Awesome for icons
  - Responsive design principles
- **Backend**:
  - PHP
  - MySQL
- **Additional Features**:
  - YouTube API integration
  - Image upload and management
  - Security features (SQL injection prevention, XSS protection)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended) or similar PHP development environment

## 🛠️ Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/PranithGajula/Movie-Project
   ```

2. **Database Setup**
   - Create a new MySQL database named `movie_db`
   - Import the `database.sql` file from the project root

3. **Configuration**
   - Navigate to `config/database.php`
   - Update database credentials if necessary

4. **Server Setup**
   - Place the project in your web server's root directory
   - For XAMPP: `C:\xampp\htdocs\xampp\movie-project`

5. **Access the Website**
   - Main Website: `http://localhost/xampp/movie-project/index.php`
   - Admin Login: `http://localhost/xampp/movie-project/admin.php`

## 👥 Default Admin Credentials

- **Username**: pranithgajula
- **Password**: pranithgajula123

## 🔒 Security Features

- SQL Injection Prevention
- XSS Protection
- Secure File Upload
- Password Hashing
- Content Security Policy (CSP)
- Input Sanitization

## 📱 Responsive Design

The website is fully responsive and provides an optimal viewing experience across a wide range of devices:
- Desktop computers
- Tablets
- Mobile phones

## 🎨 UI Features

- Modern and clean interface
- Interactive hover effects
- Dynamic content loading
- Responsive image handling
- User-friendly navigation

## 🔄 Future Updates

- User registration and reviews
- Rating system
- Social media integration
- Advanced search filters
- Movie recommendations



## 👨‍💻 Author

Gajula Pranith
