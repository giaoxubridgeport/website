<?php
session_start();

require 'config.php';

// Array to store user-facing error messages
$errors = [];

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
            // Log the detailed error for the admin/developer
            error_log("Error deleting single record: " . $e->getMessage());
            // Show a generic error to the user
            $errors[] = "An error occurred while deleting the record. Please contact the administrator.";
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
            error_log("Error deleting multiple records: " . $e->getMessage());
            $errors[] = "An error occurred while deleting the selected records. Please contact the administrator.";
        }
    }
}

/* -------------------------------------
   3. Handle Search and Pagination
------------------------------------- */

// 3a. Get the current search term
$searchTerm = $_GET['search'] ?? '';
$searchTermLower = strtolower($searchTerm); // Use lowercase for case-insensitive matching

// 3b. Pagination variables
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; // Number of records per page
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// 3c. Build queries for counting and fetching (using LOWER(...) for case-insensitive search)
if (!empty($searchTerm)) {
    // Searching
    $countSql = "SELECT COUNT(*) FROM members
                 WHERE LOWER(first_name) LIKE :search
                    OR LOWER(last_name)  LIKE :search
                    OR LOWER(email)      LIKE :search";

    $dataSql  = "SELECT *
                 FROM members
                 WHERE LOWER(first_name) LIKE :search
                    OR LOWER(last_name)  LIKE :search
                    OR LOWER(email)      LIKE :search
                 ORDER BY create_timestamp DESC
                 LIMIT :limit OFFSET :offset";
} else {
    // No search
    $countSql = "SELECT COUNT(*) FROM members";
    $dataSql  = "SELECT *
                 FROM members
                 ORDER BY create_timestamp DESC
                 LIMIT :limit OFFSET :offset";
}

// 3d. Fetch total record count
try {
    $stmtCount = $pdo->prepare($countSql);
    if (!empty($searchTerm)) {
        $stmtCount->bindValue(':search', '%' . $searchTermLower . '%', PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $totalCount = (int)$stmtCount->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting records: " . $e->getMessage());
    $errors[] = "An error occurred while counting records. Please contact the administrator.";
    $totalCount = 0;
}

$totalPages = ($totalCount > 0) ? ceil($totalCount / $limit) : 1;

// 3e. Fetch the actual records for this page
try {
    $stmt = $pdo->prepare($dataSql);
    if (!empty($searchTerm)) {
        $stmt->bindValue(':search', '%' . $searchTermLower . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching records: " . $e->getMessage());
    $errors[] = "An error occurred while fetching the records. Please contact the administrator.";
    $records = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Giáo Xứ Bridgeport</title>
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        /* Body */
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f9fc;
            color: #333;
        }
        /* Headings */
        h1 {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        /* Main Container */
        .admin-container {
            width: 1000px; /* Adjust as needed */
            max-width: 95%;
            margin: 20px auto;
            background: #fff;
            padding: 20px 25px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        /* Search Container */
        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .search-container input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-right: none;
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
        /* "Clear" button style */
        a.btn-clear {
            display: inline-block;
            padding: 10px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            background-color: #6c757d; /* a muted color */
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            align-self: center; /* aligns with the search button */
        }
        a.btn-clear:hover {
            background-color: #5a6268;
        }
        /* Table Styles */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .admin-table thead {
            background-color: #eee;
        }
        .admin-table th, .admin-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .admin-table th {
            font-weight: bold;
        }
        .no-records {
            text-align: center;
            margin-top: 20px;
        }
        /* Buttons */
        .btn {
            padding: 6px 12px;
            text-decoration: none;
            background-color: #007BFF;
            color: #fff;
            border-radius: 4px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        /* Delete Single Button */
        .delete-single-btn {
            padding: 6px 12px;
            background-color: #dc3545;
            color: #fff;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .delete-single-btn:hover {
            background-color: #c82333;
        }
        /* Delete Selected Button */
        .delete-container {
            text-align: right;
            margin-bottom: 10px;
        }
        .delete-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background-color: #dc3545;
            color: #fff;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        /* Error Messages */
        .error-messages {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-messages ul {
            list-style-type: disc;
            margin-left: 20px;
        }
        /* Pagination */
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a,
        .pagination span {
            display: inline-block;
            margin: 0 5px;
            padding: 6px 10px;
            text-decoration: none;
            color: #007BFF;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #eee;
        }
        .pagination .current-page {
            background-color: #007BFF;
            color: #fff;
            pointer-events: none;
            border: 1px solid #007BFF;
        }
    </style>
</head>
<body>

<h1>Giáo Xứ Bridgeport - Admin</h1>

<div class="admin-container">

    <!-- Display error messages, if any -->
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 1. Search Form (GET) -->
    <div class="search-container">
        <form method="GET" action="" style="display: flex;">
            <input
                type="text"
                name="search"
                placeholder="Search first name, last name, email..."
                value="<?= htmlspecialchars($searchTerm, ENT_QUOTES); ?>"
            >
            <button type="submit">Search</button>
        </form>
        <?php if (!empty($searchTerm)): ?>
            <a href="admin.php" class="btn-clear">Clear</a>
        <?php endif; ?>
    </div>

    <!-- 2. Deletion Form (POST) for Multi-Delete -->
    <form method="POST" action=""
          onsubmit="return confirm('Are you sure you want to delete the selected records?');">

        <!-- Only show the "Delete Selected" button if records exist -->
        <?php if (!empty($records)): ?>
            <div class="delete-container">
                <button type="submit" name="delete_selected" class="delete-btn">
                    Delete Selected
                </button>
            </div>
        <?php endif; ?>

        <!-- Table of Results -->
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
                        <!-- Checkbox for multi-deletion -->
                        <td>
                            <input type="checkbox" name="delete_ids[]" value="<?= $record['id']; ?>">
                        </td>
                        <td><?= $record['id']; ?></td>
                        <td><?= htmlspecialchars($record['first_name']); ?></td>
                        <td><?= htmlspecialchars($record['last_name']); ?></td>
                        <td><?= htmlspecialchars($record['email']); ?></td>
                        <td><?= htmlspecialchars($record['phone_number']); ?></td>
                        <td><?= $record['create_timestamp']; ?></td>
                        <td><?= $record['modify_timestamp']; ?></td>
                        <td>
                            <!-- Edit Link with extra class and data attribute -->
                            <a class="btn edit-btn"
                               href="edit_user.php?id=<?= $record['id']; ?>"
                               data-record-id="<?= $record['id']; ?>">
                                Edit
                            </a>
                            <!-- Single Delete Form/Button -->
                            <form method="POST" action="" style="display:inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this record?');">
                                <input type="hidden" name="delete_single_id" value="<?= $record['id']; ?>">
                                <button type="submit" name="delete_single" class="delete-single-btn">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <!-- Previous Page Link -->
                    <?php if ($page > 1): ?>
                        <a href="?search=<?= urlencode($searchTerm); ?>&page=<?= $page - 1; ?>">Previous</a>
                    <?php endif; ?>

                    <!-- Page Number Links -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current-page"><?= $i; ?></span>
                        <?php else: ?>
                            <a href="?search=<?= urlencode($searchTerm); ?>&page=<?= $i; ?>"><?= $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next Page Link -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?search=<?= urlencode($searchTerm); ?>&page=<?= $page + 1; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p class="no-records">No records found.</p>
        <?php endif; ?>
    </form>
</div>

<!-- JavaScript for toggling Edit buttons based on checkbox selection -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get all checkboxes with name "delete_ids[]"
    const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]');
    // Get all edit buttons with class "edit-btn"
    const editButtons = document.querySelectorAll('.edit-btn');

    function updateEditButtons() {
        let checked = [];
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                checked.push(checkbox.value);
            }
        });
        if (checked.length === 0) {
            // Enable all edit buttons if none are checked
            editButtons.forEach(function(btn) {
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            });
        } else if (checked.length === 1) {
            // Enable only the edit button for the row that is checked; disable others
            editButtons.forEach(function(btn) {
                if (btn.getAttribute('data-record-id') === checked[0]) {
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                } else {
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.5';
                }
            });
        } else {
            // If more than one checkbox is checked, disable all edit buttons
            editButtons.forEach(function(btn) {
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.5';
            });
        }
    }

    // Attach change event listener to each checkbox
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateEditButtons);
    });
});
</script>

</body>
</html>
