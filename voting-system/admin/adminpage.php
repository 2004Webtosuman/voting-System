<?php
require_once '../config/config.php';

// Check admin session
if (!isset($_SESSION['admin'])) {
    header("Location: adminloginpage.php");
    exit();
}

$admin_user = $_SESSION['admin'];

// Fetch Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM newaccountregistration");
$total_voters = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM newaccountregistration WHERE status = 'active'");
$active_voters = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM newaccountregistration WHERE status = 'pending'");
$pending_voters = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM newaccountregistration WHERE status = 'suspended'");
$suspended_voters = $stmt->fetchColumn();

// Pagination & Filtering
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(username LIKE :search OR email LIKE :search OR voters_id_number LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status_filter !== '') {
    $where_clauses[] = "status = :status";
    $params['status'] = $status_filter;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM newaccountregistration $where_sql");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch records
$query = "SELECT * FROM newaccountregistration $where_sql ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$voters = $stmt->fetchAll();

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: var(--card);
            border-bottom: 1px solid var(--border);
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-link:hover:not(.active) {
            background: var(--background);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="#" class="nav-brand">
            <i class="fas fa-user-shield" style="color: var(--primary);"></i>
            Admin Control Panel
        </a>
        <ul class="nav-links">
            <li><strong><?php echo htmlspecialchars($admin_user); ?></strong></li>
            <li><a href="ballot.php">Manage Ballot</a></li>
            <li><a href="../result.php">View Results</a></li>
            <li><a href="logoutadminpage.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <?php if ($msg === 'activated'): ?>
        <div style="background: #dcfce7; color: #166534; padding: 1rem; text-align: center;">User successfully activated.</div>
    <?php elseif ($msg === 'suspended'): ?>
        <div style="background: #fef9c3; color: #854d0e; padding: 1rem; text-align: center;">User successfully suspended.</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; text-align: center;">User successfully deleted.</div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3>Total Voters</h3>
                <p><?php echo $total_voters; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #166534; background: #dcfce7;"><i class="fas fa-user-check"></i></div>
            <div class="stat-info">
                <h3>Active Voters</h3>
                <p><?php echo $active_voters; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #854d0e; background: #fef9c3;"><i class="fas fa-user-clock"></i></div>
            <div class="stat-info">
                <h3>Pending Approval</h3>
                <p><?php echo $pending_voters; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color: #991b1b; background: #fee2e2;"><i class="fas fa-user-times"></i></div>
            <div class="stat-info">
                <h3>Suspended</h3>
                <p><?php echo $suspended_voters; ?></p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="filter-bar">
            <h2 style="margin: 0; font-size: 1.25rem;">Voter Management</h2>
            <form class="filter-form" method="GET" action="adminpage.php">
                <input type="text" name="search" class="form-control" placeholder="Search username, email, ID..." value="<?php echo htmlspecialchars($search); ?>" style="width: 250px; padding: 0.5rem;">
                <select name="status" class="form-control" style="width: 150px; padding: 0.5rem;">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
                <a href="adminpage.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Reset</a>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Voter ID</th>
                        <th>ID Proof</th>
                        <th>Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($voters) > 0): ?>
                        <?php foreach ($voters as $voter): ?>
                            <tr>
                                <td><?php echo $voter['id']; ?></td>
                                <td><?php echo htmlspecialchars($voter['username']); ?></td>
                                <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                <td><?php echo htmlspecialchars($voter['voters_id_number']); ?></td>
                                <td>
                                    <?php if (!empty($voter['voters_id_data'])): ?>
                                        <a href="view_id.php?id=<?php echo $voter['id']; ?>" target="_blank" style="color: var(--primary);"><i class="fas fa-file-image"></i> View File</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($voter['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($voter['status'])); ?>
                                    </span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if ($voter['status'] !== 'active'): ?>
                                        <a href="activateUser.php?id=<?php echo $voter['id']; ?>" class="action-btn" title="Activate" onclick="return confirm('Activate this user?');">
                                            <i class="fas fa-check" style="color: #166534;"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($voter['status'] !== 'suspended'): ?>
                                        <a href="suspendUser.php?id=<?php echo $voter['id']; ?>" class="action-btn" title="Suspend" onclick="return confirm('Suspend this user?');">
                                            <i class="fas fa-ban" style="color: #854d0e;"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="deleteUser.php?id=<?php echo $voter['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to completely delete this user? This cannot be undone.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No records found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
