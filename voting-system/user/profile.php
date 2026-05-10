<?php
require_once '../config/config.php';

// Ensure a user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: loginpage.php");
    exit();
}

$username = $_SESSION['username'];
$msg = "";
$msg_type = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    validate_csrf_token();
    
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $voters_id_number = trim($_POST['voters_id_number']);
    
    // File upload logic (Optional)
    $update_file_sql = "";
    $params = [
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'vid' => $voters_id_number,
        'uname' => $username
    ];

    $file_error = false;
    if (isset($_FILES['voters_id']) && $_FILES['voters_id']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['voters_id']['tmp_name'];
        $fileName = $_FILES['voters_id']['name'];
        $fileSize = $_FILES['voters_id']['size'];
        $fileType = $_FILES['voters_id']['type'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $msg = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
            $msg_type = "error";
            $file_error = true;
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $msg = "File size exceeds 2MB limit.";
            $msg_type = "error";
            $file_error = true;
        } else {
            $fileContent = file_get_contents($fileTmpPath);
            if ($fileContent !== false) {
                $params['vdata'] = encrypt_data($fileContent);
                $params['vmime'] = $fileType;
                $update_file_sql = ", voters_id_data = :vdata, voters_id_mime = :vmime";
            }
        }
    }

    if (!$file_error) {
        // Update user and set status to pending for admin re-verification
        $sql = "UPDATE newaccountregistration 
                SET email = :email, phone = :phone, address = :address, voters_id_number = :vid, status = 'pending' $update_file_sql 
                WHERE username = :uname";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            $msg = "Profile updated successfully! Your account is now pending admin verification.";
            $msg_type = "success";
        } else {
            $msg = "Failed to update profile.";
            $msg_type = "error";
        }
    }
}

// Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM newaccountregistration WHERE username = :uname");
$stmt->execute(['uname' => $username]);
$user = $stmt->fetch();

if (!$user) {
    render_error_page("User profile not found.");
}

// Fetch Voting Record (if any)
$stmt = $pdo->prepare("SELECT voted_at FROM voting_system WHERE username = :uname");
$stmt->execute(['uname' => $username]);
$vote_record = $stmt->fetch();
$has_voted = ($vote_record !== false);

$is_edit_mode = isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 3rem auto;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            padding: 0 1rem;
        }
        
        .profile-sidebar {
            background: var(--card);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary);
            margin: 0 auto 1rem;
        }
        
        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .info-card {
            background: var(--card);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .info-item label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .info-item div {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .receipt-card {
            background: linear-gradient(135deg, #166534 0%, #14532d 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .receipt-icon {
            font-size: 4rem;
            color: #dcfce7;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="voting.php" class="nav-brand">
            <img src="../photo/logo1.png" alt="Logo">
            VotingSystem
        </a>
        <ul class="nav-links">
            <li><a href="voting.php">Voting Booth</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="profile-container">
        
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;"><?php echo htmlspecialchars($user['email']); ?></p>
            
            <div style="margin-bottom: 1.5rem;">
                <span style="font-size: 0.875rem; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Account Status</span>
                <span class="badge badge-<?php echo htmlspecialchars($user['status']); ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                    <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                </span>
            </div>
            
            <?php if ($user['status'] === 'pending'): ?>
                <p style="font-size: 0.875rem; color: #854d0e; background: #fef9c3; padding: 0.75rem; border-radius: 8px;">Your account is awaiting admin verification. You cannot vote until verified.</p>
            <?php endif; ?>

            <?php if (!$is_edit_mode): ?>
                <a href="profile.php?edit=1" class="btn btn-outline" style="width: 100%; margin-top: 1rem;"><i class="fas fa-edit"></i> Edit Profile</a>
            <?php else: ?>
                <a href="profile.php" class="btn btn-outline" style="width: 100%; margin-top: 1rem;"><i class="fas fa-times"></i> Cancel Edit</a>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="profile-main">
            
            <?php if ($msg): ?>
                <div style="padding: 1rem; border-radius: 8px; background: <?php echo $msg_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $msg_type === 'success' ? '#166534' : '#991b1b'; ?>;">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <?php if ($has_voted && !$is_edit_mode): ?>
                <div class="receipt-card">
                    <i class="fas fa-certificate receipt-icon"></i>
                    <div>
                        <h2 style="color: white; margin-bottom: 0.5rem;">Vote Successfully Cast</h2>
                        <p style="opacity: 0.9;">Your ballot was securely recorded in the system.</p>
                        <p style="margin-top: 1rem; font-family: monospace; font-size: 0.9rem; opacity: 0.8;">
                            Timestamp: <?php echo htmlspecialchars($vote_record['voted_at']); ?><br>
                            Receipt ID: <?php echo hash('sha256', $user['username'] . $vote_record['voted_at']); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="info-card">
                <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 0;">
                    <?php echo $is_edit_mode ? 'Edit Profile Details' : 'Registration Details'; ?>
                </h3>
                
                <?php if ($is_edit_mode): ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="info-grid">
                            <div class="form-group info-item">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group info-item">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required pattern="[0-9]{10}">
                            </div>

                            <div class="form-group info-item" style="grid-column: 1 / -1;">
                                <label>Registered Address</label>
                                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            
                            <div class="form-group info-item">
                                <label>Voter ID Number</label>
                                <input type="text" name="voters_id_number" class="form-control" value="<?php echo htmlspecialchars($user['voters_id_number']); ?>" required>
                            </div>
                            
                            <div class="form-group info-item">
                                <label>Update Voter ID Proof (Optional)</label>
                                <input type="file" name="voters_id" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small style="color: var(--text-secondary);">Leave blank to keep your current ID document.</small>
                            </div>
                            
                            <div style="grid-column: 1 / -1; margin-top: 1rem;">
                                <div style="background: #fef9c3; color: #854d0e; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> Updating your details will require an admin to re-verify your account. You will not be able to vote while your status is pending.
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">Save Changes & Submit for Verification</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Voter ID Number</label>
                            <div><?php echo htmlspecialchars($user['voters_id_number']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>Date of Birth</label>
                            <div><?php echo htmlspecialchars($user['date_of_birth']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>Gender</label>
                            <div style="text-transform: capitalize;"><?php echo htmlspecialchars($user['gender']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <label>Phone Number</label>
                            <div><?php echo htmlspecialchars($user['phone']); ?></div>
                        </div>
                        
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <label>Registered Address</label>
                            <div><?php echo nl2br(htmlspecialchars($user['address'])); ?></div>
                        </div>
                        
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <label>Voter ID Proof</label>
                            <?php if (!empty($user['voters_id_data'])): ?>
                                <a href="view_my_id.php" target="_blank" style="color: var(--primary); text-decoration: none;"><i class="fas fa-file-pdf"></i> View Uploaded Document</a>
                            <?php else: ?>
                                <div style="color: var(--text-secondary);">No document uploaded.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>

</body>
</html>
