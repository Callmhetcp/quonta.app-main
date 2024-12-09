<?php
include 'connection.php'; // Include your DB connection logic

// Fetch users dynamically
$query = "SELECT user_id, firstname, email, nationality, balance, status, avatar FROM users ORDER BY firstname ASC";
$result = $conn->query($query);

$users = []; // Initialize the $users array
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Add each user to the $users array
        $users[] = [
            'user_id' => $row['user_id'],
            'name' => $row['firstname'], // Map 'firstname' to 'name'
            'email' => $row['email'],
            'nationality' => $row['nationality'],
            'balance' => $row['balance'],
            'status' => $row['status'],
            'avatar' => $row['avatar'] ?: substr($row['firstname'], 0, 1) // Default avatar as the first letter of the name
        ];
    }
} else {
    echo "<p>No users found in the database.</p>";
}
?>

