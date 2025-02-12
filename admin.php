<?php

require 'config.php';

/* -----------------------------------------
   1. Handle Single-Record Deletion if POST
----------------------------------------- */
if (isset($_POST['delete_single'])) {
    $deleteId = $_POST['delete_single_id'] ?? null;
    if ($deleteId) {
        try {
            $sql = "DELETE FROM members WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $deleteId]);
        } catch (PDOException $e) {
            echo "Error deleting record: " . $e->getMessage();
            exit;
        }
    }
}

/* -------------------------------------
   2. Handle Multi-Deletion if POST
------------------------------------- */
if (isset($_POST['delete_selected'])) {
    $deleteIds = $_POST['delete_ids'] ?? [];
    if (!empty($deleteIds)) {
        // Prepare placeholders for the IN clause
        $placeholders = rtrim(str_repeat('?,', count($deleteIds)), ',');
        try {
            $sql = "DELETE FROM members WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($deleteIds);
        } catch (PDOException $e) {
            echo "Error deleting records: " . $e->getMessage();
            exit;
        }
    }
}

/* -------------------------------------
   3. Handle Search if GET
------------------------------------- */
$searchTerm = $_GET['search'] ?? '';
if (!empty($searchTerm)) {
    $sql = "SELECT * FROM members
            WHERE first_name LIKE :search
               OR last_name LIKE :search
               OR email LIKE :search
            ORDER BY create_timestamp DESC";
} else {
    $sql = "SELECT * FROM members
            ORDER BY create_timestamp DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    if (!empty($searchTerm)) {
        $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
    }
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching records: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Giáo Xứ Bridgeport</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f9fc;
            color: #333;
        }
        h1 {
            text-align: center;
            margin: 20px 0 10px;
            font-weight: 600;
        }
        .admin-container {
            width: 1000px;
            max-width: 95%;
            margin: 20px auto;
            background: #fff;
            padding: 20px 25px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .search-container input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        .search-container button {
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            background-color: #007BFF;
            color: #fff;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .admin-table th, .admin-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .admin-table th {
            font-weight: bold;
            background-color: #eee;
        }
    </style>
</head>
<body>

<h1>Giáo Xứ Bridgeport - Admin</h1>
<div class="admin-container">
    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search first name, last name, email..." value="<?= htmlspecialchars($searchTerm, ENT_QUOTES); ?>">
            <button type="submit">Search</button>
        </form>
        <?php if (!empty($searchTerm)): ?>
            <a href="admin.php">Clear</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete the selected records?');">
        <?php if (!empty($records)): ?>
            <button type="submit" name="delete_selected">Delete Selected</button>
        <?php endif; ?>
        <?php if ($records && count($records) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Multi-Select</th>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Created On</th>
                        <th>Modified On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                    <tr>
                        <td><input type="checkbox" name="delete_ids[]" value="<?= $record['id']; ?>"></td>
                        <td><?= $record['id']; ?></td>
                        <td><?= htmlspecialchars($record['first_name']); ?></td>
                        <td><?= htmlspecialchars($record['last_name']); ?></td>
                        <td><?= htmlspecialchars($record['email']); ?></td>
                        <td><?= htmlspecialchars($record['phone_number']); ?></td>
                        <td><?= $record['create_timestamp']; ?></td>
                        <td><?= $record['modify_timestamp']; ?></td>
                        <td>
                            <a class="btn edit-btn" href="edit_user.php?id=<?= $record['id']; }">Edit</a>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                <input type="hidden" name="delete_single_id" value="<?= $record['id']; }"> 
                                <button type="submit" name="delete_single">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No records found.</p>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
