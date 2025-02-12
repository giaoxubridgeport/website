<?php
// index.php

require 'config.php'; // Ensure it defines $pdo, e.g. `$pdo = new PDO(...)`

/************************************************
 * 1. Simple Internationalization Setup
 ***********************************************/
$messages = [
    'en' => [
        // General
        'language_label'          => 'Language:',
        'language_english'        => 'English',
        'language_vietnamese'     => 'Vietnamese',

        // Headings
        'site_title'              => 'Bridgeport Parish',
        'register_heading'        => 'Register',

        // Form Labels
        'label_first_name'        => 'First Name',
        'label_last_name'         => 'Last Name',
        'label_email'             => 'Email',
        'label_phone'             => 'Phone Number',

        // Required note
        'required_note'           => '*',

        // Validation / Errors
        'error_required'          => 'Please fill out all required fields.',
        'error_email_invalid'     => 'Please enter a valid email address.',
        'error_phone_invalid'     => 'Please enter a valid phone number.',
        'error_email_registered'  => 'Email is registered. Please use a different email address.',
        'error_generic'           => 'Error: ',

        // Success
        'success_msg'             => 'Registration successful!',

        // Buttons
        'btn_submit'              => 'Submit',
        'btn_reset'               => 'Reset',
    ],
    'vi' => [
        // General
        'language_label'          => 'Ngôn ngữ:',
        'language_english'        => 'Tiếng Anh',
        'language_vietnamese'     => 'Tiếng Việt',

        // Headings
        'site_title'              => 'Giáo Xứ Bridgeport',
        'register_heading'        => 'Đăng Ký',

        // Form Labels
        'label_first_name'        => 'Tên',
        'label_last_name'         => 'Họ',
        'label_email'             => 'Email',
        'label_phone'             => 'Điện Thoại',

        // Required note
        'required_note'           => '*',

        // Validation / Errors
        'error_required'          => 'Vui lòng điền đầy đủ các trường bắt buộc.',
        'error_email_invalid'     => 'Vui lòng nhập địa chỉ email hợp lệ.',
        'error_phone_invalid'     => 'Vui lòng nhập số điện thoại hợp lệ.',
        'error_email_registered'  => 'Email đã được đăng ký. Vui lòng sử dụng địa chỉ khác.',
        'error_generic'           => 'Lỗi: ',

        // Success
        'success_msg'             => 'Đăng ký thành công!',

        // Buttons
        'btn_submit'              => 'Gửi',
        'btn_reset'               => 'Xóa',
    ]
];

// Decide which language to use (default to 'en')
$lang = $_GET['lang'] ?? 'en';
if (!in_array($lang, ['en', 'vi'])) {
    $lang = 'en';
}

/**
 * Helper function to get the translation
 */
function t($key) {
    global $messages, $lang;
    return $messages[$lang][$key] ?? $key;
}

/************************************************
 * 2. Process Form Submission
 ***********************************************/
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and trim form data
    $firstName   = trim($_POST['first_name'] ?? '');
    $lastName    = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');

    // 1. Check required fields: first name & last name
    if (empty($firstName) || empty($lastName)) {
        $error = t('error_required');
    }
    // 2. Validate email format only if not empty
    elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('error_email_invalid');
    }
    // 3. Validate phone number if not empty
    elseif (!empty($phoneNumber) && !preg_match('/^[0-9\-\(\)\+\s]+$/', $phoneNumber)) {
        $error = t('error_phone_invalid');
    }

    // Only proceed with DB checks and insert if no errors so far
    if (empty($error)) {
        try {
            // If email is provided, check if it’s already in use
            if (!empty($email)) {
                $checkSql = "SELECT COUNT(*) FROM members WHERE email = :email";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':email' => $email]);
                $emailCount = (int) $checkStmt->fetchColumn();

                if ($emailCount > 0) {
                    $error = t('error_email_registered');
                }
            }

            // Insert only if no error from the duplicate check
            if (empty($error)) {
                $sql = "INSERT INTO members (first_name, last_name, email, phone_number)
                        VALUES (:first_name, :last_name, :email, :phone_number)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first_name'   => $firstName,
                    ':last_name'    => $lastName,
                    ':email'        => $email ?: null, // if empty, store as NULL
                    ':phone_number' => $phoneNumber ?: null
                ]);

                $success = t('success_msg');
            }
        } catch (PDOException $e) {
            // For production, consider logging instead of showing full error
            $error = t('error_generic') . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('site_title'), ENT_QUOTES) ?></title>
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
        /* Language switcher */
        .language-switcher {
            text-align: center;
            margin-top: 10px;
        }
        .language-switcher a {
            margin: 0 5px;
            text-decoration: none;
            color: #007BFF;
        }
        .language-switcher a:hover {
            text-decoration: underline;
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

        /*************************************************
         * Two videos side by side in a responsive layout
         *************************************************/
        .video-container {
            display: flex;            /* enable flex layout for side by side */
            flex-wrap: wrap;          /* wrap to next line if not enough space */
            justify-content: center;  /* center horizontally */
            gap: 20px;                /* space between video wrappers */
            margin: 20px auto;        /* top/bottom margin, center horizontally */
            max-width: 1200px;        /* optional max width */
        }
        /* Each video is wrapped in a .video-wrapper for independent control */
        .video-wrapper {
            flex: 1 1 400px;   /* grows/shrinks, minimum ~400px */
            max-width: 560px;  /* keep typical YouTube width limit */
            box-sizing: border-box;
        }
        /* Make each iframe responsive within .video-wrapper */
        .video-wrapper iframe {
            width: 100%;
            height: 315px;  /* 16:9 ratio for a 560px width embed */
            border: none;
        }
    </style>
</head>
<body>

<!-- Language Switcher -->
<div class="language-switcher">
    <span><?= t('language_label') ?></span>
    <a href="?lang=en"><?= t('language_english') ?></a> | 
    <a href="?lang=vi"><?= t('language_vietnamese') ?></a>
</div>

<h1><?= htmlspecialchars(t('site_title'), ENT_QUOTES) ?></h1>
<h2><?= htmlspecialchars(t('register_heading'), ENT_QUOTES) ?></h2>

<div class="admin-container">
    <?php if (!empty($success)) : ?>
        <p class="success"><?= htmlspecialchars($success, ENT_QUOTES); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES); ?></p>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- First Name (required) -->
        <label>
            <?= t('label_first_name') ?> 
            <span style="color:red;"><?= t('required_note') ?></span>
        </label>
        <input type="text" name="first_name">

        <!-- Last Name (required) -->
        <label>
            <?= t('label_last_name') ?> 
            <span style="color:red;"><?= t('required_note') ?></span>
        </label>
        <input type="text" name="last_name">

        <!-- Email (optional) -->
        <label><?= t('label_email') ?></label>
        <input type="email" name="email">

        <!-- Phone Number (optional) -->
        <label><?= t('label_phone') ?></label>
        <input type="text" name="phone_number">
        
        <div class="submit-container">
            <input type="submit" value="<?= htmlspecialchars(t('btn_submit'), ENT_QUOTES) ?>">
            <input type="reset"  value="<?= htmlspecialchars(t('btn_reset'), ENT_QUOTES) ?>">
        </div>
    </form>
</div>

<!-- Two Embedded YouTube Videos Side by Side Below the Form -->
<div class="video-container">
    <!-- First Video Wrapper -->
    <div class="video-wrapper">
        <iframe 
            src="https://www.youtube.com/embed/N7dxC2tdSNQ?si=gcWl3-8rlSUVTSv4"
            title="YouTube video player"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen>
        </iframe>
    </div>

    <!-- Second Video Wrapper -->
    <div class="video-wrapper">
        <iframe
            src="https://www.youtube.com/embed/VPi1dYZsj9g?si=wcSejPCJh5DV3kk2"
            title="YouTube video player"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen>
        </iframe>
    </div>
</div>

</body>
</html>
