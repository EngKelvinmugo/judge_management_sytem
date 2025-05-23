<?php
// TEMPORARY LOGIN BYPASS - REMOVE AFTER TESTING
// Copy this code to login.php temporarily

require_once 'config/db.php';
require_once 'config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin.php");
        exit();
    } else {
        header("Location: judge.php");
        exit();
    }
}

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    
    if (empty($username) || empty($password) || empty($user_type)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            if ($user_type === 'admin') {
                $stmt = $pdo->prepare("SELECT id, username FROM admins WHERE username = ?");
            } else {
                $stmt = $pdo->prepare("SELECT id, username, display_name FROM judges WHERE username = ?");
            }
            
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // BYPASS PASSWORD CHECK FOR TESTING - REMOVE THIS IN PRODUCTION
                // Start session and set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user_type;
                
                if ($user_type === 'judge' && isset($user['display_name'])) {
                    $_SESSION['display_name'] = $user['display_name'];
                }
                
                // Redirect to appropriate page
                if ($user_type === 'admin') {
                    header("Location: admin.php");
                    exit();
                } else {
                    header("Location: judge.php");
                    exit();
                }
            } else {
                $error = "User not found.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Judge Management System</title>
    <style>
        /* Inline CSS for login page */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }
        
        .warning-banner {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            color: #92400e;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Judge Management System</h2>
        
        <div class="warning-banner">
            ⚠️ TEST MODE: Password verification bypassed
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="user_type">Login As:</label>
                <select id="user_type" name="user_type" required>
                    <option value="">Select Role</option>
                    <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="judge" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'judge') ? 'selected' : ''; ?>>Judge</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>