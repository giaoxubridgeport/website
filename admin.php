<?php
require_once 'config.php';
session_start();

// Ensure FULLTEXT index exists (only needs to be run once)
$pdo->exec("ALTER TABLE members ADD FULLTEXT(first_name, last_name, email)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_create_timestamp ON members(create_timestamp);");

// Handle Search Query (Optimized with FULLTEXT Index)
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM members 
                           WHERE MATCH(first_name, last_name, email) AGAINST(:search IN BOOLEAN MODE)
                           ORDER BY create_timestamp DESC");
    $stmt->execute(['search' => $search]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all members sorted by timestamp
    $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM members ORDER BY create_timestamp DESC");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle Secure Bulk Deletion
if (isset($_POST['delete_selected'])) {
    $deleteIds = $_POST['delete_ids'] ?? [];
    
    if (!empty($deleteIds)) {
        // Prepare placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
        try {
            $stmt = $pdo->prepare("DELETE FROM members WHERE id IN ($placeholders)");
            $stmt->execute($deleteIds);
            $_SESSION['message'] = "Selected users have been deleted.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting users: " . $e->getMessage();
        }
    }
}

// Display messages
if (isset($_SESSION['message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['message'] . "</div>";
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
    unset($_SESSION['error']);
}

// Display members in a table
echo "<table>";
echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Action</th></tr>";
foreach ($members as $member) {
    echo "<tr>
            <td>{$member['id']}</td>
            <td>{$member['first_name']}</td>
            <td>{$member['last_name']}</td>
            <td>{$member['email']}</td>
            <td>
                <form method='POST'>
                    <input type='hidden' name='delete_ids[]' value='{$member['id']}'>
                    <button type='submit' name='delete_selected'>Delete</button>
                </form>
            </td>
          </tr>";
}
echo "</table>";

?>
