<?php
// index.php

require 'config.php'; // Ensure that $pdo is defined in config.php

// Initialize messages
$success = '';
$error   = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and trim form data
    $firstName   = trim($_POST['first_name'] ?? '');
    $lastName    = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    
    // Basic validations

    // 1. Check required fields
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = "Please fill out all required fields.";
    }
    // 2. Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    // 3. Validate phone number (optional, if not empty)
    elseif (!empty($phoneNumber) && !preg_match('/^[0-9\-\(\)\+\s]+$/', $phoneNumber)) {
        $error = "Please enter a valid phone number.";
    }

    // If no errors so far, check for duplicate email
    if (empty($error)) {
        try {
            // Check if email already exists
            $checkSql = "SELECT COUNT(*) FROM members WHERE email = :email";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':email' => $email]);
            $emailCount = (int) $checkStmt->fetchColumn();

            if ($emailCount > 0) {
                $error = "That email is already registered. Please use a different one.";
            } else {
                // Insert data into the database
                $sql = "INSERT INTO members (first_name, last_name, email, phone_number)
                        VALUES (:first_name, :last_name, :email, :phone_number)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first_name'   => $firstName,
                    ':last_name'    => $lastName,
                    ':email'        => $email,
                    ':phone_number' => $phoneNumber
                ]);
                
                // Provide success feedback
                $success = "Registration successful! | Đăng ký thành công!";
            }
        } catch (PDOException $e) {
            // For production, consider logging this instead of showing full error
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration - Giáo Xứ Bridgeport</title>
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
        h1, h2 {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        /* Container Styling (matching admin.php) */
        .admin-container {
            width: 400px;
            max-width: 95%;
            margin: 20px auto;
            background: #fff;
            padding: 20px 25px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        /* Form Labels */
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            text-align: left;
        }
        /* Form Inputs */
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* Success & Error Messages */
        .success {
            color: green;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }
        /* Submit & Reset Buttons */
        .submit-container {
            text-align: center;
        }
        .submit-container input[type="submit"],
        .submit-container input[type="reset"] {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }
        .submit-container input[type="submit"] {
            background-color: #007BFF;
        }
        .submit-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .submit-container input[type="reset"] {
            background-color: #6c757d;
        }
        .submit-container input[type="reset"]:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <h1>Giáo Xứ Bridgeport</h1>
    <h2>Register | Đăng Ký</h2>
    <div class="admin-container">
        <?php if (!empty($success)) : ?>
            <p class="success"><?= htmlspecialchars($success, ENT_QUOTES); ?></p>
        <?php endif; ?>
        <?php if (!empty($error)) : ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES); ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>First Name | Tên <span style="color:red;">*</span></label>
            <input type="text" name="first_name" required>
            
            <label>Last Name | Họ <span style="color:red;">*</span></label>
            <input type="text" name="last_name" required>
            
            <label>Email <span style="color:red;">*</span></label>
            <input type="email" name="email" required>
            
            <label>Phone Number | Điện Thoại</label>
            <input type="text" name="phone_number">
            
            <div class="submit-container">
                <input type="submit" value="Submit">
                <input type="reset" value="Reset">
            </div>
        </form>
    </div>
</body>
</html>
