<?php
require_once 'config/database.php';
$conn = connectDB();

$search_query = isset($_GET['query']) ? sanitize($_GET['query']) : '';

if (empty($search_query)) {
    header('Location: index.php');
    exit();
}

// Search in movies table
$sql = "SELECT * FROM movies 
        WHERE name LIKE ? 
        OR genre LIKE ? 
        OR plot LIKE ?";

$search_term = "%{$search_query}%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $search_term, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - MovieInfo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php"><h1>MovieInfo</h1></a>
            </div>
            <div class="search-container">
                <form action="search.php" method="GET">
                    <input type="text" name="query" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search for movies..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </nav>
    </header>

    <main>
        <section class="search-results">
            <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="movie-grid">
                    <?php while($movie = $result->fetch_assoc()): ?>
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
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>No movies found matching your search.</p>
                    <a href="index.php" class="btn btn-primary">Return to Home</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>