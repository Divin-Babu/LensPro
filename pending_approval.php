<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Registration Pending</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ecf0f1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        nav {
            background: var(--primary-color);
            padding: 0.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 70px;
            height: auto;
        }

        .logo {
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            letter-spacing: 2px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .pending-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .pending-icon {
            font-size: 4rem;
            color: var(--warning-color);
            margin-bottom: 1.5rem;
        }

        .pending-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .pending-message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .steps-container {
            text-align: left;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step i {
            color: var(--secondary-color);
            margin-top: 3px;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.2rem;
        }

        .step-description {
            font-size: 0.9rem;
            color: #666;
        }

        .home-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .home-button:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: center;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .pending-card {
                padding: 1.5rem;
                margin: 1rem;
            }

            .pending-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo-container">
            <a href="index.php" class="logo">
                <img src="images/logowithoutname.png" alt="LensPro Logo">
                LensPro
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="pending-card">
            <i class="fas fa-clock pending-icon"></i>
            <h1 class="pending-title">Registration Pending Approval</h1>
            <p class="pending-message">Thank you for registering with LensPro! Your application is currently under review by our admin team.</p>
            
            <div class="steps-container">
                <div class="step">
                    <i class="fas fa-check-circle"></i>
                    <div class="step-content">
                        <h3 class="step-title">Application Submitted</h3>
                        <p class="step-description">Your registration has been successfully received by our team.</p>
                    </div>
                </div>
                <div class="step">
                    <i class="fas fa-user-shield"></i>
                    <div class="step-content">
                        <h3 class="step-title">Under Review</h3>
                        <p class="step-description">Our admin team is reviewing your application and portfolio.</p>
                    </div>
                </div>
                <div class="step">
                    <i class="fas fa-envelope"></i>
                    <div class="step-content">
                        <h3 class="step-title">Email Notification</h3>
                        <p class="step-description">You will receive an email once your application is approved.</p>
                    </div>
                </div>
            </div>

            <a href="index.php" class="home-button">
                <i class="fas fa-home"></i> Return to Homepage
            </a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
    </footer>
</body>
</html>