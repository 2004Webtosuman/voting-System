<?php
require_once '../config/config.php';

// Ensure a user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: loginpage.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? null;

// Check user status
$stmt = $pdo->prepare("SELECT status FROM newaccountregistration WHERE username = :uname");
$stmt->execute(['uname' => $username]);
$user_status = $stmt->fetchColumn();

// Check if voting is open
$stmt = $pdo->query("SELECT * FROM voting_config ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();
$voting_open = false;
if ($config) {
    $now = new DateTime();
    $start = new DateTime($config['start_time']);
    $end = new DateTime($config['end_time']);
    $voting_open = ($now >= $start && $now <= $end);
}

// Fetch candidates
$stmt = $pdo->query("SELECT * FROM candidates");
$candidates = $stmt->fetchAll();

// Check if user has already voted
$stmt = $pdo->prepare("SELECT * FROM voting_system WHERE username = :uname");
$stmt->execute(['uname' => $username]);
$vote_record = $stmt->fetch();
$hasVoted = ($vote_record !== false);

$message = "";
$message_type = "";

// Handle voting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_candidate_id']) && !$hasVoted) {
    validate_csrf_token();

    if (!$voting_open) {
        $message = "Voting is currently closed.";
        $message_type = "error";
    } elseif ($user_status !== 'active') {
        $message = "Your account is not active. Status: " . htmlspecialchars($user_status);
        $message_type = "error";
    } else {
        $candidate_id = $_POST['vote_candidate_id'];
        
        // Get candidate name
        $stmt = $pdo->prepare("SELECT name FROM candidates WHERE id = :id");
        $stmt->execute(['id' => $candidate_id]);
        $candidate_name = $stmt->fetchColumn();
        
        if ($candidate_name) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            try {
                $stmt = $pdo->prepare("INSERT INTO voting_system (username, candidate_name, ip_address, user_agent) VALUES (:uname, :cname, :ip, :ua)");
                $stmt->execute([
                    'uname' => $username,
                    'cname' => $candidate_name,
                    'ip' => $ip_address,
                    'ua' => $user_agent
                ]);
                $hasVoted = true;
                $message = "Thank you, $username! Your vote has been cast successfully.";
                $message_type = "success";
                
                // Refresh vote record for receipt
                $stmt = $pdo->prepare("SELECT * FROM voting_system WHERE username = :uname");
                $stmt->execute(['uname' => $username]);
                $vote_record = $stmt->fetch();
                
            } catch (PDOException $e) {
                // Catch duplicate key exception (code 23000)
                if ($e->getCode() == 23000) {
                    $hasVoted = true;
                    $message = "You have already voted!";
                    $message_type = "error";
                } else {
                    $message = "Error saving your vote. " . $e->getMessage();
                    $message_type = "error";
                }
            }
        } else {
            $message = "Invalid candidate selected.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Page</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: var(--card);
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            text-align: center;
        }
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .receipt-card {
            background: #dcfce7;
            border: 1px solid #166534;
            color: #166534;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin: 2rem auto;
            max-width: 500px;
        }
        .receipt-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../home.php" class="nav-brand">
            <img src="../photo/logo1.png" alt="Logo">
            VotingSystem
        </a>
        <ul class="nav-links">
            <li><strong>Welcome, <?php echo htmlspecialchars($username); ?></strong></li>
            <li><span class="badge badge-<?php echo ($user_status === 'active') ? 'active' : (($user_status === 'pending') ? 'pending' : 'suspended'); ?>"><?php echo ucfirst(htmlspecialchars($user_status)); ?></span></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div style="text-align: center; padding: 1rem; margin-top: 2rem; background: <?php echo $message_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#166534' : '#991b1b'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($hasVoted): ?>
            <div class="receipt-card">
                <i class="fas fa-check-circle receipt-icon"></i>
                <h2>Vote Cast Successfully</h2>
                <p>Your vote has been securely recorded.</p>
                <?php if ($vote_record): ?>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #14532d;">
                        Timestamp: <?php echo htmlspecialchars($vote_record['voted_at']); ?><br>
                        Receipt ID: <?php echo hash('sha256', $vote_record['username'] . $vote_record['voted_at']); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php elseif (!$voting_open): ?>
            <div class="form-container" style="text-align: center;">
                <h2>Voting is Closed</h2>
                <p>The election window is currently closed. Please check back later.</p>
                <?php if ($config): ?>
                    <p>Voting opens: <?php echo htmlspecialchars($config['start_time']); ?></p>
                    <p>Voting closes: <?php echo htmlspecialchars($config['end_time']); ?></p>
                <?php endif; ?>
            </div>
        <?php elseif ($user_status !== 'active'): ?>
            <div class="form-container" style="text-align: center;">
                <h2>Account Not Active</h2>
                <p>Your account is currently <strong><?php echo htmlspecialchars($user_status); ?></strong>. You cannot vote until an administrator activates your account.</p>
            </div>
        <?php else: ?>
            <div class="ballot-container">
                <h1 style="text-align: center; margin-bottom: 2rem;">Official Ballot</h1>
                <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">Select one candidate and click Submit.</p>
                
                <form id="voteForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="vote_candidate_id" id="selectedCandidate" value="">
                    
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="candidate-card" onclick="selectCandidate(<?php echo $candidate['id']; ?>, this)">
                            <img src="<?php echo htmlspecialchars($candidate['photo_url']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="candidate-photo" onerror="this.src='./photo/default_user.png'">
                            <div class="candidate-info">
                                <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                            </div>
                            <div class="radio-circle"></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="button" class="btn btn-primary" onclick="showConfirmModal()" style="font-size: 1.25rem; padding: 1rem 3rem;" disabled id="submitBtn">Cast Vote</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Your Vote</h2>
            <p>Are you sure you want to cast your vote for the selected candidate? This action cannot be undone.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">Confirm Vote</button>
            </div>
        </div>
    </div>

    <script>
        function selectCandidate(id, element) {
            document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selectedCandidate').value = id;
            document.getElementById('submitBtn').disabled = false;
        }

        function showConfirmModal() {
            if (document.getElementById('selectedCandidate').value) {
                document.getElementById('confirmModal').style.display = 'flex';
            }
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        function submitForm() {
            document.getElementById('voteForm').submit();
        }
    </script>
</body>
</html>
