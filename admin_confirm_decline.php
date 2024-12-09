<?php
session_start();
include 'connection.php';

// Handle AJAX (POST) requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;

    // Parse the incoming JSON request
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Check if required data is present
    if (isset($inputData['transaction_id'], $inputData['action'])) {
        $transactionId = mysqli_real_escape_string($conn, $inputData['transaction_id']);
        $action = mysqli_real_escape_string($conn, $inputData['action']);

        // Validate and check transaction status
        $query = "SELECT * FROM transactions WHERE transaction_id = ? AND status = 'Pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $transaction = $result->fetch_assoc();  // Fetch the transaction data
            $amount = $transaction['amount'];  // The amount of the transaction

            $newStatus = ($action === 'confirm') ? 'completed' : (($action === 'decline') ? 'failed' : '');

            if ($newStatus) {
                // Update the transaction status
                $updateQuery = "UPDATE transactions SET status = ? WHERE transaction_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param('ss', $newStatus, $transactionId);

                if ($updateStmt->execute()) {
                    // If transaction is confirmed, update the user's balance
                    if ($action === 'confirm') {
                        // Get the user's ID associated with this transaction (assuming there's a user_id in the transactions table)
                        $userId = $transaction['user_id'];

                        // Update the user's balance
                        $updateBalanceQuery = "UPDATE users SET balance = balance + ? WHERE user_id = ?";
                        $balanceStmt = $conn->prepare($updateBalanceQuery);
                        $balanceStmt->bind_param('di', $amount, $userId);

                        if ($balanceStmt->execute()) {
                            echo json_encode(['status' => 'success', 'message' => 'Transaction confirmed and balance updated successfully', 'newStatus' => $newStatus]);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to update balance']);
                        }
                        $balanceStmt->close();
                    } else {
                        echo json_encode(['status' => 'success', 'message' => 'Transaction updated successfully', 'newStatus' => $newStatus]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update transaction']);
                }
                $updateStmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Transaction not found or already processed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    }
    exit; // End the script here for POST requests
}

// Render the transaction history (GET requests)
function displayTransactions()
{
    global $conn;

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "User not logged in.";
        return;
    }

    

    $query = "SELECT t.*, u.firstname, u.lastname 
              FROM transactions t
              JOIN users u ON t.user_id = u.user_id
              ORDER BY t.created_at DESC";
    
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "Error fetching transactions: " . mysqli_error($conn);
        return;
    }

    if (mysqli_num_rows($result) > 0) {
        while ($transaction = mysqli_fetch_assoc($result)) {
            $transactionId = htmlspecialchars($transaction['transaction_id']);
            $amount = htmlspecialchars($transaction['amount']);
            $date = htmlspecialchars($transaction['created_at']);
            $status = htmlspecialchars($transaction['status']);
            $transactionType = htmlspecialchars($transaction['transaction_type']);
            $crypto = htmlspecialchars($transaction['crypto_symbol']);
            $wallet = htmlspecialchars($transaction['wallet_address']);
            $firstname = htmlspecialchars($transaction['firstname']);
            $lastname = htmlspecialchars($transaction['lastname']);
            ?>
            <!-- Transaction Card HTML -->
            <div class="transaction-card" data-status="<?php echo $status; ?>" data-transaction-id="<?php echo ($transactionId); ?>">
                <div class="transaction-header">
                    <a href="#" class="hash"><?php echo $transactionId; ?></a>
                    <div class="amount"><?php echo $amount; ?></div>
                </div>
                <div class="transaction-details">
                    <div class="detail-row">
                        <span class="label">Date:</span>
                        <span class="value"><?php echo date('Y-m-d', strtotime($date)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Crypto:</span>
                        <span class="value"><?php echo $crypto; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Wallet:</span>
                        <span class="value"><?php echo $wallet; ?></span>
                    </div>
                    <div class="status-row">
                        <div>
                            <span class="status-badge" 
                                  style="background: #eab20817; color: var(--pending-color);">
                                <?php echo $status; ?>
                            </span>
                            <br>
                            <span class="type-badge credit" 
                                  style="background-color: rgba(34, 197, 94, 0.1); color: var(--positive-color);">
                                <?php echo $transactionType; ?>
                            </span>
                        </div>
                        <div class="transaction-dropdown">
                            <button class="dropdown-button"><i class="fa fa-sort-down"></i></button>
                            <div class="transaction-dropdown-menu">
                            <button type="button" class="popup_trigger dropdown-item"
                            data-transaction-id="<?php echo ($transactionId); ?>"
                            data-status="<?php echo ($status); ?>">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <section class="confirm_decline">
                <div class="bg_overlay">
                    <div class="popup_box">
                    <div class="wrapper">
                        <header>
                        <span>
                            Confirm/Decline   
                            <samp class="firstname"><?php echo $firstname; ?></samp>
                            <samp class="lastname"><?php echo $lastname; ?></samp>
                            transaction
                        </span>
                        <i class="material-icons close_confirm_decline">close</i>
                        </header>
                        <main class="question">
                        <span>Are you sure you want to confirm/decline this transaction?</span>
                        </main>
                        <footer class="confirm_decline_buttons">
                        <div class="wrapper">
                            <button id="close" class="close" type="button">Close</button>
                            <button onclick="confirmTransaction()" name="confirm" id="confirm" class="btn confirm" type="button">Confirm</button>
                            <button onclick="declineTransaction()" name="decline" id="decline" class="btn decline" type="button">Decline</button>
                            
                        </div>
                        </footer>
                    </div>
                    </div>
                </div>
                <div class="toast-container" id="toastContainer"></div>
            </section>

            <?php
        }
    } else {
        echo "<p>No transactions found.</p>";
    }
    ?>



<?php

    
}
