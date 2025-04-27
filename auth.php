<?php
require_once 'db.php';
session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($action === 'signup') {
        if ($username && $email && $password) {
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? OR username = ?');
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $message = 'Username or email already taken.';
            } else {
                $hash   = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                $insert->execute([$username, $email, $hash]);
                $message = 'Signup successful! You can now log in.';
            }
        } else {
            $message = 'All fields are required for signup.';
        }
    }

    if ($action === 'login') {
        if ($email && $password) {
            $stmt = $pdo->prepare('SELECT user_id, username, password FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                header('Location: movies.php');
                exit;
            } else {
                $message = 'Invalid email or password.';
            }
        } else {
            $message = 'Please enter email and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Auth</title>
  <style>
    /* Global */
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      margin: 0;
      padding: 0;
      color: #333;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    a { text-decoration: none; color: inherit; }

    /* Header */
    header {
      background: #004080;
      color: #fff;
      padding: 20px;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    header h1 {
      margin: 0;
      font-size: 1.5rem;
      letter-spacing: 1px;
    }

    /* Main */
    main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .container {
      background: #fff;
      width: 320px;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
    }
    .container h2 {
      margin-bottom: 20px;
      color: #004080;
      font-size: 1.25rem;
    }
    .message {
      color: #c0392b;
      font-size: 0.9rem;
      margin-bottom: 15px;
      min-height: 1.2em;
    }

    /* Forms */
    form {
      display: none;
      flex-direction: column;
    }
    form.active {
      display: flex;
    }
    input {
      padding: 12px;
      margin: 8px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
      background: #fafafa;
    }
    button {
      padding: 12px;
      margin-top: 10px;
      background: #004080;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    button:hover {
      background: #003366;
    }

    /* Toggle link */
    .toggle {
      margin: 10px 0;
      font-size: 0.9rem;
      color: #004080;
      cursor: pointer;
    }
    .toggle:hover {
      text-decoration: underline;
    }

    /* Footer */
    footer {
      background: #004080;
      color: #fff;
      text-align: center;
      padding: 20px;
      font-size: 0.9rem;
    }
    footer .social {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 8px;
    }
    footer .social a {
      color: #fff;
      font-weight: bold;
      transition: opacity 0.2s;
    }
    footer .social a:hover {
      opacity: 0.7;
    }
  </style>
</head>
<body>

<header>
  <h1>Cinema Booking</h1>
</header>

<main>
  <div class="container">
    <h2 id="formTitle">Login</h2>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <div class="toggle" onclick="toggleForm()" id="toggleLink">
      Don't have an account? Sign Up
    </div>

    <form id="loginForm" class="active" method="POST">
      <input type="hidden" name="action" value="login">
      <input type="email"   name="email"    placeholder="Email"    required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>

    <form id="signupForm" method="POST">
      <input type="hidden" name="action" value="signup">
      <input type="text"     name="username" placeholder="Username" required>
      <input type="email"    name="email"    placeholder="Email"    required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
    </form>
  </div>
</main>

<footer>
  &copy; <?= date('Y') ?> Cinema Booking. All rights reserved.
  <div class="social">
    <a href="https://www.youtube.com/" >YouTube</a>
    <a href="https://www.instagram.com/" >Instagram</a>
    <a href="https://x.com/" >Twitter</a>
  </div>
</footer>

<script>
function toggleForm() {
  const login  = document.getElementById('loginForm');
  const signup = document.getElementById('signupForm');
  const title  = document.getElementById('formTitle');
  const link   = document.getElementById('toggleLink');
  if (login.classList.contains('active')) {
    login.classList.remove('active');
    signup.classList.add('active');
    title.textContent = 'Sign Up';
    link.textContent  = 'Already have an account? Login';
  } else {
    signup.classList.remove('active');
    login.classList.add('active');
    title.textContent = 'Login';
    link.textContent  = "Don't have an account? Sign Up";
  }
}
</script>

</body>
</html>
