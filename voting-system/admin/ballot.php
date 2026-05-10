<?php
require_once '../config/config.php';

// Check admin session
if (!isset($_SESSION['admin'])) {
    header("Location: adminloginpage.php");
    exit();
}

$admin_user = $_SESSION['admin'];
$msg = "";

// Handle Configuration Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $election_name = trim($_POST['election_name']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Clear old config and insert new (or just update)
    $pdo->query("TRUNCATE TABLE voting_config");
    $stmt = $pdo->prepare("INSERT INTO voting_config (election_name, start_time, end_time) VALUES (?, ?, ?)");
    if ($stmt->execute([$election_name, $start_time, $end_time])) {
        $msg = "Election configuration updated successfully.";
    } else {
        $msg = "Error updating election configuration.";
    }
}

// Handle Candidate Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    $name = trim($_POST['name']);
    $party = trim($_POST['party']);
    
    // Default photo
    $photo_url = "../photo/default_user.png";
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../photo/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
            $photo_url = "../photo/" . $fileName;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO candidates (name, party, photo_url) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $party, $photo_url])) {
        $msg = "Candidate added successfully.";
    } else {
        $msg = "Error adding candidate.";
    }
}

// Handle Candidate Deletion
if (isset($_GET['delete_candidate'])) {
    $id = (int)$_GET['delete_candidate'];
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    if ($stmt->execute([$id])) {
        $msg = "Candidate deleted successfully.";
    }
}

// Fetch current config
$config = $pdo->query("SELECT * FROM voting_config ORDER BY id DESC LIMIT 1")->fetch();

// Fetch candidates
$candidates = $pdo->query("SELECT * FROM candidates")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ballot - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }
        .card h2 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: var(--primary);
        }
        .candidate-list {
            list-style: none;
            padding: 0;
        }
        .candidate-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .candidate-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="adminpage.php" class="nav-brand">
            <i class="fas fa-user-shield" style="color: var(--primary);"></i>
            Admin Control Panel
        </a>
        <ul class="nav-links">
            <li><strong><?php echo htmlspecialchars($admin_user); ?></strong></li>
            <li><a href="adminpage.php">Voters</a></li>
            <li><a href="../result.php">View Results</a></li>
            <li><a href="logoutadminpage.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- Election Settings -->
        <div class="card">
            <h2><i class="fas fa-cog"></i> Election Settings</h2>
            <?php if ($msg): ?>
                <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Election Name</label>
                    <input type="text" name="election_name" class="form-control" value="<?php echo htmlspecialchars($config['election_name'] ?? 'General Election'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="datetime-local" name="start_time" class="form-control" value="<?php echo $config ? date('Y-m-d\TH:i', strtotime($config['start_time'])) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="datetime-local" name="end_time" class="form-control" value="<?php echo $config ? date('Y-m-d\TH:i', strtotime($config['end_time'])) : ''; ?>" required>
                </div>
                <button type="submit" name="update_config" class="btn btn-primary" style="width: 100%;">Save Settings</button>
            </form>
        </div>

        <!-- Add Candidate -->
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> Add Candidate</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Candidate Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Party / Affiliation</label>
                    <input type="text" name="party" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Candidate Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <button type="submit" name="add_candidate" class="btn btn-accent" style="width: 100%;">Add Candidate</button>
            </form>
        </div>
        
        <!-- Manage Candidates -->
        <div class="card" style="grid-column: 1 / -1;">
            <h2><i class="fas fa-users"></i> Manage Candidates</h2>
            <ul class="candidate-list">
                <?php if (count($candidates) > 0): ?>
                    <?php foreach ($candidates as $candidate): ?>
                        <li class="candidate-item">
                            <div class="candidate-info">
                                <img src="<?php echo htmlspecialchars($candidate['photo_url']); ?>" alt="Photo" class="candidate-img" onerror="this.src='../photo/default_user.png'">
                                <div>
                                    <strong><?php echo htmlspecialchars($candidate['name']); ?></strong><br>
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($candidate['party']); ?></span>
                                </div>
                            </div>
                            <a href="ballot.php?delete_candidate=<?php echo $candidate['id']; ?>" class="action-btn delete" onclick="return confirm('Remove this candidate?');" title="Remove">
                                <i class="fas fa-trash"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="text-align: center; color: var(--text-secondary); padding: 1rem;">No candidates added yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>
