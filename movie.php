<?php
require_once 'config/database.php';
$conn = connectDB();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$movie_id = sanitize($_GET['id']);

// Get movie details
$movie_query = "SELECT * FROM movies WHERE movie_id = ?";
$stmt = $conn->prepare($movie_query);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    header('Location: index.php');
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com;">
    <title><?php echo htmlspecialchars($movie['name']); ?> - MovieInfo</title>
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
                    <input type="text" name="query" placeholder="Search for movies..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </nav>
    </header>

    <main>
        <div class="movie-details">
            <div class="movie-header">
                <div class="movie-poster-container">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['name']); ?>" class="movie-poster-large">
                </div>
                <div class="movie-info-detailed">
                    <h1><?php echo htmlspecialchars($movie['name']); ?></h1>
                    <div class="movie-meta">
                        <div class="meta-item">
                            <i class="fas fa-film"></i>
                            <span class="meta-label">Genre:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($movie['genre']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span class="meta-label">Duration:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($movie['duration']); ?> minutes</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-star"></i>
                            <span class="meta-label">Rating:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($movie['rating']); ?>/10</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span class="meta-label">Release Date:</span>
                            <span class="meta-value"><?php echo date('F j, Y', strtotime($movie['release_date'])); ?></span>
                        </div>
                        <?php if (!empty($movie['ott_platform'])): ?>
                            <div class="meta-item ott-info">
                                <i class="fas fa-tv"></i>
                                <span class="meta-label">Watch on:</span>
                                <?php if (!empty($movie['ott_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($movie['ott_link']); ?>" target="_blank" class="ott-link">
                                        <?php echo htmlspecialchars($movie['ott_platform']); ?>
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="meta-value"><?php echo htmlspecialchars($movie['ott_platform']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-description">
                        <h2>Plot Of The Movie</h2>
                        <p><?php echo nl2br(htmlspecialchars($movie['plot'])); ?></p>
                    </div>
                </div>
            </div>

            <?php if ($actors->num_rows > 0): ?>
            <div class="cast-section">
                <h2>Cast</h2>
                <div class="cast-grid">
                    <?php while ($actor = $actors->fetch_assoc()): ?>
                        <div class="person-card">
                            <img src="<?php echo htmlspecialchars($actor['image_url']); ?>" alt="<?php echo htmlspecialchars($actor['name']); ?>" class="person-image">
                            <h3><?php echo htmlspecialchars($actor['name']); ?></h3>
                            <p><?php echo htmlspecialchars($actor['role']); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($crew->num_rows > 0): ?>
            <div class="crew-section">
                <h2>Crew</h2>
                <div class="crew-grid">
                    <?php while ($member = $crew->fetch_assoc()): ?>
                        <div class="person-card">
                            <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="person-image">
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p><?php echo htmlspecialchars($member['role']); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($songs->num_rows > 0): ?>
            <div class="songs-section">
                <h2>Songs</h2>
                <div class="songs-list">
                    <?php while ($song = $songs->fetch_assoc()): ?>
                        <div class="song-item">
                            <h3><?php echo htmlspecialchars($song['name']); ?></h3>
                            <p>Singer: <?php echo htmlspecialchars($song['singer']); ?></p>
                            <p>Music Director: <?php echo htmlspecialchars($song['music_director']); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Movie Trailer Section -->
            <div class="trailer-section">
                <h2>Movie Trailer</h2>
                <?php
                // Function to convert YouTube URL to embed format
                function getYouTubeEmbedUrl($url) {
                    $video_id = '';
                    
                    // Extract video ID from different YouTube URL formats
                    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
                        $video_id = $matches[1];
                    } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
                        $video_id = $matches[1];
                    } elseif (preg_match('/youtube\.com\/v\/([^\&\?\/]+)/', $url, $matches)) {
                        $video_id = $matches[1];
                    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
                        $video_id = $matches[1];
                    }
                    
                    if ($video_id) {
                        return 'https://www.youtube.com/embed/' . $video_id;
                    }
                    
                    return false;
                }
                
                $embed_url = getYouTubeEmbedUrl($movie['trailer_url']);
                if ($embed_url): 
                ?>
                    <div class="video-container">
                        <iframe 
                            width="100%" 
                            height="480" 
                            src="<?php echo htmlspecialchars($embed_url); ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    </div>
                <?php else: ?>
                    <p>Trailer not available</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>

    <style>
        .songs-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .song-item {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 5px;
        }

        .song-item h3 {
            margin-bottom: 0.5rem;
            color: #1a1a1a;
        }

        .song-item p {
            margin: 0.25rem 0;
            color: #666;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            margin: 20px 0;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .trailer-section {
            margin: 30px 0;
        }
        
        .trailer-section h2 {
            margin-bottom: 20px;
        }

        .movie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }

        .meta-item {
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .meta-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: #e50914;
            
           
        }

        .meta-item i {
            color: #e50914;
            font-size: 1.2em;
        }

        .meta-label {
            font-weight: bold;
            color: #666;
        }

        .meta-value {
            color: #333;
        }

        .ott-info {
            background: #e50914;
            color: white;
        }

        .ott-info i,
        .ott-info .meta-label,
        .ott-info .meta-value {
            color: white;
        }

        .ott-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ott-link:hover {
            text-decoration: underline;
        }

        .ott-link i.fa-external-link-alt {
            font-size: 0.8em;
        }

        @media (max-width: 768px) {
            .movie-meta {
                flex-direction: column;
            }

            .meta-item {
                width: 100%;
            }
        }
    </style>
</body>
</html> 