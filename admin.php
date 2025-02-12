<?php

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

require 'config.php';



/* -----------------------------------------

   1. Handle Single-Record Deletion if POST

----------------------------------------- */

if (isset($_POST['delete_single'])) {

    $deleteId = $_POST['delete_single_id'] ?? null;

    if ($deleteId) {

        try {

            $sql = "DELETE FROM members WHERE id = :id";

            $stmt = $conn->prepare($sql);

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

            $stmt = $conn->prepare($sql);

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

    $stmt = $conn->prepare($sql);

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

        /* "Clear" link style */

        .search-container a {

            margin-left: 10px;

            text-decoration: none;

            color: #6c757d;

            align-self: center;

        }

        .search-container a:hover {

            text-decoration: underline;

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



        /* Delete Button Styles */

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

    </style>

</head>

<body>



<h1>Giáo Xứ Bridgeport - Admin</h1>



<div class="admin-container">



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

            <!-- Show a clear link only if a search is active -->

            <a href="admin.php">Clear</a>

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

                            <a class="btn edit-btn" href="edit_user.php?id=<?= $record['id']; ?>" data-record-id="<?= $record['id']; ?>">Edit</a>

                            

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

        <?php else: ?>

            <p class="no-records">No records found.</p>

        <?php endif; ?>

    </form>

</div>



<!-- JavaScript for disabling Edit buttons based on checkbox selection -->

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

