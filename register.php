<?php
// register.php
require 'config.php';

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $firstName   = $_POST['first_name'] ?? '';
    $lastName    = $_POST['last_name'] ?? '';
    $email       = $_POST['email'] ?? '';
    $phoneNumber = $_POST['phone_number'] ?? '';

    // Basic server-side validation (optional)
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = "Please fill out required fields.";
    } else {
        // Insert data into the database
        try {
            $sql = "INSERT INTO members (first_name, last_name, email, phone_number) 
                    VALUES (:first_name, :last_name, :email, :phone_number)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name'  => $lastName,
                ':email'      => $email,
                ':phone_number' => $phoneNumber
            ]);

            // Provide feedback to the user
            $success = "Registration successful!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration - Church Family Records</title>
    <style>
        .form-container {
            margin: 20px auto;
            width: 300px;
        }
        label, input {
            display: block;
            margin-bottom: 8px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
<h1>Register</h1>

<div class="form-container">
    <?php if (!empty($success)) : ?>
        <p class="success"><?= $success; ?></p>
    <?php endif; ?>

    <?php if (!empty($error)) : ?>
        <p class="error"><?= $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>First Name <span style="color:red;">*</span></label>
        <input type="text" name="first_name" required>

        <label>Last Name <span style="color:red;">*</span></label>
        <input type="text" name="last_name" required>

        <label>Email <span style="color:red;">*</span></label>
        <input type="email" name="email" required>

        <label>Phone Number</label>
        <input type="text" name="phone_number">

        <input type="submit" value="Submit">
    </form>
</div>
</body>
</html>
