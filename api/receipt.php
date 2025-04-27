<?php
require_once 'db.php';
session_start();
// redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php'); exit;
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if (!$booking_id) {
    echo "<p>Invalid booking.</p>"; exit;
}

// fetch booking, showtime, movie
$stmt = $pdo->prepare(
    "SELECT b.booking_id, s.show_date, s.show_time, m.title
     FROM bookings b
     JOIN showtimes s ON b.showtime_id = s.showtime_id
     JOIN movies m ON s.movie_id = m.movie_id
     WHERE b.booking_id = ? AND b.user_id = ?"
);
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$info = $stmt->fetch();
if (!$info) {
    echo "<p>Booking not found.</p>"; exit;
}

// fetch seats
$seatStmt = $pdo->prepare(
    "SELECT st.seat_number
     FROM booking_seats bs
     JOIN seats st ON bs.seat_id = st.seat_id
     WHERE bs.booking_id = ?
     ORDER BY st.seat_number"
);
$seatStmt->execute([$booking_id]);
$seats = $seatStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt — <?= htmlspecialchars($info['title']) ?></title>
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

    /* Main */
    main {
      max-width: 500px;
      margin: 30px auto;
      padding: 0 20px;
    }
    .receipt {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
    }
    .receipt h2 {
      margin-bottom: 20px;
      color: #004080;
      font-size: 1.5rem;
    }
    .receipt p {
      margin: 8px 0;
      font-size: 1rem;
    }
    .receipt .seats {
      margin: 15px 0;
      font-weight: bold;
      color: #c0392b;
    }
    .receipt .note {
      margin-top: 20px;
      font-size: 0.9rem;
      font-style: italic;
      color: #666;
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
  <div class="receipt">
    <h2>Booking Confirmed</h2>
    <p><strong>Movie:</strong> <?= htmlspecialchars($info['title']) ?></p>
    <p>
      <strong>Date &amp; Time:</strong>
      <?= date('M j, Y', strtotime($info['show_date'])) ?>
      @
      <?= date('g:i A', strtotime($info['show_time'])) ?>
    </p>
    <p class="seats">
      Seats: <?= htmlspecialchars(implode(', ', $seats)) ?>
    </p>
    <p class="note">
      Please save this page or take a screenshot and show it when entering.
    </p>
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

</body>
</html>
