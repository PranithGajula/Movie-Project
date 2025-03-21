<?php
session_start();
require_once 'config/database.php';
$conn = connectDB();

// Handle login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT admin_id, password FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            header('Location: dashboard.php');
            exit();
        } else {
            $login_error = 'Invalid username or password';
        }
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Get all movies
$query = "SELECT * FROM movies ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Information Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-login-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-login-btn:hover {
            color: #e50914;
        }

        .login-dropdown {
            position: relative;
            display: inline-block;
        }

        .login-form {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            min-width: 250px;
            margin-top: 0.5rem;
        }

        .login-form.show {
            display: block;
        }

        .login-form input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .login-form button {
            width: 100%;
            padding: 0.5rem;
            background-color: #e50914;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .login-form button:hover {
            background-color: #f40612;
        }

        .error-message {
            color: #e50914;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Adjust the existing search container */
        .search-container {
            flex: 0 0 auto;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>MovieInfo</h1>
            </div>
            <div class="nav-right">
                <div class="search-container">
                    <form action="search.php" method="GET">
                        <input type="text" name="query" placeholder="Search for movies..." required>
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <a href="dashboard.php" class="admin-login-btn">
                        <i class="fas fa-user-shield"></i> Admin Dashboard
                    </a>
                <?php else: ?>
                    <div class="login-dropdown">
                        <button class="admin-login-btn" onclick="toggleLoginForm()">
                            <i class="fas fa-user"></i> Admin Login
                        </button>
                        <div class="login-form" id="loginForm">
                            <?php if ($login_error): ?>
                                <div class="error-message"><?php echo $login_error; ?></div>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <input type="text" name="username" placeholder="Username" required>
                                <input type="password" name="password" placeholder="Password" required>
                                <button type="submit" name="admin_login">Login</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="featured-movies">
            <h2>Featured Movies</h2>
            <div class="movie-grid">
                <?php
                if ($result->num_rows > 0) {
                    while($movie = $result->fetch_assoc()) {
                        ?>
                        <div class="movie-card">
                            <a href="movie.php?id=<?php echo $movie['movie_id']; ?>">
                                <img src="<?php echo $movie['poster_url']; ?>" alt="<?php echo $movie['name']; ?>" class="movie-poster">
                                <div class="movie-info">
                                    <h3><?php echo $movie['name']; ?></h3>
                                    <div class="movie-meta">
                                        <span class="genre"><?php echo $movie['genre']; ?></span>
                                        <span class="rating"><i class="fas fa-star"></i> <?php echo $movie['rating']; ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p class='no-movies'>No movies found in the database.</p>";
                }
                ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>

    <script>
        function toggleLoginForm() {
            const loginForm = document.getElementById('loginForm');
            loginForm.classList.toggle('show');
        }

        // Close the login form when clicking outside
        document.addEventListener('click', function(event) {
            const loginForm = document.getElementById('loginForm');
            const loginBtn = event.target.closest('.admin-login-btn');
            const form = event.target.closest('.login-form');
            
            if (!loginBtn && !form && loginForm.classList.contains('show')) {
                loginForm.classList.remove('show');
            }
        });
    </script>
</body>
</html>