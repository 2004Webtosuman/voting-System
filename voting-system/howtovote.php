<?php

include("home.php");

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How to Vote</title>
    <link rel="stylesheet" href="styles.css"> <!-- Include your custom CSS file -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 2rem;
            color: #333;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .step {
            margin: 20px 0;
        }
        .step-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007BFF;
        }
        .step-content {
            margin-top: 5px;
            font-size: 1rem;
        }
        .vote-button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: #fff;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            margin-top: 20px;
        }
        .vote-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>How to Vote</h1>
    <div class="step">
        <div class="step-title">Step 1: Register and Log in</div>
        <div class="step-content">Visit the registration page and create an account using your details. Once registered, log in with your email and password.</div>
    </div>

    <div class="step">
        <div class="step-title">Step 2: Navigate to the Voting Page</div>
        <div class="step-content">After logging in, click on the "Voting" option in the navigation bar to access the ballot.</div>
    </div>

    <div class="step">
        <div class="step-title">Step 3: Select Your Candidate</div>
        <div class="step-content">Choose your preferred candidate from the list of available candidates by clicking the corresponding option.</div>
    </div>

    <div class="step">
        <div class="step-title">Step 4: Confirm Your Vote</div>
        <div class="step-content">Double-check your choice and click the "Submit Vote" button. Once submitted, your vote is final.</div>
    </div>

    <div class="step">
        <div class="step-title">Step 5: View Election Results (if available)</div>
        <div class="step-content">You can visit the "Results" section to view real-time election results after the voting period ends.</div>
    </div>

    <button class="vote-button" onclick="goToVotePage()">Go and Register First or Login</button>
</div>

<script>
    function goToVotePage() {
        window.location.href = 'newaccountregistration.php'; // Replace this with your actual voting page URL
    }
</script>
</body>
</html>
