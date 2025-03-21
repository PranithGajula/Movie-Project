<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

$conn = connectDB();

// Get all movies
$query = "SELECT * FROM movies ORDER BY created_at DESC";
$result = $conn->query($query);

// Handle movie deletion
if (isset($_POST['delete_movie']) && isset($_POST['movie_id'])) {
    $movie_id = sanitize($_POST['movie_id']);
    $delete_query = "DELETE FROM movies WHERE movie_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MovieInfo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php"><h1>MovieInfo</h1></a>
            </div>
            <div class="admin-nav">
                <a href="add_movie.php" class="btn btn-primary">Add New Movie</a>
                <a href="change_admin_credentials.php" class="btn btn-primary">Change Admin Credentials</a>
                <form action="logout.php" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </nav>
    </header>

    <main>
        <div class="admin-container">
            <h2>Movie Management</h2>
            
            <div class="movie-list">
                <table>
                    <thead>
                        <tr>
                            <th>Movie Name</th>
                            <th>Genre</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($movie = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $movie['name']; ?></td>
                                <td><?php echo $movie['genre']; ?></td>
                                <td><?php echo $movie['rating']; ?></td>
                                <td>
                                    <a href="edit_movie.php?id=<?php echo $movie['movie_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="dashboard.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                                        <button type="submit" name="delete_movie" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this movie?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>

    <style>
        .movie-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .movie-list th,
        .movie-list td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .movie-list th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn i {
            font-size: 0.9rem;
        }
    </style>
</body>
</html>