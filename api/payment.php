<?php
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$errors = [];
$card_number = $expiry = $cvv = $card_name = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showtime_id = (int)($_POST['showtime_id'] ?? 0);
    $seat_ids    = $_POST['seat_ids'] ?? [];
    $card_number = trim($_POST['card_number'] ?? '');
    $expiry      = trim($_POST['expiry'] ?? '');
    $cvv         = trim($_POST['cvv'] ?? '');
    $card_name   = trim($_POST['card_name'] ?? '');

    if (!preg_match('/^\d{16}$/', $card_number)) {
        $errors[] = 'Card number must be 16 digits.';
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $errors[] = 'Expiry must be MM/YY.';
    } else {
        list($mm, $yy) = explode('/', $expiry);
        if ((int)$yy < (int)date('y') || ((int)$yy === (int)date('y') && (int)$mm < (int)date('m'))) {
            $errors[] = 'Card has expired.';
        }
    }
    if (!preg_match('/^\d{3}$/', $cvv)) {
        $errors[] = 'Security code must be 3 digits.';
    }
    if (!preg_match('/^[A-Za-z ]+$/', $card_name)) {
        $errors[] = 'Name on card must contain only letters and spaces.';
    }
    if (empty($seat_ids)) {
        $errors[] = 'No seats selected.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        $ins = $pdo->prepare('INSERT INTO bookings (user_id, showtime_id, status) VALUES (?, ?, ?)');
        $ins->execute([$_SESSION['user_id'], $showtime_id, 'Confirmed']);
        $booking_id = $pdo->lastInsertId();
        $bs = $pdo->prepare('INSERT INTO booking_seats (booking_id, seat_id) VALUES (?, ?)');
        $up = $pdo->prepare('UPDATE seats SET is_booked = 1 WHERE seat_id = ?');
        foreach ($seat_ids as $sid) {
            $bs->execute([$booking_id, (int)$sid]);
            $up->execute([(int)$sid]);
        }
        $pdo->commit();
        header("Location: receipt.php?booking_id=$booking_id");
        exit;
    }
}

$showtime_id = (int)($_REQUEST['showtime_id'] ?? 0);
$seat_ids    = $_REQUEST['seat_ids'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment — <?= htmlspecialchars($card_name ?: '') ?></title>
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
    .form-container {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
    }
    .form-container h2 {
      margin-bottom: 20px;
      color: #004080;
      font-size: 1.5rem;
    }
    .error {
      color: #c0392b;
      font-size: 0.9rem;
      margin-bottom: 15px;
      text-align: left;
    }

    /* Form fields */
    input[type="text"] {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
      background: #fafafa;
    }

    /* Pay button */
    button[type="submit"] {
      width: 100%;
      padding: 12px;
      margin-top: 20px;
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
  <div class="form-container">
    <h2>Payment Details</h2>

    <?php if ($errors): ?>
      <div class="error">
        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="showtime_id" value="<?= $showtime_id ?>">
      <?php foreach ($seat_ids as $sid): ?>
        <input type="hidden" name="seat_ids[]" value="<?= htmlspecialchars($sid) ?>">
      <?php endforeach; ?>

      <input type="text"
             name="card_number"
             placeholder="Card Number"
             maxlength="16"
             value="<?= htmlspecialchars($card_number) ?>"
             required>

      <input type="text"
             name="expiry"
             placeholder="MM/YY"
             maxlength="5"
             pattern="(0[1-9]|1[0-2])\/\d{2}"
             oninput="this.value=this.value.replace(/[^\d]/g,'').replace(/^(\d{2})(\d)/,'$1/$2');"
             value="<?= htmlspecialchars($expiry) ?>"
             required>

      <input type="text"
             name="cvv"
             placeholder="CVV"
             maxlength="3"
             pattern="\d{3}"
             value="<?= htmlspecialchars($cvv) ?>"
             required>

      <input type="text"
             name="card_name"
             placeholder="Name on Card"
             pattern="[A-Za-z ]+"
             value="<?= htmlspecialchars($card_name) ?>"
             required>

      <button type="submit">Pay Now</button>
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

</body>
</html>
