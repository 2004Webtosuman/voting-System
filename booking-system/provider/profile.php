<?php
    $page_title = 'Profile & Services';
    require_once __DIR__ . '/header.php';

    global $conn;

    $success = '';
    $errors  = [];

    // Get provider info
    $provider = getProviderInfo($user['id']);

    // ----------------------------
    // Handle KYC Upload
    // ----------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_type'])) {
        $docType = $_POST['document_type'] ?? '';
        if (! $docType) {
            $errors[] = "Document type missing.";
        } elseif (! isset($_FILES[$docType]) || $_FILES[$docType]['error'] !== 0) {
            $errors[] = "$docType: No file selected or upload error.";
        } else {
            $file    = $_FILES[$docType];
            $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

            if (! in_array(strtolower($ext), $allowed)) {
                $errors[] = "$docType: Invalid file type.";
            } else {
                $newName   = $docType . '_' . $user['id'] . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/uploads/';
                if (! is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $dest = $uploadDir . $newName;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $stmtCheck = $conn->prepare("SELECT id FROM kyc_documents WHERE user_id = ? AND document_type = ?");
                    $stmtCheck->execute([$user['id'], $docType]);
                    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $stmtUpdate = $conn->prepare("UPDATE kyc_documents SET file_path = ?, submission_date = NOW() WHERE id = ?");
                        $stmtUpdate->execute([$newName, $existing['id']]);
                    } else {
                        $stmtInsert = $conn->prepare("INSERT INTO kyc_documents (user_id, document_type, file_path) VALUES (?,?,?)");
                        $stmtInsert->execute([$user['id'], $docType, $newName]);
                    }

                    $stmtStatus = $conn->prepare("UPDATE users SET kyc_status = 'pending' WHERE id = ?");
                    $stmtStatus->execute([$user['id']]);
                    $success = "$docType uploaded successfully!";
                } else {
                    $errors[] = "$docType: Failed to upload.";
                }
            }
        }
    }

    // ----------------------------
    // Handle Profile Update
    // ----------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! isset($_POST['delete_doc_id']) && ! isset($_POST['document_type'])) {
        $service_type     = $_POST['service_type'] ?? 'plumber';
        $bio              = trim($_POST['bio'] ?? '');
        $hourly_rate      = $_POST['hourly_rate'] ?? 0;
        $years_experience = $_POST['years_experience'] ?? 0;
        $phone            = $_POST['phone'] ?? $user['phone'];

        try {
            $stmt = $conn->prepare("
            UPDATE providers
            SET service_type = ?, bio = ?, hourly_rate = ?, years_experience = ?
            WHERE user_id = ?
        ");
            $stmtPhone = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");

            if ($stmt->execute([$service_type, $bio, $hourly_rate, $years_experience, $user['id']])
                && $stmtPhone->execute([$phone, $user['id']])) {
                $success  = 'Profile updated successfully!';
                $provider = getProviderInfo($user['id']);
            } else {
                $errors[] = 'Failed to update profile';
            }
        } catch (PDOException $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }

    // ----------------------------
    // Handle KYC Delete
    // ----------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc_id'])) {
        $docId = intval($_POST['delete_doc_id']);

        $stmt = $conn->prepare("SELECT file_path FROM kyc_documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$docId, $user['id']]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doc) {
            $filePath = __DIR__ . '/uploads/' . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $stmtDelete = $conn->prepare("DELETE FROM kyc_documents WHERE id = ? AND user_id = ?");
            $stmtDelete->execute([$docId, $user['id']]);
            $success = 'Document deleted successfully!';
        }
    }

    // ----------------------------
    // Fetch KYC Documents
    // ----------------------------
    $stmt = $conn->prepare("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY submission_date DESC");
    $stmt->execute([$user['id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $docMap    = [];
    foreach ($documents as $doc) {
        $docMap[$doc['document_type']] = $doc;
    }

?>

<div class="card">
  <h1>Your Profile</h1>

  <?php if ($errors): foreach ($errors as $error): ?>
		      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
		  <?php endforeach;endif; ?>

  <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
    </div>

    <div class="form-group">
      <label>Phone</label>
      <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
    </div>

    <div class="form-group">
      <label>Service Type</label>
      <select name="service_type" required>
        <option value="plumber"                                                               <?php echo $provider['service_type'] === 'plumber' ? 'selected' : ''; ?>>Plumber</option>
        <option value="electrician"                                                                       <?php echo $provider['service_type'] === 'electrician' ? 'selected' : ''; ?>>Electrician</option>
      </select>
    </div>

    <div class="form-group">
      <label>About You</label>
      <textarea name="bio" placeholder="Tell customers about your experience..."><?php echo htmlspecialchars($provider['bio'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
      <label>Hourly Rate ($)</label>
      <input type="number" name="hourly_rate" step="0.01" value="<?php echo $provider['hourly_rate'] ?? 50; ?>" required>
    </div>

    <div class="form-group">
      <label>Years of Experience</label>
      <input type="number" name="years_experience" value="<?php echo $provider['years_experience'] ?? 0; ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Update Profile</button>
  </form>
</div>

<!-- KYC Upload & Delete Cards -->
<div class="card">
  <h2>Uploaded KYC Documents</h2>
  <?php foreach (['id', 'citizenship_front', 'citizenship_back', 'certification'] as $docType): ?>
  <div class="document-preview" id="preview-<?php echo $docType; ?>" style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;padding:0.75rem;border-radius:8px;border:1px solid #ccc;">
    <div style="flex:1">
      <strong><?php echo ucwords(str_replace('_', ' ', $docType)); ?></strong>
      <?php if (isset($docMap[$docType])): ?>
          <p>Uploaded:<?php echo date('M d, Y g:i A', strtotime($docMap[$docType]['submission_date'])); ?></p>
      <?php else: ?>
          <p class="not-uploaded">Not uploaded yet</p>
      <?php endif; ?>
    </div>

    <?php if (isset($docMap[$docType])): ?>
        <a href="<?php echo SITE_URL; ?>uploads/<?php echo $docMap[$docType]['file_path']; ?>" target="_blank" class="btn btn-primary" style="padding:0.4rem 0.8rem;background:#10b981;color:#fff;border-radius:6px;text-decoration:none;">View</a>
        <form method="POST" style="display:inline-block;">
            <input type="hidden" name="delete_doc_id" value="<?php echo $docMap[$docType]['id']; ?>">
            <button type="submit" style="padding:0.4rem 0.8rem;background:#ef4444;color:#fff;border:none;border-radius:6px;cursor:pointer;">Delete</button>
        </form>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
        <input type="hidden" name="document_type" value="<?php echo $docType; ?>">
        <input type="file" id="file-<?php echo $docType; ?>" name="<?php echo $docType; ?>" accept=".jpg,.jpeg,.png,.pdf">
        <label for="file-<?php echo $docType; ?>" class="file-label" style="padding:0.4rem 0.8rem;background:#4f46e5;color:#fff;border-radius:6px;cursor:pointer;">Choose / Drag</label>
        <button type="submit" class="file-label" style="background:#f59e0b;">Upload</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<script>
['id','citizenship_front','citizenship_back','certification'].forEach(type=>{
    const preview = document.getElementById('preview-'+type);
    const input = document.getElementById('file-'+type);

    preview.addEventListener('dragover', e=>{
        e.preventDefault();
        preview.classList.add('dragover');
    });
    preview.addEventListener('dragleave', e=>{
        preview.classList.remove('dragover');
    });
    preview.addEventListener('drop', e=>{
        e.preventDefault();
        preview.classList.remove('dragover');
        if(e.dataTransfer.files.length > 0){
            input.files = e.dataTransfer.files;
        }
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
