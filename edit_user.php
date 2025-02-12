<?php
// edit_user.php
require 'config.php';

// Initialize error message
$error = '';

// Get the user ID from the query parameter
$userId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If user clicked 'Cancel', go back to admin page without updating
    if (isset($_POST['cancel'])) {
        header('Location: admin.php');
        exit;
    }

    // Otherwise proceed with updating
    // We'll re-verify the user ID in case someone tampered with the form
    $userId      = $_POST['id'] ?? null;
    $firstName   = $_POST['first_name'] ?? '';
    $lastName    = $_POST['last_name'] ?? '';
    $email       = $_POST['email'] ?? '';
    $phoneNumber = $_POST['phone_number'] ?? '';

    // If there's no valid user ID, set an error
    if (empty($userId)) {
        $error = 'Invalid user ID.';
    } else {
        try {
            $sql = "UPDATE members
                    SET first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone_number = :phone_number
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name'   => $firstName,
                ':last_name'    => $lastName,
                ':email'        => $email,
                ':phone_number' => $phoneNumber,
                ':id'           => $userId
            ]);

            // Redirect back to admin page on success
            header('Location: admin.php');
            exit;
        } catch (PDOException $e) {
            $error = "Error updating record: " . $e->getMessage();
        }
    }
}

// If it’s a GET request (page load) and no update in process, fetch user data
$user = null;
if (empty($error) && !empty($userId) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT * FROM members WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "User not found.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching record: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Member - Giáo Xứ Bridgeport</title>
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

        /* Container that looks like a card */
        .form-container {
            background: #fff;
            width: 400px;
            max-width: 90%;
            margin: 20px auto;
            padding: 20px 25px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        /* Error message styling */
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }

        /* Labels and Inputs */
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            text-align: left;
        }
        input[type="text"],
        input[type="email"] {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Buttons */
        .buttons {
            text-align: center;
            margin-top: 10px;
        }
        .buttons input[type="submit"],
        .buttons button {
            display: inline-block;
            min-width: 100px;
            padding: 10px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
        }
        .buttons input[type="submit"] {
            background-color: #007BFF;
        }
        .buttons input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .buttons button[name="cancel"] {
            background-color: #6c757d;
        }
        .buttons button[name="cancel"]:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

<h1>Edit Member</h1>

<div class="form-container">
    <?php if (!empty($error)): ?>
        <!-- Display Error -->
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES); ?></p>
    <?php endif; ?>

    <?php if (empty($error) && $user): ?>
        <!-- If no errors and user found, show the form -->
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']); ?>">

            <label>First Name</label>
            <input type="text" name="first_name" 
                   value="<?= htmlspecialchars($user['first_name']); ?>" required>

            <label>Last Name</label>
            <input type="text" name="last_name" 
                   value="<?= htmlspecialchars($user['last_name']); ?>" required>

            <label>Email</label>
            <input type="email" name="email" 
                   value="<?= htmlspecialchars($user['email']); ?>" required>

            <label>Phone Number</label>
            <input type="text" name="phone_number" 
                   value="<?= htmlspecialchars($user['phone_number']); ?>">

            <div class="buttons">
                <input type="submit" value="Update">
                <button type="submit" name="cancel">Cancel</button>
            </div>
        </form>
    <?php elseif (empty($error)): ?>
        <!-- If there's no user and no error, this means invalid ID or user not found -->
        <p class="error">No user data to display.</p>
    <?php else: ?>
        <!-- If we have an error, we already showed it above. You could add a link here. -->
        <p style="text-align: center;">
            <a href="admin.php">Return to Admin Page</a>
        </p>
    <?php endif; ?>
</div>

</body>
</html>
