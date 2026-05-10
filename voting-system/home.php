<?php
require_once 'config/config.php';

// Fetch a quick stat for the hero section
$stmt = $pdo->query("SELECT COUNT(*) FROM voting_system");
$total_votes = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Online Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <style>
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary), #2c5282);
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }
        
        .hero h1 {
            color: white;
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
        }
        
        .hero p {
            font-size: 1.25rem;
            max-width: 800px;
            margin: 0 auto 2.5rem;
            opacity: 0.9;
        }
        
        .stat-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-size: 1.125rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(4px);
        }
        
        .stat-badge span {
            font-weight: 700;
            color: var(--success);
            font-size: 1.25rem;
        }

        /* Features Section */
        .features {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--card);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            display: inline-block;
            padding: 1rem;
            background: rgba(232, 57, 58, 0.1);
            border-radius: 50%;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="home.php" class="nav-brand">
            <img src="./photo/logo1.png" alt="Logo">
            VotingSystem
        </a>
        <ul class="nav-links">
            <li><a href="result.php">Live Results</a></li>
            <li><a href="user/loginpage.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Login</a></li>
            <li><a href="admin/adminloginpage.php" class="btn btn-primary" style="padding: 0.5rem 1rem;">Admin</a></li>
        </ul>
    </nav>

    <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
        <div style="background: #dcfce7; color: #166534; padding: 1rem; text-align: center; font-weight: bold;">
            Registration successful! Your account is pending admin approval.
        </div>
    <?php endif; ?>

    <section class="hero">
        <div class="stat-badge">
            Over <span><?php echo number_format($total_votes); ?></span> Votes Cast Securely
        </div>
        <h1>Your Voice. Your Vote.</h1>
        <p>Participate in the democratic process through our secure, transparent, and easy-to-use online voting platform. Register today and make your voice heard.</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="user/newaccountregistration.php" class="btn btn-accent" style="font-size: 1.125rem; padding: 1rem 2rem;">Register to Vote</a>
            <a href="howtovote.php" class="btn btn-outline" style="color: white; border-color: white; font-size: 1.125rem; padding: 1rem 2rem;">How it Works</a>
        </div>
    </section>

    <section class="features">
        <h2 class="section-title">Everything you need to vote</h2>
        <div class="grid">
            <a href="user/newaccountregistration.php" class="feature-card">
                <i class="fas fa-id-card feature-icon"></i>
                <h3>1. Register</h3>
                <p>Sign up with your valid Voter ID and details to get verified for the upcoming elections.</p>
            </a>
            
            <a href="user/loginpage.php" class="feature-card">
                <i class="fas fa-check-to-slot feature-icon" style="color: var(--primary); background: rgba(26, 60, 94, 0.1);"></i>
                <h3 style="color: var(--primary);">2. Cast Vote</h3>
                <p>Log in during the election window to securely cast your ballot for your preferred candidate.</p>
            </a>
            
            <a href="result.php" class="feature-card">
                <i class="fas fa-chart-pie feature-icon" style="color: var(--success); background: rgba(46, 204, 113, 0.1);"></i>
                <h3 style="color: var(--success);">3. View Results</h3>
                <p>Track the election outcome with real-time analytics once the voting period has officially closed.</p>
            </a>
        </div>
    </section>

</body>
</html>
