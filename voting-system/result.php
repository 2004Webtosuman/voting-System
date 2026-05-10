<?php
require_once 'config/config.php';

// Check if voting is open
$stmt = $pdo->query("SELECT * FROM voting_config ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();
$voting_open = false;
$end_time = null;
if ($config) {
    $now = new DateTime();
    $start = new DateTime($config['start_time']);
    $end = new DateTime($config['end_time']);
    $end_time = $end->format('Y-m-d H:i:s');
    $voting_open = ($now >= $start && $now <= $end);
}

// Admins can see results anytime. Regular users can only see them after voting closes.
$is_admin = isset($_SESSION['admin']);

if ($voting_open && !$is_admin) {
    // Show waiting page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Results Pending</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="theme.css">
    </head>
    <body class="error-body">
        <div class="error-container">
            <i class="fas fa-lock error-icon" style="color: var(--primary);"></i>
            <h1>Results Unavailable</h1>
            <p>The election is currently ongoing. Results will be available after voting closes.</p>
            <p><strong>Voting closes at:</strong> <?php echo htmlspecialchars($end_time); ?></p>
            <br>
            <a href="home.php" class="btn btn-primary">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch total votes
$stmt = $pdo->query("SELECT COUNT(*) FROM voting_system");
$total_votes = $stmt->fetchColumn();

// Fetch candidate results
$stmt = $pdo->query("
    SELECT c.name, c.party, c.photo_url, COUNT(v.username) as vote_count 
    FROM candidates c
    LEFT JOIN voting_system v ON c.name = v.candidate_name
    GROUP BY c.id
    ORDER BY vote_count DESC
");
$results = $stmt->fetchAll();

// Determine the winner (if voting is closed)
$winner = null;
if (!$voting_open && count($results) > 0 && $total_votes > 0) {
    // Basic check for winner (assuming index 0 has the highest due to ORDER BY)
    $winner = $results[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Auto-refresh every 30 seconds -->
    <meta http-equiv="refresh" content="30">
    <title>Live Election Results</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <style>
        .results-container {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--card);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
        }
        
        .result-row {
            margin-bottom: 2rem;
        }
        
        .candidate-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .candidate-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
        }
        
        .vote-stats {
            font-weight: 700;
            color: var(--primary);
        }
        
        .bar-container {
            background-color: var(--border);
            border-radius: 9999px;
            height: 24px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .bar-fill {
            height: 100%;
            background-color: var(--accent);
            border-radius: 9999px;
            transition: width 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            white-space: nowrap;
        }
        
        /* Different colors for top 3 */
        .result-row:nth-child(1) .bar-fill { background-color: var(--primary); }
        .result-row:nth-child(2) .bar-fill { background-color: var(--accent); }
        .result-row:nth-child(3) .bar-fill { background-color: var(--success); }
        
        .winner-banner {
            background: #dcfce7;
            border: 1px solid #166534;
            color: #166534;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: bold;
        }
        
        .refresh-indicator {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 2rem;
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
            <li><a href="home.php">Home</a></li>
            <?php if ($is_admin): ?>
                <li><a href="admin/adminpage.php">Admin Dashboard</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="results-container">
        <h1 style="text-align: center; margin-bottom: 1rem;">Live Election Results</h1>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
            Total Votes Cast: <strong><?php echo number_format($total_votes); ?></strong>
            <?php if ($is_admin && $voting_open): ?>
                <br><span style="color: var(--accent);">(Admin View: Voting is still open)</span>
            <?php endif; ?>
        </p>
        
        <?php if ($winner): ?>
            <div class="winner-banner">
                <i class="fas fa-trophy" style="color: #ca8a04;"></i> Winner: <?php echo htmlspecialchars($winner['name']); ?> (<?php echo htmlspecialchars($winner['party']); ?>)
            </div>
        <?php endif; ?>

        <?php foreach ($results as $index => $row): ?>
            <?php 
                $percentage = ($total_votes > 0) ? round(($row['vote_count'] / $total_votes) * 100, 1) : 0; 
            ?>
            <div class="result-row">
                <div class="candidate-header">
                    <div class="candidate-info">
                        <img src="<?php echo htmlspecialchars($row['photo_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="candidate-photo" onerror="this.src='./photo/default_user.png'">
                        <div>
                            <div style="font-weight: 700;"><?php echo htmlspecialchars($row['name']); ?></div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);"><?php echo htmlspecialchars($row['party']); ?></div>
                        </div>
                    </div>
                    <div class="vote-stats">
                        <?php echo number_format($row['vote_count']); ?> votes
                    </div>
                </div>
                <div class="bar-container">
                    <div class="bar-fill" style="width: <?php echo max($percentage, 2); ?>%;">
                        <?php echo $percentage; ?>%
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="refresh-indicator">
            <i class="fas fa-sync fa-spin"></i> Auto-refreshing every 30 seconds
        </div>
    </div>
</body>
</html>
