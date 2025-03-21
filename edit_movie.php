<?php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Check if movie ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$movie_id = sanitize($_GET['id']);
$conn = connectDB();
$success_message = '';
$error_message = '';

// Get movie details
$query = "SELECT * FROM movies WHERE movie_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();

if (!$movie) {
    header('Location: dashboard.php');
    exit();
}

// Get actors
$actors_query = "SELECT * FROM actors WHERE movie_id = ?";
$stmt = $conn->prepare($actors_query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$actors = $stmt->get_result();

// Get crew
$crew_query = "SELECT * FROM crew WHERE movie_id = ?";
$stmt = $conn->prepare($crew_query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$crew = $stmt->get_result();

// Get songs
$songs_query = "SELECT * FROM songs WHERE movie_id = ?";
$stmt = $conn->prepare($songs_query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$songs = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get basic movie details
    $name = sanitize($_POST['name']);
    $genre = sanitize($_POST['genre']);
    $duration = sanitize($_POST['duration']);
    $rating = sanitize($_POST['rating']);
    $release_date = sanitize($_POST['release_date']);
    $trailer_url = sanitize($_POST['trailer_url']);
    
    // Handle OTT platform
    $ott_platform = sanitize($_POST['ott_platform']);
    if ($ott_platform === 'Other' && isset($_POST['custom_ott_platform'])) {
        $ott_platform = sanitize($_POST['custom_ott_platform']);
    }
    
    $ott_link = sanitize($_POST['ott_link']);
    $plot = str_replace(["\r\n", "\r"], "\n", $_POST['plot']); // Normalize line endings

    // Handle poster upload
    $poster_url = $movie['poster_url']; // Keep existing poster by default
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['poster']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/posters/' . $new_filename;
            
            if (move_uploaded_file($_FILES['poster']['tmp_name'], $upload_path)) {
                $poster_url = $upload_path;
                // Delete old poster if it exists and is not the default
                if ($movie['poster_url'] && file_exists($movie['poster_url'])) {
                    unlink($movie['poster_url']);
                }
            }
        }
    }

    // Update movie details
    $update_query = "UPDATE movies SET name = ?, genre = ?, duration = ?, rating = ?, 
                    release_date = ?, trailer_url = ?, plot = ?, poster_url = ?, 
                    ott_platform = ?, ott_link = ? 
                    WHERE movie_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssiissssssi", $name, $genre, $duration, $rating, 
                            $release_date, $trailer_url, $plot, $poster_url, 
                            $ott_platform, $ott_link, $movie_id);

    if ($update_stmt->execute()) {
        // Handle actors update
        if (isset($_POST['actor_names']) && is_array($_POST['actor_names'])) {
            // Get existing actors to track their images
            $existing_actors = [];
            $existing_query = "SELECT image_url FROM actors WHERE movie_id = ?";
            $stmt = $conn->prepare($existing_query);
            $stmt->bind_param("i", $movie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                if($row['image_url'] && file_exists($row['image_url'])) {
                    $existing_actors[] = $row['image_url'];
                }
            }

            // Delete existing actors from database
            $conn->query("DELETE FROM actors WHERE movie_id = $movie_id");
            
            $actor_stmt = $conn->prepare("INSERT INTO actors (movie_id, name, role, image_url) VALUES (?, ?, ?, ?)");
            foreach ($_POST['actor_names'] as $key => $actor_name) {
                if (!empty($actor_name)) {
                    $actor_role = $_POST['actor_roles'][$key];
                    $actor_image_url = isset($existing_actors[$key]) ? $existing_actors[$key] : ''; // Keep existing image URL by default

                    // Handle new image upload
                    if (isset($_FILES['actor_images']['name'][$key]) && $_FILES['actor_images']['error'][$key] === 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['actor_images']['name'][$key];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed)) {
                            // Delete old image if it exists
                            if (!empty($actor_image_url) && file_exists($actor_image_url)) {
                                unlink($actor_image_url);
                            }

                            $new_filename = uniqid('actor_') . '.' . $ext;
                            $upload_path = 'uploads/actors/' . $new_filename;
                            
                            if (move_uploaded_file($_FILES['actor_images']['tmp_name'][$key], $upload_path)) {
                                $actor_image_url = $upload_path;
                            }
                        }
                    }

                    $actor_stmt->bind_param("isss", $movie_id, $actor_name, $actor_role, $actor_image_url);
                    $actor_stmt->execute();
                }
            }

            // Clean up unused actor images
            foreach($existing_actors as $old_image) {
                if(!empty($old_image) && file_exists($old_image)) {
                    // Check if image is still in use
                    $check_query = "SELECT COUNT(*) as count FROM actors WHERE image_url = ?";
                    $stmt = $conn->prepare($check_query);
                    $stmt->bind_param("s", $old_image);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if($result['count'] == 0) {
                        unlink($old_image);
                    }
                }
            }
        }

        // Handle crew update
        if (isset($_POST['crew_names']) && is_array($_POST['crew_names'])) {
            // Get existing crew to track their images
            $existing_crew = [];
            $existing_query = "SELECT image_url FROM crew WHERE movie_id = ?";
            $stmt = $conn->prepare($existing_query);
            $stmt->bind_param("i", $movie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                if($row['image_url'] && file_exists($row['image_url'])) {
                    $existing_crew[] = $row['image_url'];
                }
            }

            // Delete existing crew from database
            $conn->query("DELETE FROM crew WHERE movie_id = $movie_id");
            
            $crew_stmt = $conn->prepare("INSERT INTO crew (movie_id, name, role, image_url) VALUES (?, ?, ?, ?)");
            foreach ($_POST['crew_names'] as $key => $crew_name) {
                if (!empty($crew_name)) {
                    $crew_role = $_POST['crew_roles'][$key];
                    $crew_image_url = isset($existing_crew[$key]) ? $existing_crew[$key] : ''; // Keep existing image URL by default

                    // Handle new image upload
                    if (isset($_FILES['crew_images']['name'][$key]) && $_FILES['crew_images']['error'][$key] === 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['crew_images']['name'][$key];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed)) {
                            // Delete old image if it exists
                            if (!empty($crew_image_url) && file_exists($crew_image_url)) {
                                unlink($crew_image_url);
                            }

                            $new_filename = uniqid('crew_') . '.' . $ext;
                            $upload_path = 'uploads/crew/' . $new_filename;
                            
                            if (move_uploaded_file($_FILES['crew_images']['tmp_name'][$key], $upload_path)) {
                                $crew_image_url = $upload_path;
                            }
                        }
                    }

                    $crew_stmt->bind_param("isss", $movie_id, $crew_name, $crew_role, $crew_image_url);
                    $crew_stmt->execute();
                }
            }

            // Clean up unused crew images
            foreach($existing_crew as $old_image) {
                if(!empty($old_image) && file_exists($old_image)) {
                    // Check if image is still in use
                    $check_query = "SELECT COUNT(*) as count FROM crew WHERE image_url = ?";
                    $stmt = $conn->prepare($check_query);
                    $stmt->bind_param("s", $old_image);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    if($result['count'] == 0) {
                        unlink($old_image);
                    }
                }
            }
        }

        // Handle songs update
        $conn->query("DELETE FROM songs WHERE movie_id = $movie_id");
        if (isset($_POST['song_names']) && is_array($_POST['song_names'])) {
            $song_stmt = $conn->prepare("INSERT INTO songs (movie_id, name, singer, music_director) VALUES (?, ?, ?, ?)");
            foreach ($_POST['song_names'] as $key => $song_name) {
                $singer = $_POST['singers'][$key];
                $music_director = $_POST['music_directors'][$key];
                $song_stmt->bind_param("isss", $movie_id, $song_name, $singer, $music_director);
                $song_stmt->execute();
            }
        }

        $success_message = "Movie updated successfully!";
        
        // Refresh movie data
        $refresh_query = "SELECT * FROM movies WHERE movie_id = ?";
        $refresh_stmt = $conn->prepare($refresh_query);
        $refresh_stmt->bind_param("i", $movie_id);
        $refresh_stmt->execute();
        $movie = $refresh_stmt->get_result()->fetch_assoc();
        
        // Refresh actors data
        $actors_stmt = $conn->prepare($actors_query);
        $actors_stmt->bind_param("i", $movie_id);
        $actors_stmt->execute();
        $actors = $actors_stmt->get_result();
        
        // Refresh crew data
        $crew_stmt = $conn->prepare($crew_query);
        $crew_stmt->bind_param("i", $movie_id);
        $crew_stmt->execute();
        $crew = $crew_stmt->get_result();
        
        // Refresh songs data
        $songs_stmt = $conn->prepare($songs_query);
        $songs_stmt->bind_param("i", $movie_id);
        $songs_stmt->execute();
        $songs = $songs_stmt->get_result();
    } else {
        $error_message = "Error updating movie. Please try again.";
    }
}

// Get fresh movie data even if not updating
if (!isset($movie) || !$movie) {
    $movie_query = "SELECT * FROM movies WHERE movie_id = ?";
    $stmt = $conn->prepare($movie_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();

    if (!$movie) {
        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie - MovieInfo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-movie-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="url"],
        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .current-poster {
            max-width: 200px;
            margin: 1rem 0;
        }

        .section-title {
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e50914;
        }

        .dynamic-fields {
            border: 1px solid #ddd;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .dynamic-fields .form-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .add-more-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background-color: #e50914;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 2rem;
        }

        .submit-btn:hover {
            background-color: #f40612;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .ott-platform-container {
            display: flex;
            flex-direction: column;
        }
        
        .ott-platform-container select,
        .ott-platform-container input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
    </style>
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
        <div class="edit-movie-form">
            <h2>Edit Movie: <?php echo htmlspecialchars($movie['name']); ?></h2>
            
            <?php if ($success_message): ?>
                <div class="message success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Movie Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($movie['name']) ? htmlspecialchars($movie['name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre" value="<?php echo isset($movie['genre']) ? htmlspecialchars($movie['genre']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" value="<?php echo isset($movie['duration']) ? htmlspecialchars($movie['duration']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="rating">Rating</label>
                    <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="<?php echo isset($movie['rating']) ? htmlspecialchars($movie['rating']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="date" id="release_date" name="release_date" value="<?php echo isset($movie['release_date']) ? htmlspecialchars($movie['release_date']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="trailer_url">Trailer URL (YouTube)</label>
                    <input type="url" id="trailer_url" name="trailer_url" value="<?php echo isset($movie['trailer_url']) ? htmlspecialchars($movie['trailer_url']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="ott_platform">OTT Platform</label>
                    <div class="ott-platform-container">
                        <select id="ott_platform" name="ott_platform" onchange="handleOttPlatformChange(this.value)">
                            <option value="">Select Platform</option>
                            <option value="Netflix" <?php echo (isset($movie['ott_platform']) && $movie['ott_platform'] == 'Netflix') ? 'selected' : ''; ?>>Netflix</option>
                            <option value="Amazon Prime" <?php echo (isset($movie['ott_platform']) && $movie['ott_platform'] == 'Amazon Prime') ? 'selected' : ''; ?>>Amazon Prime</option>
                            <option value="Disney+" <?php echo (isset($movie['ott_platform']) && $movie['ott_platform'] == 'Disney+') ? 'selected' : ''; ?>>Disney+</option>
                            <option value="Hotstar" <?php echo (isset($movie['ott_platform']) && $movie['ott_platform'] == 'Hotstar') ? 'selected' : ''; ?>>Hotstar</option>
                            <option value="Other" <?php echo (isset($movie['ott_platform']) && $movie['ott_platform'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <div id="custom_ott_container" style="display: none; margin-top: 10px;">
                            <input type="text" id="custom_ott_platform" name="custom_ott_platform" placeholder="Enter custom OTT platform name">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ott_link">OTT Platform Link</label>
                    <input type="url" id="ott_link" name="ott_link" value="<?php echo isset($movie['ott_link']) ? htmlspecialchars($movie['ott_link']) : ''; ?>" placeholder="https://...">
                    <small>Leave empty if not available on any OTT platform</small>
                </div>

                <div class="form-group">
                    <label for="plot">Movie Review</label>
                    <textarea id="plot" name="plot" required><?php echo isset($movie['plot']) ? str_replace(['\\r\\n', '\\n'], "\n", htmlspecialchars($movie['plot'])) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="poster">Movie Poster</label>
                    <?php if (isset($movie['poster_url']) && $movie['poster_url'] && file_exists($movie['poster_url'])): ?>
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="Current Poster" class="current-poster">
                    <?php endif; ?>
                    <input type="file" id="poster" name="poster" accept="image/*">
                    <small>Leave empty to keep current poster</small>
                </div>

                <h3 class="section-title">Cast</h3>
                <div id="actors-container">
                    <?php while ($actor = $actors->fetch_assoc()): ?>
                        <div class="dynamic-fields">
                            <div class="form-group">
                                <div>
                                    <label>Actor Name</label>
                                    <input type="text" name="actor_names[]" value="<?php echo htmlspecialchars($actor['name']); ?>" required>
                                </div>
                                <div>
                                    <label>Role</label>
                                    <input type="text" name="actor_roles[]" value="<?php echo htmlspecialchars($actor['role']); ?>" required>
                                </div>
                                <div>
                                    <label>Actor Image</label>
                                    <?php if ($actor['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($actor['image_url']); ?>" alt="Current Actor Image" style="max-width: 100px; margin-bottom: 10px;">
                                    <?php endif; ?>
                                    <input type="file" name="actor_images[]" accept="image/*">
                                    <small>Leave empty to keep current image</small>
                                </div>
                            </div>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove Actor</button>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="add-more-btn" onclick="addActor()">Add Actor</button>

                <h3 class="section-title">Crew</h3>
                <div id="crew-container">
                    <?php while ($member = $crew->fetch_assoc()): ?>
                        <div class="dynamic-fields">
                            <div class="form-group">
                                <div>
                                    <label>Crew Member Name</label>
                                    <input type="text" name="crew_names[]" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                                </div>
                                <div>
                                    <label>Role</label>
                                    <input type="text" name="crew_roles[]" value="<?php echo htmlspecialchars($member['role']); ?>" required>
                                </div>
                                <div>
                                    <label>Crew Image</label>
                                    <?php if ($member['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="Current Crew Image" style="max-width: 100px; margin-bottom: 10px;">
                                    <?php endif; ?>
                                    <input type="file" name="crew_images[]" accept="image/*">
                                    <small>Leave empty to keep current image</small>
                                </div>
                            </div>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove Crew Member</button>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="add-more-btn" onclick="addCrew()">Add Crew Member</button>

                <h3 class="section-title">Songs</h3>
                <div id="songs-container">
                    <?php while ($song = $songs->fetch_assoc()): ?>
                        <div class="dynamic-fields">
                            <div class="form-group">
                                <div>
                                    <label>Song Name</label>
                                    <input type="text" name="song_names[]" value="<?php echo htmlspecialchars($song['name']); ?>" required>
                                </div>
                                <div>
                                    <label>Singer</label>
                                    <input type="text" name="singers[]" value="<?php echo htmlspecialchars($song['singer']); ?>" required>
                                </div>
                                <div>
                                    <label>Music Director</label>
                                    <input type="text" name="music_directors[]" value="<?php echo htmlspecialchars($song['music_director']); ?>" required>
                                </div>
                            </div>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove Song</button>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="add-more-btn" onclick="addSong()">Add Song</button>

                <button type="submit" class="submit-btn">Update Movie</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>

    <script>
        function createField(type) {
            const div = document.createElement('div');
            div.className = 'dynamic-fields';
            
            let fields = '';
            if (type === 'actor') {
                fields = `
                    <div class="form-group">
                        <div>
                            <label>Actor Name</label>
                            <input type="text" name="actor_names[]" required>
                        </div>
                        <div>
                            <label>Role</label>
                            <input type="text" name="actor_roles[]" required>
                        </div>
                        <div>
                            <label>Actor Image</label>
                            <input type="file" name="actor_images[]" accept="image/*">
                        </div>
                    </div>
                `;
            } else if (type === 'crew') {
                fields = `
                    <div class="form-group">
                        <div>
                            <label>Crew Member Name</label>
                            <input type="text" name="crew_names[]" required>
                        </div>
                        <div>
                            <label>Role</label>
                            <input type="text" name="crew_roles[]" required>
                        </div>
                        <div>
                            <label>Crew Image</label>
                            <input type="file" name="crew_images[]" accept="image/*">
                        </div>
                    </div>
                `;
            } else if (type === 'song') {
                fields = `
                    <div class="form-group">
                        <div>
                            <label>Song Name</label>
                            <input type="text" name="song_names[]" required>
                        </div>
                        <div>
                            <label>Singer</label>
                            <input type="text" name="singers[]" required>
                        </div>
                        <div>
                            <label>Music Director</label>
                            <input type="text" name="music_directors[]" required>
                        </div>
                    </div>
                `;
            }
            
            div.innerHTML = fields + `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove ${type}</button>
            `;
            
            return div;
        }

        function addActor() {
            document.getElementById('actors-container').appendChild(createField('actor'));
        }

        function addCrew() {
            document.getElementById('crew-container').appendChild(createField('crew'));
        }

        function addSong() {
            document.getElementById('songs-container').appendChild(createField('song'));
        }

        function handleOttPlatformChange(value) {
            const customContainer = document.getElementById('custom_ott_container');
            const customInput = document.getElementById('custom_ott_platform');
            
            if (value === 'Other') {
                customContainer.style.display = 'block';
                customInput.required = true;
            } else {
                customContainer.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }

        // Check initial value on page load
        document.addEventListener('DOMContentLoaded', function() {
            const ottSelect = document.getElementById('ott_platform');
            handleOttPlatformChange(ottSelect.value);
        });
    </script>
</body>
</html> 