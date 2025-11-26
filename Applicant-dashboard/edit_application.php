<?php
// Page-specific variables
$page_title = 'Edit Application';
$current_page = 'dashboard';

// Include Header
require_once __DIR__ . '/applicant_header.php';

$application = null;
$form_details = [];
$documents = [];
$message = '';

if (isset($_GET['id'])) {
    $applicationId = (int)$_GET['id'];

    // Fetch application details, ensuring it belongs to the current user for security
    $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $applicationId, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
        $form_details = json_decode($application['form_details'], true) ?? [];
    }
    $stmt->close();

    // Fetch existing documents for this application
    if ($application) {
        $docs_stmt = $conn->prepare("SELECT * FROM documents WHERE application_id = ?");
        $docs_stmt->bind_param("i", $applicationId);
        $docs_stmt->execute();
        $docs_result = $docs_stmt->get_result();
        $documents = $docs_result->fetch_all(MYSQLI_ASSOC);
        $docs_stmt->close();
    }
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    // This would ideally be in a separate processing file, but for simplicity, we handle it here.
    // A full implementation would be similar to `process_business_permit.php` but using UPDATE instead of INSERT.
    $message = '<div class="message success">Application updated successfully! (This is a placeholder - no data was actually changed).</div>';
}


// Include Sidebar
require_once __DIR__ . '/applicant_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <div class="form-container">
        <header class="header">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <a href="view_my_application.php?id=<?= $application['id'] ?? '' ?>" class="btn" style="padding: 8px 12px;"><i class="fas fa-arrow-left"></i> Back to View</a>
            </div>
            <h1>Edit Application #<?= htmlspecialchars($application['id'] ?? '') ?></h1>
            <p>Update your application details and upload any necessary documents below.</p>
        </header>

        <?php if (!$application): ?>
            <div class="message error">Application not found or you do not have permission to edit it.</div>
        <?php else: ?>
            <?php if ($message) echo $message; ?>

            <form method="POST" action="edit_application.php?id=<?= $application['id'] ?>" class="business-permit-form" enctype="multipart/form-data">
                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                
                <!-- Section I: APPLICANT SECTION -->
                <div class="form-section">
                    <h2>I. APPLICANT SECTION</h2>

                    <!-- 1. BASIC INFORMATION -->
                    <section>
                        <h3>1. BASIC INFORMATION</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Application Type:</label>
                                <div class="radio-options">
                                    <input type="radio" id="new" name="application_type" value="New" <?= ($form_details['application_type'] ?? '') === 'New' ? 'checked' : '' ?> required> 
                                    <label for="new">New</label>
                                    <input type="radio" id="renewal" name="application_type" value="Renewal" <?= ($form_details['application_type'] ?? '') === 'Renewal' ? 'checked' : '' ?>> 
                                    <label for="renewal">Renewal</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Mode of Payment:</label>
                                <div class="radio-options">
                                    <input type="radio" id="annually" name="mode_of_payment" value="Annually" <?= ($form_details['mode_of_payment'] ?? '') === 'Annually' ? 'checked' : '' ?> required> 
                                    <label for="annually">Annually</label>
                                    <input type="radio" id="semi-annually" name="mode_of_payment" value="Semi-Annually" <?= ($form_details['mode_of_payment'] ?? '') === 'Semi-Annually' ? 'checked' : '' ?>> 
                                    <label for="semi-annually">Semi-Annually</label>
                                    <input type="radio" id="quarterly" name="mode_of_payment" value="Quarterly" <?= ($form_details['mode_of_payment'] ?? '') === 'Quarterly' ? 'checked' : '' ?>> 
                                    <label for="quarterly">Quarterly</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_application">Date of Application:</label>
                                <input type="date" id="date_of_application" name="date_of_application" value="<?= htmlspecialchars($form_details['date_of_application'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="tin_no">TIN No.:</label>
                                <input type="text" id="tin_no" name="tin_no" value="<?= htmlspecialchars($form_details['tin_no'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="dti_reg_no">DTI/SCC/CDA Registration No.:</label>
                                <input type="text" id="dti_reg_no" name="dti_reg_no" value="<?= htmlspecialchars($form_details['dti_reg_no'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="dti_reg_date">DTI/SCC/CDA Date of Registration:</label>
                                <input type="date" id="dti_reg_date" name="dti_reg_date" value="<?= htmlspecialchars($form_details['dti_reg_date'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label for="business_name">Business Name:</label>
                                <input type="text" id="business_name" name="business_name" value="<?= htmlspecialchars($application['business_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="trade_name">Trade Name/Franchise:</label>
                                <input type="text" id="trade_name" name="trade_name" value="<?= htmlspecialchars($form_details['trade_name'] ?? '') ?>">
                            </div>
                        </div>
                    </section>

                    <!-- 2. OTHER INFORMATION -->
                    <section>
                        <h3>2. OTHER INFORMATION</h3>

                        <div class="form-row">
                            <div class="form-group" style="flex: 3;">
                                <label for="business_address">Business Address:</label>
                                <input type="text" id="business_address" name="business_address" value="<?= htmlspecialchars($application['business_address'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="b_postal_code">Postal Code:</label>
                                <input type="text" id="b_postal_code" name="b_postal_code" value="<?= htmlspecialchars($form_details['b_postal_code'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="b_email">Business Email Address:</label>
                                <input type="email" id="b_email" name="b_email" value="<?= htmlspecialchars($form_details['b_email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="b_mobile">Business Mobile No.:</label>
                                <input type="text" id="b_mobile" name="b_mobile" value="<?= htmlspecialchars($form_details['b_mobile'] ?? '') ?>">
                            </div>
                        </div>

                        <h4>Taxpayer/Registrant Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="last_name">Last Name:</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($form_details['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name:</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($form_details['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="middle_name">Middle Name:</label>
                                <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($form_details['middle_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 3;">
                                <label for="owner_address">Owner's Address:</label>
                                <input type="text" id="owner_address" name="owner_address" value="<?= htmlspecialchars($form_details['owner_address'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="o_postal_code">Postal Code:</label>
                                <input type="text" id="o_postal_code" name="o_postal_code" value="<?= htmlspecialchars($form_details['o_postal_code'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="o_email">Owner's Email Address:</label>
                                <input type="email" id="o_email" name="o_email" value="<?= htmlspecialchars($form_details['o_email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="o_mobile">Owner's Mobile No.:</label>
                                <input type="text" id="o_mobile" name="o_mobile" value="<?= htmlspecialchars($form_details['o_mobile'] ?? '') ?>">
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Section II: UPLOADED DOCUMENTS -->
                <div class="form-section">
                    <h2>II. UPLOADED DOCUMENTS</h2>
                    <p class="notes">Review your currently uploaded documents. You can upload new files to replace existing ones or to add missing ones.</p>
                    
                    <div class="document-list">
                        <?php if (empty($documents)): ?>
                            <p>No documents have been uploaded for this application yet.</p>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <div class="document-item">
                                    <div class="doc-preview">
                                        <?php
                                        $file_extension = strtolower(pathinfo($doc['document_name'], PATHINFO_EXTENSION));
                                        $file_path = '../uploads/' . htmlspecialchars($doc['file_path']);
                                        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])):
                                        ?>
                                            <img src="<?= $file_path ?>" alt="<?= htmlspecialchars($doc['document_name']) ?>">
                                        <?php elseif ($file_extension === 'pdf'): ?>
                                            <i class="fas fa-file-pdf"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p title="<?= htmlspecialchars($doc['document_name']) ?>"><?= htmlspecialchars($doc['document_name']) ?></p>
                                    <a href="<?= $file_path ?>" class="btn btn-secondary" target="_blank" style="padding: 8px 16px; font-size: 0.9rem;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <h3 style="margin-top: 30px;">Upload New or Replacement Documents</h3>
                    <div class="document-upload-section">
                        <div class="document-item-upload">
                            <label for="dti_registration">DTI Registration Certificate:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="dti_registration" name="documents[dti_registration]" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="dti_registration" class="file-label"><i class="fas fa-upload"></i> Choose File</label>
                                <span class="file-name">No new file selected</span>
                            </div>
                        </div>

                        <div class="document-item-upload">
                            <label for="bir_registration">BIR Registration Certificate:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="bir_registration" name="documents[bir_registration]" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="bir_registration" class="file-label"><i class="fas fa-upload"></i> Choose File</label>
                                <span class="file-name">No new file selected</span>
                            </div>
                        </div>

                        <div class="document-item-upload">
                            <label for="barangay_clearance">Barangay Clearance:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="barangay_clearance" name="documents[barangay_clearance]" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="barangay_clearance" class="file-label"><i class="fas fa-upload"></i> Choose File</label>
                                <span class="file-name">No new file selected</span>
                            </div>
                        </div>

                        <div class="document-item-upload">
                            <label for="fire_safety_certificate">Fire Safety Certificate:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="fire_safety_certificate" name="documents[fire_safety_certificate]" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="fire_safety_certificate" class="file-label"><i class="fas fa-upload"></i> Choose File</label>
                                <span class="file-name">No new file selected</span>
                            </div>
                        </div>

                        <div class="document-item-upload">
                            <label for="sanitary_permit">Sanitary Permit:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="sanitary_permit" name="documents[sanitary_permit]" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="sanitary_permit" class="file-label"><i class="fas fa-upload"></i> Choose File</label>
                                <span class="file-name">No new file selected</span>
                            </div>
                        </div>

                        <div class="document-item-upload">
                            <label for="other_documents">Other Supporting Documents:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="other_documents" name="documents[other_documents]" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="other_documents" class="file-label"><i class="fas fa-upload"></i> Choose File</label>
                                <span class="file-name">No new file selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section III: PAYMENT RECEIPT UPLOAD -->
                <div class="form-section">
                    <h2>III. PAYMENT RECEIPT UPLOAD</h2>
                    <p class="notes">If your application has been assessed and you have paid the required fees, please upload a clear copy of your official receipt here. This will notify the staff to proceed with your application.</p>
                    
                    <?php
                    // Check if a payment receipt already exists
                    $receipt_exists = false;
                    $receipt_path = '';
                    foreach ($documents as $doc) {
                        if (strpos(strtolower($doc['document_name']), 'payment_receipt') !== false) {
                            $receipt_exists = true;
                            $receipt_path = '../uploads/' . htmlspecialchars($doc['file_path']);
                            break;
                        }
                    }
                    ?>

                    <?php if ($receipt_exists): ?>
                    <div class="message info">
                        <i class="fas fa-receipt"></i>
                        <div>
                            <h4>Payment Receipt Already Uploaded</h4>
                            <p>A payment receipt has already been submitted for this application. You can view it <a href="<?= $receipt_path ?>" target="_blank">here</a>. Uploading a new file will replace the existing one.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="document-upload-section">
                        <div class="document-item-upload">
                            <label for="payment_receipt">Official Receipt:</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="payment_receipt" name="payment_receipt" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="payment_receipt" class="file-label">
                                    <i class="fas fa-upload"></i> Choose Receipt File
                                </label>
                                <span class="file-name">No file selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_application" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Using styles from submit_application.php and view_my_application.php for consistency */
    .form-container {
        max-width: 1100px;
        margin: auto;
        background: #fff;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    .header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
    }
    .header p {
        color: #64748b;
        margin-top: 5px;
    }
    .form-section {
        border: 1px solid #e2e8f0;
        padding: 25px;
        margin-bottom: 25px;
        border-radius: 12px;
        background: #f8fafc;
    }
    .form-section h2 {
        color: #4a69bd;
        border-bottom: 2px solid #4a69bd;
        padding-bottom: 10px;
        margin-bottom: 25px;
        font-size: 1.4rem;
    }
    .form-section h3 {
        color: #334155;
        margin-top: 20px;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 15px;
    }
    .form-group {
        flex: 1;
        min-width: 250px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #475569;
    }
    .business-permit-form input[type="text"],
    .business-permit-form input[type="email"],
    .business-permit-form input[type="date"],
    .business-permit-form input[type="number"],
    .business-permit-form textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .business-permit-form input:focus {
        border-color: #4a69bd;
        outline: none;
        box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.15);
    }
    .radio-options {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .radio-options label {
        font-weight: normal;
        margin-bottom: 0;
    }
    .notes {
        font-style: italic;
        color: #64748b;
        background: #f1f5f9;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    /* Document List Styles (from view_application.php) */
    .document-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    .document-item { border: 1px solid var(--border-color); border-radius: var(--border-radius); text-align: center; padding: 15px; transition: all 0.2s ease; background: #fff; }
    .document-item:hover { box-shadow: var(--shadow); transform: translateY(-3px); }
    .document-item .doc-preview { height: 120px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 8px; margin-bottom: 10px; }
    .document-item .doc-preview img { max-height: 100%; max-width: 100%; object-fit: cover; border-radius: 4px; }
    .document-item .doc-preview i { font-size: 3rem; color: var(--text-secondary-color); }
    .document-item p { font-weight: 600; margin-bottom: 10px; word-break: break-word; }

    /* Document Upload Section Styles (from submit_application.php) */
    .document-upload-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }
    .document-item-upload {
        padding: 15px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .document-item-upload label {
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
        display: block;
    }
    .file-upload-wrapper { display: flex; align-items: center; gap: 10px; }
    .file-input { position: absolute; width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; z-index: -1; }
    .file-label { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: #fff; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background-color 0.2s ease; margin: 0; }
    .file-label:hover { background-color: #5a6268; }
    .file-label i { margin-right: 8px; }
    .file-name { flex-grow: 1; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; background-color: #fff; color: #5a6a7b; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Message Box Styles */
    .message { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid transparent; display: flex; align-items: center; gap: 15px; }
    .message i { font-size: 1.5rem; }
    .message h4 { margin: 0 0 5px; font-size: 1.1rem; }
    .message p { margin: 0; line-height: 1.5; }
    .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    .message.info { background-color: #e3f2fd; color: #0d6efd; border-color: #b6d4fe; }

    .form-actions {
        text-align: right;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-primary {
        background: #28a745;
        color: #fff;
    }
    .btn-primary:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    .btn-secondary {
        background: #6c757d;
        color: #fff;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File input name display logic
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : 'No file selected';
            // Find the sibling .file-name span to update its text
            this.closest('.file-upload-wrapper').querySelector('.file-name').textContent = fileName;
        });
    });
});
</script>

<?php
// Include Footer
require_once __DIR__ . '/applicant_footer.php';
?>