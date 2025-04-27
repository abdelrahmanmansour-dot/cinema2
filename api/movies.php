<?php
require_once 'db.php';
session_start();
// redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// fetch movies with average ratings
$stmt = $pdo->query(
    "SELECT m.*, COALESCE(AVG(r.rating),0) AS avg_rating
     FROM movies m
     LEFT JOIN reviews r ON m.movie_id = r.movie_id
     GROUP BY m.movie_id"
);
$movies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Now Showing</title>
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
      font-size: 2rem;
      letter-spacing: 1px;
    }

    /* Main container */
    main {
      max-width: 960px;
      margin: 20px auto;
      padding: 0 20px;
    }

    /* Movie cards */
    .movie-card {
      background: #fff;
      display: flex;
      margin-bottom: 20px;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .movie-card:hover {
      transform: translateY(-4px);
    }
    .movie-card img {
      width: 120px;
      border-radius: 4px;
    }
    .details {
      margin-left: 20px;
      flex: 1;
    }
    .details h2 {
      margin: 0 0 8px;
      font-size: 1.5rem;
      color: #004080;
    }
    .details p {
      line-height: 1.4;
    }

    /* Stars */
    .stars {
      display: inline-block;
      vertical-align: middle;
    }
    .star {
      font-size: 20px;
      cursor: pointer;
      color: #ccc;
      transition: color 0.1s;
    }
    .star.hover,
    .star.selected {
      color: #f1c40f;
    }
    .avg-box {
      display: inline-block;
      border: 1px solid #ccc;
      padding: 2px 6px;
      margin-left: 10px;
      border-radius: 4px;
      min-width: 60px;
      text-align: center;
      font-weight: bold;
      background: #fafafa;
    }

    /* Buttons */
    .buttons {
      margin-top: 12px;
    }
    .buttons button {
      padding: 8px 14px;
      margin-right: 8px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.2s;
    }
    .trailer {
      background: #e67e22;
      color: #fff;
    }
    .trailer:hover {
      background: #d35400;
    }
    .buy {
      background: #c0392b;
      color: #fff;
    }
    .buy:hover {
      background: #992d22;
    }

    /* Footer */
    footer {
      background: #004080;
      color: #fff;
      text-align: center;
      padding: 12px 20px;
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
  <h1>Cinema Now Showing</h1>
</header>

<main>
  <?php foreach ($movies as $movie): ?>
    <div class="movie-card">
      <img src="<?= htmlspecialchars($movie['poster_url']) ?>"
           alt="<?= htmlspecialchars($movie['title']) ?> poster">
      <div class="details">
        <h2><?= htmlspecialchars($movie['title']) ?></h2>

        <div class="stars"
             data-movie-id="<?= $movie['movie_id'] ?>"
             data-avg-rating="<?= round($movie['avg_rating'],1) ?>">
          <?php for ($i = 1; $i <= 10; $i++): ?>
            <span class="star" data-value="<?= $i ?>">&#9733;</span>
          <?php endfor; ?>
          <span class="avg-box"><?= round($movie['avg_rating'],1) ?> Stars</span>
        </div>

        <p><strong>Genre:</strong> <?= htmlspecialchars($movie['genre']) ?></p>
        <p><?= nl2br(htmlspecialchars($movie['description'])) ?></p>

        <div class="buttons">
          <button class="trailer"
                  onclick="window.open('<?= htmlspecialchars($movie['trailer_url'] ?? '#') ?>','_blank')">
            Trailer
          </button>
          <button class="buy"
                  onclick="location.href='booking.php?movie_id=<?= $movie['movie_id'] ?>'">
            Buy Tickets
          </button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
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
// star hover & click behavior
document.querySelectorAll('.stars').forEach(function(box) {
  const stars = box.querySelectorAll('.star');
  const movieId = box.getAttribute('data-movie-id');
  const avgBox = box.querySelector('.avg-box');
  let initialAvg = parseFloat(box.getAttribute('data-avg-rating'));

  stars.forEach(function(star) {
    star.addEventListener('mouseover', function() {
      const val = +this.dataset.value;
      stars.forEach(s => s.classList.toggle('hover', +s.dataset.value <= val));
      avgBox.textContent = val + ' Stars';
    });

    star.addEventListener('mouseout', function() {
      stars.forEach(s => s.classList.remove('hover'));
      avgBox.textContent = initialAvg.toFixed(1) + ' Stars';
    });

    star.addEventListener('click', function() {
      const val = +this.dataset.value;
      // send rating to server
      fetch('rate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ movie_id: movieId, rating: val })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          initialAvg = data.avg_rating;
          avgBox.textContent = initialAvg.toFixed(1) + ' Stars';
          stars.forEach(s => s.classList.toggle('selected', +s.dataset.value <= val));
        } else {
          alert(data.message || 'Rating failed.');
        }
      });
    });
  });
});
</script>

</body>
</html>
