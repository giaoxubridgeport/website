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
            die("Error deleting record: " . $e->getMessage());
        }
    }
}

/* -------------------------------------
   2. Handle Multi-Deletion if POST
------------------------------------- */
if (isset($_POST['delete_selected'])) {
    $deleteIds = $_POST['delete_ids'] ?? [];
    if (!empty($deleteIds)) {
        $placeholders = rtrim(str_repeat('?,', count($deleteIds)), ',');
        try {
            $sql = "DELETE FROM members WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($deleteIds);
        } catch (PDOException $e) {
            die("Error deleting records: " . $e->getMessage());
        }
    }
}

/* -------------------------------------
   3. Handle Search if GET
------------------------------------- */
$searchTerm = $_GET['search'] ?? '';
$sql = "SELECT * FROM members ORDER BY create_timestamp DESC";

if (!empty($searchTerm)) {
    $sql = "SELECT * FROM members 
            WHERE first_name LIKE :search 
               OR last_name LIKE :search 
               OR email LIKE :search 
            ORDER BY create_timestamp DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    if (!empty($searchTerm)) {
        $stmt->bindValue(':search', "%$searchTerm%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
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
</div>
</body>
</html>
