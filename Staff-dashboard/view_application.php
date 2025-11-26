<?php
$current_page = 'applicants'; // Keep 'applicants' active in the sidebar
$page_title = 'View Application';
require_once './staff_header.php'; // Handles session, DB, auth, and starts HTML
require_once './email_functions.php'; // For any email functions needed

// --- Main Logic ---
$application = null;
$message = '';
$email_message = '';
$applicationId = $_GET['id'] ?? 0;
$lgu_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    $applicationId = (int)$_POST['application_id'];
    
    $conn->begin_transaction();
    try {
        // The staff should only update the LGU form data, not the applicant's data.
        // We also need to update the `updated_at` timestamp on the main application to track activity.
        $updateTimestampStmt = $conn->prepare("UPDATE applications SET updated_at = NOW() WHERE id = ?");
        $updateTimestampStmt->bind_param("i", $applicationId);
        $updateTimestampStmt->execute();
        $updateTimestampStmt->close();

            // Now, handle the LGU Section data
            $lgu_form_data = [
                'verification' => $_POST['verification'] ?? [],
                'fees' => $_POST['fees'] ?? []
            ];
            $lgu_form_data_json = json_encode($lgu_form_data);

            $lgu_stmt = $conn->prepare("REPLACE INTO staff_form_data (application_id, form_data) VALUES (?, ?)");
            $lgu_stmt->bind_param("is", $applicationId, $lgu_form_data_json);
            $lgu_stmt->execute();
            $lgu_stmt->close();

            $conn->commit();
            $message = '<div class="message success">LGU data updated successfully!</div>';
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="message error">Failed to update data: ' . $e->getMessage() . '</div>';
        }

        header("Location: view_application.php?id=" . $applicationId . "&status=updated");
        exit;
    }

if ($applicationId > 0) {
    $stmt = $conn->prepare("SELECT a.*, u.name as applicant_name, u.email as applicant_email FROM applications a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$application) {
    echo 'Application not found.';
    exit;
}

$form_details = json_decode($application['form_details'], true) ?? [];


// Fetch LGU/staff form data
$staff_form_data = [];
$stmt = $conn->prepare("SELECT form_data FROM staff_form_data WHERE application_id = ?");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $staff_form_data = json_decode($row['form_data'], true) ?? [];
}
$stmt->close();

// Display a success message if the URL contains the status parameter
if (isset($_GET['status']) && $_GET['status'] === 'updated') {
    $message = '<div class="message success">LGU Form data updated successfully!</div>';
}
?>
<?php require_once './staff_sidebar.php'; // Include the shared sidebar ?>
  <style>
    :root {
        --card-bg-color: #ffffff;
        --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    /* Main Content */
    .main { flex: 1; padding: 30px; overflow-y: auto; transition: margin-left 0.3s ease; }
    .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .main-header h1 { font-size: 28px; font-weight: 700; color: var(--secondary-color); }

    .tab-nav { display: flex; border-bottom: 2px solid var(--border-color); margin-bottom: 20px; }

    .tab-link { padding: 10px 20px; cursor: pointer; background: transparent; border: none; font-size: 1rem; font-weight: 600; color: var(--text-secondary-color); position: relative; }
    .tab-link.active { color: var(--primary-color); }
    .tab-link.active::after { content: ''; position: absolute; bottom: -2px; left: 0; right: 0; height: 3px; background: var(--primary-color); border-radius: 3px 3px 0 0; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .details-card, .form-container { background: var(--card-bg-color); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow); }
    .details-card h3, .form-container h2 { font-size: 1.5rem; font-weight: 600; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-item label { display: block; font-weight: 600; color: var(--text-secondary-color); margin-bottom: 5px; }
    .info-item p { font-size: 1.1rem; }
    .info-item .badge { padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; text-align: center; display: inline-block; }
    .info-item .badge-yes { background: rgba(40, 167, 69, 0.1); color: #28a745; }
    .section-divider { margin-top: 30px; padding-top: 25px; border-top: 1px solid var(--border-color); }

    table { width: 100%; border-collapse: collapse; margin-top: 15px; }

    th, td { border: 1px solid #444; padding: 6px; text-align: center; }
    th { background: #f2f2f2; }
    input, textarea, select { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
    select {appearance:none;}


    .form-actions { display: flex; gap: 15px; justify-content: flex-start; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; }
    .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.3s ease; }
    .btn-primary { background: #4a69bd; color: #fff; }
    .btn-secondary { background: #6c757d; color: #fff; }
    .btn-release { background: linear-gradient(45deg, #28a745, #218838); color: #fff; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
    .btn-release:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); }

    .status-badge { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; color: #fff; text-transform: capitalize; }
    .status-approved, .status-complete { background-color: #28a745; }
    .status-pending, .status-in-review { background-color: #ffc107; color: #333; }
    .status-rejected { background-color: #dc3545; }

    /* Styles for the full edit form */
    .business-permit-form .form-section { border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
    .business-permit-form .form-section h2 { font-size: 1.2rem; color: var(--primary-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 8px; margin-bottom: 20px; }
    .business-permit-form .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 15px; }
    .business-permit-form .form-group { flex: 1; min-width: 250px; }
    .business-permit-form .form-group label { font-weight: 600; color: var(--text-secondary-color); margin-bottom: 5px; display: block; }
    .business-permit-form input[type="text"], .business-permit-form input[type="email"], .business-permit-form input[type="date"], .business-permit-form input[type="number"], .business-permit-form textarea, .business-permit-form select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; }
    .business-permit-form .radio-options { display: flex; gap: 15px; }
    .business-permit-form .radio-options label { font-weight: normal; }
    .message { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; border: 1px solid transparent; }
    .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

  </style>

    <div class="main">
        <div class="main-header">

            <h1>Application #<?= $application['id'] ?></h1>
        </div>

        <?= $message ?>
        <div class="tab-nav">
            <button class="tab-link active" data-tab="tab-1">View Application</button>
            <button class="tab-link" data-tab="tab-2">LGU Form Section</button>
        </div>


        <div id="tab-1" class="tab-content active">
            <div class="details-card">
                <?= $email_message ?>

                <h3>Applicant Information</h3>
                <div class="info-grid">

                    <div class="info-item"><label>Applicant Name</label><p><?= htmlspecialchars($application['applicant_name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Email Address</label><p><?= htmlspecialchars($application['applicant_email'] ?? 'N/A') ?></p></div>

                </div>

                <h3 class="section-divider">Business Information</h3>
                <div class="info-grid">
                    <div class="info-item"><label>Business Name</label><p><?= htmlspecialchars($application['business_name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Trade Name/Franchise</label><p><?= htmlspecialchars($form_details['trade_name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Business Address</label><p><?= htmlspecialchars($application['business_address'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Business Email</label><p><?= htmlspecialchars($form_details['b_email'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Business Mobile</label><p><?= htmlspecialchars($form_details['b_mobile'] ?? 'N/A') ?></p></div>
                </div>

                <h3 class="section-divider">Owner Information</h3>
                <div class="info-grid">
                    <div class="info-item"><label>Last Name</label><p><?= htmlspecialchars($form_details['last_name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>First Name</label><p><?= htmlspecialchars($form_details['first_name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Middle Name</label><p><?= htmlspecialchars($form_details['middle_name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Owner's Address</label><p><?= htmlspecialchars($form_details['owner_address'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Owner's Email</label><p><?= htmlspecialchars($form_details['o_email'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Owner's Mobile</label><p><?= htmlspecialchars($form_details['o_mobile'] ?? 'N/A') ?></p></div>
                </div>

                <h3 class="section-divider">Other Details</h3>
                <div class="info-grid">
                    <div class="info-item"><label>Application Type</label><p><?= htmlspecialchars($form_details['application_type'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Mode of Payment</label><p><?= htmlspecialchars($form_details['mode_of_payment'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>TIN Number</label><p><?= htmlspecialchars($form_details['tin_no'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>DTI Reg. No.</label><p><?= htmlspecialchars($form_details['dti_reg_no'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Business Area (sq m)</label><p><?= htmlspecialchars($form_details['business_area'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Total Employees</label><p><?= htmlspecialchars($form_details['total_employees'] ?? 'N/A') ?></p></div>
                </div>

                <h3 class="section-divider">Application Actions</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Submitted On</label>
                        <p><?= date('M d, Y, h:i A', strtotime($application['submitted_at'])) ?></p>
                    </div>
                    <?php if ($application['status'] === 'approved' || $application['status'] === 'complete'): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <label>Actions</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <a href="generate_permit.php?id=<?= $applicationId ?>" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-print"></i> View/Print Permit
                            </a>
                            <a href="release_permit.php?id=<?= $applicationId ?>" class="btn btn-release" onclick="return confirm('Are you sure you want to release this permit and notify the applicant? This action cannot be undone.');">
                                <i class="fas fa-paper-plane"></i> Release & Notify Applicant
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                 <h3 class="section-divider">Applicant Form Details</h3>
                <div class="info-grid">
                <?php

                foreach ($form_details as $key => $value) {
                    // Exclude keys that are already displayed separately
                    if (in_array($key, ['business_name', 'business_address', 'type_of_business', 'applicant_name', 'applicant_email'])) {
                        continue;
                    }

                    // Format the label
                    $label = ucwords(str_replace('_', ' ', $key));

                    // Display the key-value pair as a single item
                     echo '<div class="info-item">';
                     echo '<label>' . htmlspecialchars($label) . '</label>';
                    
                        echo '<p>' . htmlspecialchars($value) . '</p>';
                    
                      echo '</div>';
                }
                ?>

                </div>



                <?php if (!empty($staff_form_data)): ?>
                <h3 class="section-divider">LGU Section Assessment</h3>
                <div class="form-section">
                    <h2>II. LGU SECTION (Read-Only)</h2>
                    <table>
                        <tr>
                            <th>Description</th>
                            <th>Office/Agency</th>
                            <th>Yes</th>
                            <th>No</th>
                            <th>Not Needed</th>
                        </tr>
                        <?php
                        $documents = [
                            "Occupancy Permit (For New)" => "Office of the Building Official",
                            "Barangay Clearance (For Renewal)" => "Barangay",
                            "Sanitary Permit / Health Clearance" => "City Health Office",
                            "City Environmental Certificate" => "City ENRO",
                            "Market Clearance (For Stall Holder)" => "City Market Administrator",
                            "Valid Fire Safety Inspection Certificate" => "Bureau of Fire Protection"
                        ];
                        foreach ($documents as $desc => $office) {
                            $status = $staff_form_data['verification'][$desc] ?? '';
                            echo "<tr>
                                <td>$desc</td>
                                <td>$office</td>
                                <td>" . ($status === 'Yes' ? '✔️' : '') . "</td>
                                <td>" . ($status === 'No' ? '✔️' : '') . "</td>
                                <td>" . ($status === 'Not Needed' ? '✔️' : '') . "</td>
                            </tr>";
                        }
                        ?>
                    </table>

                    <h3>2. Assessment of Applicable Fees</h3>
                    <table>
                        <tr>
                            <th>Local Taxes / Regulatory Fees</th>
                            <th>Amount Due</th>
                            <th>Penalty / Surcharge</th>
                            <th>Total</th>
                        </tr>
                        <?php
                        $fees = [
                            "Gross Sale Tax", "Tax on Delivery Vans/Trucks", "Tax on Storage for Combustible/Explosive Substances",
                            "Tax on Signboard/Billboards", "Mayor's Permit Fee", "Garbage Charges", "Delivery Trucks/Vans Permit Fee",
                            "Sanitary Inspection Fee", "Building Inspection Fee", "Electrical Inspection Fee", "Mechanical Inspection Fee",
                            "Plumbing Inspection Fee", "Signboard/Billboard Renewal Fee", "Signboard/Billboard and Permit Fee",
                            "Storage & Sale of Combustible/Explosive Substances", "Others"
                        ];
                        foreach ($fees as $fee) {
                            $amount = $staff_form_data['fees'][$fee]['amount'] ?? 0;
                            $penalty = $staff_form_data['fees'][$fee]['penalty'] ?? 0;
                            $total = $staff_form_data['fees'][$fee]['total'] ?? 0;
                            echo "<tr>
                                <td>$fee</td>
                                <td>₱ " . number_format((float)$amount, 2) . "</td>
                                <td>₱ " . number_format((float)$penalty, 2) . "</td>
                                <td>₱ " . number_format((float)$total, 2) . "</td>
                            </tr>";
                        }
                        ?>
                        <tr>
                            <td colspan="3"><strong>Total Fees for LGU</strong></td>
                            <td>
                                <?php
                                $totalLGU = $staff_form_data['fees']['Total Fees for LGU']['total'] ?? 0;
                                echo "₱ " . number_format((float)$totalLGU, 2);
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3"><strong>Fire Safety Inspection Fee (10%)</strong></td>
                            <td>
                                <?php
                                $totalFSIF = $staff_form_data['fees']['FSIF']['total'] ?? 0;
                                echo "₱ " . number_format((float)$totalFSIF, 2);
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php else: ?>
                <h3 class="section-divider">LGU Section Assessment</h3>
                <p>The LGU Section form has not been filled out for this application yet.</p>
                <?php endif; ?>

            </div>

        </div>

        <div id="tab-2" class="tab-content">
            <div class="form-container">
                <form method="POST" action="view_application.php?id=<?= $applicationId ?>" class="business-permit-form">
                    <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                    <input type="hidden" name="update_application" value="1">
                    <input type="hidden" name="old_status" value="<?= $application['status'] ?>">
                    <?= $lgu_message ?>
                    
                    <!-- LGU Section -->
                    <div class="form-section">
                        <h2>II. LGU Form</h2>
                        <table>
                            <tr>
                                <th>Description</th>
                                <th>Office/Agency</th>
                                <th>Yes</th>
                                <th>No</th>
                                <th>Not Needed</th>
                            </tr>
                            <?php
                            $documents = [
                                "Occupancy Permit (For New)" => "Office of the Building Official",
                                "Barangay Clearance (For Renewal)" => "Barangay",
                                "Sanitary Permit / Health Clearance" => "City Health Office",
                                "City Environmental Certificate" => "City ENRO",
                                "Market Clearance (For Stall Holder)" => "City Market Administrator",
                                "Valid Fire Safety Inspection Certificate" => "Bureau of Fire Protection"
                            ];
                            foreach ($documents as $desc => $office) {
                                echo "<tr>
                                    <td>$desc</td>
                                    <td>$office</td>
                                    <td><input type='radio' name='verification[$desc]' value='Yes' " . (($staff_form_data['verification'][$desc] ?? '') === 'Yes' ? 'checked' : '') . "></td>
                                    <td><input type='radio' name='verification[$desc]' value='No' " . (($staff_form_data['verification'][$desc] ?? '') === 'No' ? 'checked' : '') . "></td>
                                    <td><input type='radio' name='verification[$desc]' value='Not Needed' " . (($staff_form_data['verification'][$desc] ?? '') === 'Not Needed' ? 'checked' : '') . "></td>
                                </tr>";
                            }
                            ?>
                        </table>

                        <h3>2. Assessment of Applicable Fees</h3>
                        <table>
                            <tr>
                                <th>Local Taxes / Regulatory Fees</th>
                                <th>Amount Due</th>
                                <th>Penalty / Surcharge</th>
                                <th>Total</th>
                            </tr>
                            <?php
                            $fees = [
                                "Gross Sale Tax", "Tax on Delivery Vans/Trucks", "Tax on Storage for Combustible/Explosive Substances",
                                "Tax on Signboard/Billboards", "Mayor's Permit Fee", "Garbage Charges", "Delivery Trucks/Vans Permit Fee",
                                "Sanitary Inspection Fee", "Building Inspection Fee", "Electrical Inspection Fee", "Mechanical Inspection Fee",
                                "Plumbing Inspection Fee", "Signboard/Billboard Renewal Fee", "Signboard/Billboard and Permit Fee",
                                "Storage & Sale of Combustible/Explosive Substances", "Others"
                            ];
                            foreach ($fees as $fee) {
                                echo "<tr>
                                    <td>$fee</td>
                                    <td><input type='number' name='fees[$fee][amount]' step='0.01' value='" . htmlspecialchars($staff_form_data['fees'][$fee]['amount'] ?? '') . "'></td>
                                    <td><input type='number' name='fees[$fee][penalty]' step='0.01' value='" . htmlspecialchars($staff_form_data['fees'][$fee]['penalty'] ?? '') . "'></td>
                                    <td><input type='number' name='fees[$fee][total]' step='0.01' value='" . htmlspecialchars($staff_form_data['fees'][$fee]['total'] ?? '') . "'></td>
                                </tr>";
                            }
                            ?>
                            <tr>
                                <td colspan="3"><strong>Total Fees for LGU</strong></td>
                                <td><input type="number" name="fees[Total Fees for LGU][total]" step="0.01" value="<?= htmlspecialchars($staff_form_data['fees']['Total Fees for LGU']['total'] ?? '') ?>"></td>
                            </tr>
                            <tr>
                                <td colspan="3"><strong>Fire Safety Inspection Fee (10%)</strong></td>
                                <td><input type="number" name="fees[FSIF][total]" step="0.01" value="<?= htmlspecialchars($staff_form_data['fees']['FSIF']['total'] ?? '') ?>"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_application" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save LGU Form
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.querySelector('[data-tab=\'tab-1\']').click();">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
  <script>
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = document.getElementById(tab.dataset.tab);

            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            tabContents.forEach(tc => tc.classList.remove('active'));
            target.classList.add('active');
        });
    });

    // --- LGU Form Fee Calculation Logic ---
    document.addEventListener('DOMContentLoaded', function() {
        const feesTable = document.querySelector('#tab-2 .business-permit-form table:last-of-type');
        if (!feesTable) return;

        const feeRows = feesTable.querySelectorAll('tbody tr');
        const totalLGUInput = document.querySelector('input[name="fees[Total Fees for LGU][total]"]');
        const fsifInput = document.querySelector('input[name="fees[FSIF][total]"]');

        // Function to calculate the total for a single row
        function calculateRowTotal(row) {
            const amountInput = row.querySelector('input[name*="[amount]"]');
            const penaltyInput = row.querySelector('input[name*="[penalty]"]');
            const totalInput = row.querySelector('input[name*="[total]"]');

            if (amountInput && penaltyInput && totalInput) {
                const amount = parseFloat(amountInput.value) || 0;
                const penalty = parseFloat(penaltyInput.value) || 0;
                totalInput.value = (amount + penalty).toFixed(2);
            }
        }

        // Function to calculate the grand totals
        function calculateGrandTotals() {
            let grandTotal = 0;
            const individualFeeRows = Array.from(feeRows).slice(0, -2); // Exclude the last two total rows

            individualFeeRows.forEach(row => {
                const totalInput = row.querySelector('input[name*="[total]"]');
                if (totalInput) {
                    grandTotal += parseFloat(totalInput.value) || 0;
                }
            });

            if (totalLGUInput) {
                totalLGUInput.value = grandTotal.toFixed(2);
            }

            if (fsifInput) {
                // Fire Safety Inspection Fee is 10% of the LGU total
                const fsif = grandTotal * 0.10;
                fsifInput.value = fsif.toFixed(2);
            }
        }

        // Add event listeners to all amount and penalty inputs
        feeRows.forEach(row => {
            const inputs = row.querySelectorAll('input[name*="[amount]"], input[name*="[penalty]"]');
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    calculateRowTotal(row);
                    calculateGrandTotals();
                });
            });
        });

        // Initial calculation on page load to populate totals if data exists
        const individualFeeRows = Array.from(feeRows).slice(0, -2);
        individualFeeRows.forEach(row => {
             calculateRowTotal(row);
        });
        calculateGrandTotals();
    });
  </script>

<?php require_once './staff_footer.php'; ?>