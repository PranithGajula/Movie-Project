<?php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Get current credentials
    $current_username = sanitize($_POST['current_username']);
    $current_password = $_POST['current_password'];
    
    // Get new credentials
    $new_username = sanitize($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current credentials
    $query = "SELECT admin_id, password FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($current_password, $admin['password'])) {
            // Check if new password matches confirmation
            if ($new_password === $confirm_password) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update credentials
                $update_query = "UPDATE admin_users SET username = ?, password = ? WHERE admin_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssi", $new_username, $hashed_password, $admin['admin_id']);
                
                if ($update_stmt->execute()) {
                    $success_message = "Credentials updated successfully! Please log in with your new credentials.";
                    // Log out the admin
                    session_destroy();
                } else {
                    $error_message = "Error updating credentials. Please try again.";
                }
            } else {
                $error_message = "New password and confirmation do not match.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    } else {
        $error_message = "Current username is incorrect.";
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Admin Credentials - MovieInfo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .credentials-form {
            max-width: 500px;
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

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus {
            border-color: #e50914;
            outline: none;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: #e50914;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #e50914;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php"><h1>MovieInfo</h1></a>
            </div>
        </nav>
    </header>

    <main>
        <div class="credentials-form">
            <h2>Change Admin Credentials</h2>
            
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

            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_username">Current Username</label>
                    <input type="text" id="current_username" name="current_username" required>
                </div>

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_username">New Username</label>
                    <input type="text" id="new_username" name="new_username" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="submit-btn">Update Credentials</button>
            </form>

            <a href="dashboard.php" class="back-link">Back to Dashboard</a>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 MovieInfo. All rights reserved.</p>
    </footer>
</body>
</html> 