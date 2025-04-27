<?php
require_once 'db.php';
session_start();

// redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// get movie_id from query
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
if (!$movie_id) {
    echo "<p>No movie selected.</p>";
    exit;
}

// fetch movie details
$stmt = $pdo->prepare('SELECT title, poster_url FROM movies WHERE movie_id = ?');
$stmt->execute([$movie_id]);
$movie = $stmt->fetch();
if (!$movie) {
    echo "<p>Movie not found.</p>";
    exit;
}

// fetch showtimes
$stStmt = $pdo->prepare(
    'SELECT showtime_id, show_date, show_time
     FROM showtimes
     WHERE movie_id = ?
     ORDER BY show_date, show_time'
);
$stStmt->execute([$movie_id]);
$showtimes = $stStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Tickets — <?= htmlspecialchars($movie['title']) ?></title>
  <style>
    /* Global */
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      margin: 0;
      padding: 0;
      color: #333;
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
      font-size: 1.75rem;
      letter-spacing: 1px;
    }

    /* Main content */
    main {
      max-width: 500px;
      margin: 30px auto;
      padding: 0 20px;
    }
    .container {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
    }
    .movie-header img {
      width: 120px;
      border-radius: 4px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }
    .movie-header h2 {
      margin: 15px 0 5px;
      color: #004080;
      font-size: 1.5rem;
    }

    /* Form */
    label {
      display: block;
      margin: 20px 0 8px;
      font-weight: bold;
      color: #004080;
    }
    select {
      width: 100%;
      padding: 12px;
      border-radius: 4px;
      border: 1px solid #ccc;
      background: #fafafa;
      font-size: 1rem;
    }
    button[type="submit"] {
      margin-top: 20px;
      padding: 12px;
      width: 100%;
      background: #004080;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    button[type="submit"]:hover {
      background: #003366;
    }

    /* Footer */
    footer {
      background: #004080;
      color: #fff;
      text-align: center;
      padding: 20px;
      margin-top: 40px;
      font-size: 0.9rem;
    }
    footer .social {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 10px;
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
    <div class="movie-header">
      <img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="Poster of <?= htmlspecialchars($movie['title']) ?>">
      <h2><?= htmlspecialchars($movie['title']) ?></h2>
    </div>

    <form action="seats.php" method="GET">
      <input type="hidden" name="movie_id" value="<?= $movie_id ?>">

      <label for="showtime">Choose Date &amp; Time</label>
      <select name="showtime_id" id="showtime" required>
        <?php foreach ($showtimes as $st): ?>
          <option value="<?= $st['showtime_id'] ?>">
            <?= date('M j, Y', strtotime($st['show_date'])) ?> @ <?= date('g:i A', strtotime($st['show_time'])) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Select Seats</button>
    </form>
  </div>
</main>

<footer>
  &copy; <?= date('Y') ?> Cinema Booking. All rights reserved.
  <div class="social">
  <a href="https://www.youtube.com/" >YouTube</a>
    <a href="https://www.instagram.com/" >Instagram</a>
    <a href="https://x.com/" >Twitter</a>
</footer>

</body>
</html>
