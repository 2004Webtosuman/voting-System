<?php
    $page_title = 'KYC Verification';
    require_once __DIR__ . '/header.php';

    global $conn;

    $success            = '';
    $errors             = [];
    $current_kyc_status = $user['kyc_status'];

    // Fetch uploaded documents
    $stmt = $conn->prepare("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY submission_date DESC");
    $stmt->execute([$user['id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map documents by type
    $docMap = [];
    foreach ($documents as $doc) {
        $docMap[$doc['document_type']] = $doc;
    }

    // Handle file uploads
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    // Check if document already exists
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

                    // Set KYC status to pending
                    $stmtStatus = $conn->prepare("UPDATE users SET kyc_status = 'pending' WHERE id = ?");
                    $stmtStatus->execute([$user['id']]);
                    $current_kyc_status = 'pending';

                    $success = "$docType uploaded successfully!";

                    // Refresh document map
                    $stmt = $conn->prepare("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY submission_date DESC");
                    $stmt->execute([$user['id']]);
                    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $docMap    = [];
                    foreach ($documents as $doc) {
                        $docMap[$doc['document_type']] = $doc;
                    }
                } else {
                    $errors[] = "$docType: Failed to upload.";
                }
            }
        }
    }
?>

<style>
.card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}
.document-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px dashed #ccc;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: border-color 0.3s, background 0.3s;
}
.document-preview.dragover {
    border-color: #4f46e5;
    background: rgba(79,70,229,0.05);
}
.document-preview input[type="file"] { display: none; }
.file-label {
    background: #4f46e5;
    color: #fff;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
}
.file-label:hover { background: #4338ca; }
.btn-view { background: #10b981; color:#fff; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.85rem; }
.not-uploaded { color:#999; font-size:0.85rem; font-style:italic; }
</style>

<div class="card">
<h2>Uploaded Documents</h2>

<?php if ($success) {
        echo "<div class='alert alert-success'>{$success}</div>";
    }
?>
<?php foreach ($errors as $error) {
        echo "<div class='alert alert-danger'>{$error}</div>";
    }
?>

<?php foreach (['id', 'citizenship_front', 'citizenship_back', 'certification'] as $docType): ?>
<div class="document-preview" id="preview-<?php echo $docType; ?>">
    <div style="flex:1">
        <strong><?php echo ucwords(str_replace('_', ' ', $docType)); ?></strong>
        <?php if (isset($docMap[$docType])): ?>
            <p>Uploaded:<?php echo date('M d, Y g:i A', strtotime($docMap[$docType]['submission_date'])); ?></p>
        <?php else: ?>
            <p class="not-uploaded">Not uploaded yet</p>
        <?php endif; ?>
    </div>

    <?php if (isset($docMap[$docType])): ?>
        <a href="<?php echo SITE_URL; ?>uploads/<?php echo $docMap[$docType]['file_path']; ?>" target="_blank" class="btn-view">View</a>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
        <input type="hidden" name="document_type" value="<?php echo $docType; ?>">
        <input type="file" id="file-<?php echo $docType; ?>" name="<?php echo $docType; ?>" accept=".jpg,.jpeg,.png,.pdf">
        <label for="file-<?php echo $docType; ?>" class="file-label">Choose / Drag</label>
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
