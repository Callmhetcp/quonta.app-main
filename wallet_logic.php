<?php
include 'connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit;
}

// Get the logged-in user's ID from session
$userId = $_SESSION['user_id'];

// Query to get the user's crypto data from the database
$query = "SELECT crypto_symbol, amount FROM transactions WHERE user_id = ? AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

// Sum the amounts for each crypto_symbol
$cryptoData = [];
while ($row = $result->fetch_assoc()) {
    $symbol = $row['crypto_symbol'];
    $amount = floatval($row['amount']); // Ensure the amount is a number
    if (isset($cryptoData[$symbol])) {
        $cryptoData[$symbol] += $amount;
    } else {
        $cryptoData[$symbol] = $amount;
    }
}

$stmt->close();
?>