<?php
require_once 'db.php';
session_start();
// redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// get showtime and movie IDs
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;
$movie_id    = isset($_GET['movie_id'])    ? (int)$_GET['movie_id']    : 0;
if (!$showtime_id || !$movie_id) {
    echo "<p>Invalid selection.</p>";
    exit;
}

// fetch movie for context
$mvStmt = $pdo->prepare('SELECT title FROM movies WHERE movie_id = ?');
$mvStmt->execute([$movie_id]);
$movie = $mvStmt->fetch();

// fetch seats
$stStmt = $pdo->prepare('SELECT seat_id, seat_number, is_booked FROM seats WHERE showtime_id = ?');
$stStmt->execute([$showtime_id]);
$allSeats = $stStmt->fetchAll();

// map seats by row and number
$seatMap = [];
foreach ($allSeats as $s) {
    $row = strtoupper($s['seat_number'][0]);
    $num = (int)substr($s['seat_number'],1);
    $seatMap[$row][$num] = ['id' => $s['seat_id'], 'booked' => $s['is_booked']];
}

// define row order
$rows = ['A','B','C','D','E','F'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Select Seats for <?= htmlspecialchars($movie['title']) ?></title>
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

    /* Main */
    main {
      max-width: 600px;
      margin: 30px auto;
      padding: 0 20px;
    }
    .container {
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .header h2 {
      margin: 0;
      font-size: 1.5rem;
      color: #004080;
    }
    .legend {
      text-align: right;
    }
    .legend div {
      margin-bottom: 6px;
      font-size: 0.9rem;
    }
    .legend-seat {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 1px solid #333;
      margin-right: 6px;
      vertical-align: middle;
    }
    .legend-seat.booked { background: #000; }
    
    .screen {
      width: 100%;
      height: 24px;
      background: #ccc;
      margin: 20px 0;
      border-radius: 12px 12px 0 0;
      text-align: center;
      line-height: 24px;
      font-weight: bold;
      color: #333;
    }
    .seats {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .row {
      display: flex;
      margin-bottom: 6px;
    }
    .seat {
      width:  Thirty px;
      height:  Thirty px;
      margin: 4px;
      border: 1px solid #333;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      background: #fff;
      border-radius: 4px;
      transition: background 0.2s;
    }
    .seat.booked {
      background: #000;
      cursor: not-allowed;
    }
    .seat.selected,
    .seat:not(.booked):hover {
      background: #c0392b;
    }
    button#bookBtn {
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
    button#bookBtn:hover {
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
    <div class="header">
      <h2>Seats for <?= htmlspecialchars($movie['title']) ?></h2>
      <div class="legend">
        <div><span class="legend-seat booked"></span>Booked</div>
        <div><span class="legend-seat"></span>Available</div>
      </div>
    </div>

    <div class="screen">SCREEN</div>

    <div class="seats">
      <?php foreach ($rows as $row): ?>
        <div class="row">
          <?php for ($i = 1; $i <= 10; $i++):
            $info = $seatMap[$row][$i] ?? ['id'=>0,'booked'=>1];
            $classes = 'seat' . ($info['booked'] ? ' booked' : '');
          ?>
            <div class="<?= $classes ?>"
                 data-seat-id="<?= $info['id'] ?>"
                 data-seat-number="<?= $row.$i ?>">
              <?= $row.$i ?>
            </div>
          <?php endfor; ?>
        </div>
      <?php endforeach; ?>

      <button id="bookBtn">Book Selected Seats</button>
    </div>
  </div>
</main>

<footer>
  <div>&copy; <?= date('Y') ?> Cinema Booking. All rights reserved.</div>
  <div class="social">
  <a href="https://www.youtube.com/" >YouTube</a>
    <a href="https://www.instagram.com/" >Instagram</a>
    <a href="https://x.com/" >Twitter</a>
  </div>
</footer>

<script>
const showtimeId = <?= json_encode($showtime_id) ?>;
const selected = new Set();

document.querySelectorAll('.seat').forEach(el => {
  if (!el.classList.contains('booked')) {
    el.addEventListener('click', () => {
      const id = el.dataset.seatId;
      if (selected.has(id)) {
        selected.delete(id);
        el.classList.remove('selected');
      } else {
        selected.add(id);
        el.classList.add('selected');
      }
    });
  }
});

document.getElementById('bookBtn').addEventListener('click', () => {
  if (selected.size === 0) {
    alert('Please select at least one seat.');
    return;
  }
  const form = document.createElement('form');
  form.method = 'GET';
  form.action = 'payment.php';

  const stInput = document.createElement('input');
  stInput.type = 'hidden';
  stInput.name = 'showtime_id';
  stInput.value = showtimeId;
  form.appendChild(stInput);

  selected.forEach(id => {
    const seatInput = document.createElement('input');
    seatInput.type = 'hidden';
    seatInput.name = 'seat_ids[]';
    seatInput.value = id;
    form.appendChild(seatInput);
  });

  document.body.appendChild(form);
  form.submit();
});
</script>

</body>
</html>
