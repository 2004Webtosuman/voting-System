<?php
$page_title = 'KYC Verification';
require_once __DIR__ . '/header.php';

global $conn;

$success = '';
$errors = [];
$current_kyc_status = $user['kyc_status'];

// Get uploaded documents
$stmt = $conn->prepare("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY submission_date DESC");
$stmt->execute([$user['id']]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
  $document_type = $_POST['document_type'] ?? '';
  
  if (!in_array($document_type, ['id', 'citizenship'])) {
    $errors[] = 'Invalid document type';
  } else {
    $upload_result = uploadKYCDocument($user['id'], $document_type);
    
    if ($upload_result['success']) {
      try {
        // Insert document record
        $stmt = $conn->prepare("
          INSERT INTO kyc_documents (user_id, document_type, file_path)
          VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $document_type, $upload_result['filename']]);
        
        // Update user KYC status to pending if not already verified
        if ($current_kyc_status !== 'verified') {
          $stmt = $conn->prepare("UPDATE users SET kyc_status = 'pending' WHERE id = ?");
          $stmt->execute([$user['id']]);
          $current_kyc_status = 'pending';
        }
        
        $success = 'Document uploaded successfully! Your KYC is under review.';
      } catch (PDOException $e) {
        $errors[] = 'Error saving document: ' . $e->getMessage();
      }
    } else {
      $errors[] = $upload_result['message'];
    }
  }
}
?>

<style>
  .kyc-status {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid;
  }
  
  .kyc-status.verified {
    border-left-color: var(--success);
    background: rgba(16, 185, 129, 0.05);
  }
  
  .kyc-status.pending {
    border-left-color: var(--warning);
    background: rgba(245, 158, 11, 0.05);
  }
  
  .kyc-status.rejected {
    border-left-color: var(--danger);
    background: rgba(239, 68, 68, 0.05);
  }
  
  .status-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
  }
  
  .document-preview {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    border: 1px solid var(--border);
  }
  
  .document-icon {
    font-size: 2rem;
  }
</style>

<div class="kyc-status <?php echo $current_kyc_status; ?>">
  <div style="display: flex; gap: 1rem; align-items: start;">
    <div class="status-icon">
      <?php
      if ($current_kyc_status === 'verified') {
        echo '✅';
      } elseif ($current_kyc_status === 'pending') {
        echo '⏳';
      } else {
        echo '❌';
      }
      ?>
    </div>
    <div>
      <h2 style="margin: 0 0 0.5rem 0;">
        KYC Status: <strong><?php echo ucfirst($current_kyc_status); ?></strong>
      </h2>
      <p style="margin: 0; color: #666;">
        <?php
        if ($current_kyc_status === 'verified') {
          echo 'Your identity has been verified. You can now use all features.';
        } elseif ($current_kyc_status === 'pending') {
          echo 'Your documents are under review. We will notify you once the verification is complete.';
        } else {
          echo 'Please upload your documents to verify your identity.';
        }
        ?>
      </p>
      <?php if ($current_kyc_status === 'rejected'): ?>
        <p style="margin: 0.5rem 0 0 0; color: var(--danger);">
          <strong>Reason:</strong> <?php echo htmlspecialchars($user['kyc_rejection_reason'] ?? 'No reason provided'); ?>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($errors): ?>
  <?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="card">
  <h2>Uploaded Documents</h2>
  
  <?php if ($documents): ?>
    <?php foreach ($documents as $doc): ?>
      <div class="document-preview">
        <div class="document-icon">📄</div>
        <div style="flex: 1;">
          <strong><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></strong>
          <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
            Uploaded: <?php echo date('M d, Y g:i A', strtotime($doc['submission_date'])); ?>
          </p>
        </div>
        <a href="<?php echo SITE_URL; ?>uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary" style="padding: 0.5rem 1rem;">View</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No documents uploaded yet.</p>
  <?php endif; ?>
</div>

<?php if ($current_kyc_status !== 'verified'): ?>
  <div class="card">
    <h2>Upload Documents for Verification</h2>
    
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Document Type</label>
        <select name="document_type" required>
          <option value="">Select a document type</option>
          <option value="id">Government ID (Passport, Driver's License, etc.)</option>
          <option value="citizenship">Proof of Citizenship/Residency</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Upload Document (JPG, PNG, PDF)</label>
        <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required>
        <small style="color: #666;">Maximum file size: 5MB</small>
      </div>
      
      <button type="submit" class="btn btn-primary">Upload Document</button>
    </form>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
