<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password and create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword])) {
                // Redirect to login page with success message
                $_SESSION['signup_success'] = 'Registration successful! Please login.';
                header('Location: login.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #537CA2, #37526C, #8DA9C4);
            flex-direction: column;
            color: #fff;
            margin: 0;
        }
        .container {
            width: 400px;
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(15px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.3);
        }
        h2 {
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 24px;
        }
        form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .input-container {
            position: relative;
            width: 100%;
            max-width: 320px;
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        input {
            width: 100%;
            padding: 14px 40px 14px 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            outline: none;
            transition: background 0.3s ease;
        }
        input:focus {
            background: rgba(255, 255, 255, 0.3);
        }
        input::placeholder {
            color: #ddd;
        }
        button {
            width: 100%;
            max-width: 320px;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            font-weight: 600;
            margin-top: 10px;
        }
        .submit-btn {
            background: #ff7f50;
            color: white;
        }
        .submit-btn:hover {
            background: #ff6347;
        }
        .switch {
            text-align: center;
            margin-top: 12px;
        }
        .switch a {
            color: #fff;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .switch a:hover {
            color: #ff7f50;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #fff;
            font-size: 18px;
            transition: color 0.3s ease;
        }
        .toggle-password:hover {
            color: #ff7f50;
        }
        .toggle-password::before {
            content: '\1F441';
        }
        .toggle-password.active::before {
            content: '\1F576';
        }
        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .success-message {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="signup.php">
            <div class="input-container">
                <input type="text" name="first_name" placeholder="First Name" required>
            </div>
            <div class="input-container">
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
            <div class="input-container">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-container">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span class="toggle-password" onclick="togglePassword('password', this)"></span>
            </div>
            <div class="input-container">
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                <span class="toggle-password" onclick="togglePassword('confirm-password', this)"></span>
            </div>
            <button type="submit" class="submit-btn">Sign Up</button>
        </form>
        <div class="switch">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        function togglePassword(id, toggleIcon) {
            let input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                toggleIcon.classList.add("active");
            } else {
                input.type = "password";
                toggleIcon.classList.remove("active");
            }
        }
    </script>
</body>
</html>