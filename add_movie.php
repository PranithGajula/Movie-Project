<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

$conn = connectDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle movie data
    $name = sanitize($_POST['name']);
    $genre = sanitize($_POST['genre']);
    $duration = sanitize($_POST['duration']);
    $rating = sanitize($_POST['rating']);
    $trailer_url = sanitize($_POST['trailer_url']);
    $plot = sanitize($_POST['plot']);
    $release_date = sanitize($_POST['release_date']);

    // Handle poster upload
    $poster_url = '';
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === 0) {
        $target_dir = "uploads/posters/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['poster']['tmp_name'], $target_file)) {
            $poster_url = $target_file;
        }
    }

    // Insert movie
    $query = "INSERT INTO movies (name, genre, duration, rating, trailer_url, plot, poster_url, release_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiissss", $name, $genre, $duration, $rating, $trailer_url, $plot, $poster_url, $release_date);
    
    if ($stmt->execute()) {
        $movie_id = $conn->insert_id;

        // Handle actors
        if (isset($_POST['actor_name']) && is_array($_POST['actor_name'])) {
            $actor_query = "INSERT INTO actors (movie_id, name, role, image_url) VALUES (?, ?, ?, ?)";
            $actor_stmt = $conn->prepare($actor_query);

            foreach ($_POST['actor_name'] as $key => $actor_name) {
                $actor_role = $_POST['actor_role'][$key];
                $actor_image = '';
                
                if (isset($_FILES['actor_image']['name'][$key]) && $_FILES['actor_image']['error'][$key] === 0) {
                    $target_dir = "uploads/actors/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $file_extension = strtolower(pathinfo($_FILES['actor_image']['name'][$key], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['actor_image']['tmp_name'][$key], $target_file)) {
                        $actor_image = $target_file;
                    }
                }

                $actor_stmt->bind_param("isss", $movie_id, $actor_name, $actor_role, $actor_image);
                $actor_stmt->execute();
            }
        }

        // Handle crew members
        if (isset($_POST['crew_name']) && is_array($_POST['crew_name'])) {
            $crew_query = "INSERT INTO crew (movie_id, name, role, image_url) VALUES (?, ?, ?, ?)";
            $crew_stmt = $conn->prepare($crew_query);

            foreach ($_POST['crew_name'] as $key => $crew_name) {
                $crew_role = $_POST['crew_role'][$key];
                $crew_image = '';
                
                if (isset($_FILES['crew_image']['name'][$key]) && $_FILES['crew_image']['error'][$key] === 0) {
                    $target_dir = "uploads/crew/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $file_extension = strtolower(pathinfo($_FILES['crew_image']['name'][$key], PATHINFO_EXTENSION));
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['crew_image']['tmp_name'][$key], $target_file)) {
                        $crew_image = $target_file;
                    }
                }

                $crew_stmt->bind_param("isss", $movie_id, $crew_name, $crew_role, $crew_image);
                $crew_stmt->execute();
            }
        }

        // Handle songs
        if (isset($_POST['song_name']) && is_array($_POST['song_name'])) {
            $song_query = "INSERT INTO songs (movie_id, name, singer, music_director) VALUES (?, ?, ?, ?)";
            $song_stmt = $conn->prepare($song_query);

            foreach ($_POST['song_name'] as $key => $song_name) {
                $singer = $_POST['singer'][$key];
                $music_director = $_POST['music_director'][$key];

                $song_stmt->bind_param("isss", $movie_id, $song_name, $singer, $music_director);
                $song_stmt->execute();
            }
        }

        $message = 'Movie added successfully!';
        header('Location: dashboard.php');
        exit();
    } else {
        $message = 'Error adding movie: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie - MovieInfo</title>
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
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="admin-container">
            <h2>Add New Movie</h2>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <form class="admin-form" method="POST" action="add_movie.php" enctype="multipart/form-data">
                <h3>Movie Details</h3>
                <div class="form-group">
                    <label for="name">Movie Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre" required>
                </div>
                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" required>
                </div>
                <div class="form-group">
                    <label for="rating">IMDB Rating</label>
                    <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" required>
                </div>
                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="date" id="release_date" name="release_date" required>
                </div>
                <div class="form-group">
                    <label for="trailer_url">Trailer URL (YouTube)</label>
                    <input type="url" id="trailer_url" name="trailer_url" required>
                </div>
                <div class="form-group">
                    <label for="poster">Movie Poster</label>
                    <input type="file" id="poster" name="poster" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="plot">Movie Plot</label>
                    <textarea id="plot" name="plot" rows="4" required></textarea>
                </div>

                <h3>Cast</h3>
                <div id="actors-container">
                    <div class="actor-entry">
                        <div class="form-group">
                            <label>Actor Name</label>
                            <input type="text" name="actor_name[]" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" name="actor_role[]" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="actor_image[]" accept="image/*">
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addActor()">Add Another Actor</button>

                <h3>Crew</h3>
                <div id="crew-container">
                    <div class="crew-entry">
                        <div class="form-group">
                            <label>Crew Member Name</label>
                            <input type="text" name="crew_name[]" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" name="crew_role[]" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="crew_image[]" accept="image/*">
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addCrew()">Add Another Crew Member</button>

                <h3>Songs</h3>
                <div id="songs-container">
                    <div class="song-entry">
                        <div class="form-group">
                            <label>Song Name</label>
                            <input type="text" name="song_name[]" required>
                        </div>
                        <div class="form-group">
                            <label>Singer</label>
                            <input type="text" name="singer[]" required>
                        </div>
                        <div class="form-group">
                            <label>Music Director</label>
                            <input type="text" name="music_director[]" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addSong()">Add Another Song</button>

                <button type="submit" class="btn btn-primary">Add Movie</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>

    <script>
        function addActor() {
            const container = document.getElementById('actors-container');
            const entry = document.createElement('div');
            entry.className = 'actor-entry';
            entry.innerHTML = `
                <div class="form-group">
                    <label>Actor Name</label>
                    <input type="text" name="actor_name[]" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" name="actor_role[]" required>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="actor_image[]" accept="image/*">
                </div>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(entry);
        }

        function addCrew() {
            const container = document.getElementById('crew-container');
            const entry = document.createElement('div');
            entry.className = 'crew-entry';
            entry.innerHTML = `
                <div class="form-group">
                    <label>Crew Member Name</label>
                    <input type="text" name="crew_name[]" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" name="crew_role[]" required>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="crew_image[]" accept="image/*">
                </div>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(entry);
        }

        function addSong() {
            const container = document.getElementById('songs-container');
            const entry = document.createElement('div');
            entry.className = 'song-entry';
            entry.innerHTML = `
                <div class="form-group">
                    <label>Song Name</label>
                    <input type="text" name="song_name[]" required>
                </div>
                <div class="form-group">
                    <label>Singer</label>
                    <input type="text" name="singer[]" required>
                </div>
                <div class="form-group">
                    <label>Music Director</label>
                    <input type="text" name="music_director[]" required>
                </div>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(entry);
        }
    </script>

    <style>
        .actor-entry, .crew-entry, .song-entry {
            border: 1px solid #ddd;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-bottom: 2rem;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        h3 {
            margin: 2rem 0 1rem;
        }
    </style>
</body>
</html>