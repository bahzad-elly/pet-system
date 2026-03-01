<?php
// delete_user.php
session_start();
require_once 'db.php';

// Security Check: Must be logged in AND must be an admin 
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only administrators can delete records.");
}

if (isset($_GET['id'])) {
    $user_id_to_delete = intval($_GET['id']);

    // Prevent an admin from deleting themselves
    if ($user_id_to_delete === $_SESSION['uid']) {
        die("Error: You cannot delete your own account.<br><br><a href='view_users.php'>Go Back</a>");
    }

    try {
        // Get the username for logging purposes
        $stmt = $pdo->prepare("SELECT username FROM users WHERE uid = :uid");
        $stmt->execute([':uid' => $user_id_to_delete]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user) {
            // Execute Deletion
            $delete_stmt = $pdo->prepare("DELETE FROM users WHERE uid = :uid");
            if ($delete_stmt->execute([':uid' => $user_id_to_delete])) {
                
                // Log the sensitive operation 
                $log_sql = "INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:log_uid, 'Delete', 'users', :targetid, 'Deleted user account: " . $target_user['username'] . "')";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->execute([':log_uid' => $_SESSION['uid'], ':targetid' => $user_id_to_delete]);

                // Redirect back
                header("location: view_users.php");
                exit;
            }
        } else {
            die("User not found.");
        }
    } catch (PDOException $e) {
        die("Error deleting user: " . $e->getMessage() . "<br><br><a href='view_users.php'>Go Back</a>");
    }
} else {
    die("No ID provided.");
}
?>