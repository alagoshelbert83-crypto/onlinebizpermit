<?php
// FAQ Data with Interactive Choices
$faqs = [
    [
        'id' => 'welcome',
        'keywords' => ['hello', 'hi', 'start', 'help', 'welcome'],
        'answer' => 'Welcome to OnlineBizPermit! I can help you with business permit applications, requirements, and general questions. What would you like to know?',
        'choices' => [
            ['text' => 'How to apply for a business permit?', 'action' => 'apply_process'],
            ['text' => 'What documents do I need?', 'action' => 'required_documents'],
            ['text' => 'Check application status', 'action' => 'check_status'],
            ['text' => 'Technical or payment issues?', 'action' => 'technical_support'],
            ['text' => 'Talk to a person (Live Chat)', 'action' => 'live_chat_request']
        ]
    ],
    [
        'id' => 'account_help',
        'keywords' => ['account', 'login', 'password', 'reset', 'forgot'],
        'answer' => 'Account Help:
        
                    • Forgot password: Use “Forgot Password” on the login page
                    
                    • Change password: Go to Settings → Change Password
                    
                    • Update email/phone: Settings → Update Profile
                    
                    • Can\'t login: Ensure correct email and reset your password if needed',
        'choices' => [
            ['text' => 'Reset my password', 'action' => 'how_reset_password'],
            ['text' => 'Change my email/phone', 'action' => 'update_profile_help'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'how_reset_password',
        'keywords' => ['reset password', 'forgot password', 'recover'],
        'answer' => 'Reset Password Steps:
        
                    1. Click “Forgot Password” on the login page
                    
                    2. Enter your registered email
                    
                    3. Check your inbox for the reset link
                    
                    4. Set a new secure password',
        'choices' => [
            ['text' => 'Back to Account Help', 'action' => 'account_help'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'update_profile_help',
        'keywords' => ['update profile', 'change email', 'change phone'],
        'answer' => 'Update Profile:
        
                    1. Log in to your account
                    
                    2. Go to Settings
                    
                    3. Edit your Email and Phone
                    
                    4. Save changes
                    
                    For password changes, use the Change Password section in Settings.',
        'choices' => [
            ['text' => 'Go to Settings', 'action' => 'account_help'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'penalties_fines',
        'keywords' => ['penalty', 'fine', 'late', 'deadline', 'overdue'],
        'answer' => 'Penalties & Fines:
        
                    • Late renewal may incur a surcharge (10%-25% depending on LGU rules)
                    
                    • Operating without a permit can lead to closure orders and fines
                    
                    • Always renew on time and keep documents updated',
        'choices' => [
            ['text' => 'Renewal requirements', 'action' => 'renewal_docs'],
            ['text' => 'Processing time', 'action' => 'processing_time'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'office_hours',
        'keywords' => ['hours', 'schedule', 'time', 'open', 'close'],
        'answer' => 'Office Hours:
                    
                    • Monday–Friday: 8:00 AM – 5:00 PM
                    
                    • Lunch Break: 12:00 PM – 1:00 PM
                    
                    • Weekends/Holidays: Closed
                    
                    Online services remain available 24/7.',
        'choices' => [
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'business_types',
        'keywords' => ['type', 'classification', 'single', 'partnership', 'corporation', 'cooperative'],
        'answer' => 'Business Types:
        
                    • Single Proprietorship
                    
                    • Partnership\n• Corporation
                    
                    • Cooperative
                    
                    Choose the correct type during application for accurate fees and requirements.',
        'choices' => [
            ['text' => 'Documents by type', 'action' => 'required_documents'],
            ['text' => 'Application steps', 'action' => 'apply_process']
        ]
    ],
    [
        'id' => 'apply_process',
        'keywords' => ['apply', 'application', 'process', 'how to apply', 'steps'],
        'answer' => 'Here\'s how to apply for a business permit:',
        'choices' => [
            ['text' => 'Step 1: Fill Application Form', 'action' => 'step1'],
            ['text' => 'Step 2: Upload Documents', 'action' => 'step2'],
            ['text' => 'Step 3: Assessment & Payment', 'action' => 'step3'],
            ['text' => 'Can I edit my application?', 'action' => 'edit_application'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'step1',
        'keywords' => ['step 1', 'application form', 'fill form'],
        'answer' => 'Step 1: Fill Application Form  
                    
                    •Click "New Application"
                    
                    •Select business type
                    
                    •Fill in business details
                    
                    •Provide business address
                    
                    •Review all information',
        'choices' => [
            ['text' => 'Next: Upload Documents', 'action' => 'step2'],
            ['text' => 'Back to application process', 'action' => 'apply_process'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'step2',
        'keywords' => ['step 2', 'upload', 'documents', 'files'],
        'answer' => 'Step 2: Upload Required Documents
                    
                    •Prepare all required documents
                    
                    •Click "Upload Documents"
                    
                    •Upload each document
                    
                    •Ensure files are clear and readable
                    
                    •Verify all documents are uploaded',
        'choices' => [
            ['text' => 'Next: Submit & Pay', 'action' => 'step3'],
            ['text' => 'What documents do I need?', 'action' => 'required_documents'],
            ['text' => 'Back to application process', 'action' => 'apply_process']
        ]
    ],
    [
        'id' => 'step3',
        'keywords' => ['step 3', 'submit', 'pay', 'payment', 'assessment', 'receipt'],
        'answer' => 'Step 3: Assessment & Payment
                        
                        •Submit your application for review.
                        
                        • Wait for our team to assess the fees.
                        
                        •You will be notified once the assessment is ready.
                        
                        •Pay the assessed fees at the municipal treasurer\'s office or authorized banks.
                        
                        •Upload a clear copy of your official receipt.
                        
                        •Your application will proceed once payment is verified.',
        'choices' => [
            ['text' => 'How to check status?', 'action' => 'check_status'],
            ['text' => 'Where to pay?', 'action' => 'payment_methods'],
            ['text' => 'Back to application process', 'action' => 'apply_process']
        ]
    ],
    [
        'id' => 'required_documents',
        'keywords' => ['documents', 'requirements', 'what do i need', 'papers'],
        'answer' => 'Required Documents for Business Permit:',
        'choices' => [
            ['text' => 'For New Business', 'action' => 'new_business_docs'],
            ['text' => 'For Renewal', 'action' => 'renewal_docs'],
            ['text' => 'For Amendment', 'action' => 'amendment_docs'],
            ['text' => 'Common Document Questions', 'action' => 'document_clarifications'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'new_business_docs',
        'keywords' => ['new business', 'new application', 'first time'],
        'answer' => 'Documents for New Business Application:
                    
                    • Valid ID (2 copies)
                    
                    • Barangay Clearance
                    
                    • DTI Registration Certificate
                    
                    • BIR Registration Certificate
                    
                    • Fire Safety Certificate
                    
                    • Sanitary Permit
                    
                    • Building Permit',
        'choices' => [
            ['text' => 'For Renewal', 'action' => 'renewal_docs'],
            ['text' => 'For Amendment', 'action' => 'amendment_docs'],
            ['text' => 'Back to documents', 'action' => 'required_documents']
        ]
    ],
    [
        'id' => 'document_clarifications',
        'keywords' => ['barangay clearance', 'dti', 'BIR', 'fire safety', 'sanitary permit','Building Permit'],
        'answer' => 'Here are some clarifications on common documents:',
        'choices' => [
            ['text' => 'Where to get Barangay Clearance?', 'action' => 'where_get_barangay'],
            ['text' => 'What is DTI/SEC/CDA Registration?', 'action' => 'what_is_dti_sec'],
            ['text' => 'Back to document list', 'action' => 'required_documents']
        ]
    ],
    [
        'id' => 'where_get_barangay',
        'keywords' => ['barangay clearance', 'where get'],
        'answer' => "You can obtain a Barangay Business Clearance from the barangay hall where your business is physically located. 
                     This is a prerequisite for the municipal business permit.",
        'choices' => [
            ['text' => 'What is DTI/SEC/CDA Registration?', 'action' => 'what_is_dti_sec'],
            ['text' => 'Back to document list', 'action' => 'required_documents']
        ]
    ],
    [
        'id' => 'what_is_dti_sec',
        'keywords' => ['dti', 'sec', 'cda', 'registration'],
        'answer' => "This is your primary business registration with a national government agency:
                    
                    • DTI (Department of Trade and Industry): For sole proprietorships.
                    
                    • SEC (Securities and Exchange Commission): For partnerships and corporations.
                    
                    • CDA (Cooperative Development Authority): For cooperatives.",
        'choices' => [
            ['text' => 'Where to get Barangay Clearance?', 'action' => 'where_get_barangay'],
            ['text' => 'Back to document list', 'action' => 'required_documents']
        ]
    ],
    [
        'id' => 'renewal_docs',
        'keywords' => ['renewal', 'renew', 'expired'],
        'answer' => 'Documents for Business Permit Renewal:
           
                     • Valid ID (2 copies)
                     
                     • Barangay Clearance
                     
                     • DTI Registration Certificate
                     
                     • BIR Registration Certificate
                     
                     • Fire Safety Certificate
                     
                     • Sanitary Permit
                     
                     • Building Permit',
        'choices' => [
            ['text' => 'For New Business', 'action' => 'new_business_docs'],
            ['text' => 'For Amendment', 'action' => 'amendment_docs'],
            ['text' => 'Back to documents', 'action' => 'required_documents']
        ]
    ],
    [
        'id' => 'renewal_process',
        'keywords' => ['renewal process', 'when to renew', 'renewal period'],
        'answer' => "The business permit renewal period is typically from January 1st to January 20th of each year.
                    
                    1. Log in to your account.
                    
                    2. Go to 'My Applications'.
                    
                    3. Find your expiring permit and click the 'Renew' button.
                    
                    4. Update any changed information and upload the latest required documents.",
        'choices' => [
            ['text' => 'Renewal documents', 'action' => 'renewal_docs'],
            ['text' => 'Penalties for late renewal', 'action' => 'penalties_fines'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'amendment_docs',
        'keywords' => ['amendment', 'change', 'modify', 'update'],
        'answer' => 'Documents for Business Permit Amendment:
                    
                    • Current Business Permit
                    
                    • Updated DTI/SEC Registration
                    
                    • Updated Barangay Clearance
                    
                    • Supporting documents for changes
                    
                    • Affidavit of Change
                    
                    • Updated lease contract (if address changed)
                    
                    • Tax clearance',
        'choices' => [
            ['text' => 'For New Business', 'action' => 'new_business_docs'],
            ['text' => 'For Renewal', 'action' => 'renewal_docs'],
            ['text' => 'Back to documents', 'action' => 'required_documents']
        ]
    ],
    [
        'id' => 'check_status',
        'keywords' => ['status', 'check', 'track', 'progress', 'where is my'],
        'answer' => 'How to Check Your Application Status:
                    
                    1. Log in to your account
                   
                    2. Go to "My Applications"
                   
                    3. Click on your application
                   
                    4. View current status and updates
                   
                    Status Types:
                   
                    • Pending - Application received
                   
                    • Review - Being processed by staff
                   
                    • Approved - Ready for permit release
                   
                    • Complete - Permit has been released
                   
                    • Rejected - Needs revision',
        'choices' => [
            ['text' => 'What if my application is rejected?', 'action' => 'rejected_application'],
            ['text' => 'How long does it take?', 'action' => 'processing_time'],
            ['text' => 'What happens after it\'s approved?', 'action' => 'post_approval'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'rejected_application',
        'keywords' => ['rejected', 'denied', 'failed', 'not approved'],
        'answer' => 'If Your Application is Rejected:
                    
                    1. Check the rejection reason in your account
                    
                    2. Review required corrections
                    
                    3. Update your application
                    
                    4. Resubmit with corrections
                    
                    5. Contact support if you need help
                    
                    Common rejection reasons:
                    
                    • Wrong documents
                    
                    • Incorrect information
                    
                    • Unclear document copies',
        'choices' => [
            ['text' => 'How to resubmit?', 'action' => 'resubmit_process'],
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Back to status check', 'action' => 'check_status']
        ]
    ],
    [
        'id' => 'resubmit_process',
        'keywords' => ['resubmit', 'submit again', 'correct', 'fix'],
        'answer' => 'How to Resubmit Your Application:
                    1. Log in to your account
                    
                    2. Go to "My Applications"
                    
                    3. Click on the rejected application
                    
                    4. Click "Edit Application"
                    
                    5. Make the required corrections
                    
                    6. Upload corrected documents
                    
                    7. Review all changes
                    
                    8. Submit the updated application',
        'choices' => [
            ['text' => 'What if I need help?', 'action' => 'contact_support'],
            ['text' => 'Back to status check', 'action' => 'check_status'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'edit_application',
        'keywords' => ['edit', 'change', 'correct', 'mistake', 'update application'],
        'answer' => "You can edit your application information as long as its status is 'Pending'.
                    
                    If it has been 'Rejected', you can also edit it to make corrections before resubmitting.
                    
                    Once an application is 'Under Review' or 'Approved', you can no longer edit it.
                    
                    If you need to make urgent changes, please contact support or use the live chat.",
        'choices' => [
            ['text' => 'How to resubmit a rejected application?', 'action' => 'resubmit_process'],
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'processing_time',
        'keywords' => ['how long', 'time', 'duration', 'when', 'days'],
        'answer' => 'Processing Time for Business Permits:
            
                    • New Applications: 5-10 business days
                    
                    • Renewals: 3-5 business days
                    • Amendments: 3-7 business days
                    
                    Note: Processing time may vary based on:
                    
                    • Completeness of documents
                    
                    • Business type complexity
                    
                    • Current workload
                    
                    • Payment processing time',
        'choices' => [
            ['text' => 'How to speed up processing?', 'action' => 'speed_up'],
            ['text' => 'Check status', 'action' => 'check_status'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'speed_up',
        'keywords' => ['speed up', 'faster', 'expedite', 'rush'],
        'answer' => 'Tips to Speed Up Processing:
        
                    1. Ensure all documents are complete
                    
                    2. Upload clear, high-quality scans
                    
                    3. Double-check all information
                    
                    4. Respond quickly to requests
                    
                    5. Keep contact information updated
                    
                    6. Pay fees promptly
                    
                    Note: Rush processing may be available for an additional fee.',
        'choices' => [
            ['text' => 'Payment methods', 'action' => 'payment_methods'],
            ['text' => 'Check status', 'action' => 'check_status'],
            ['text' => 'Main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'payment_methods',
        'keywords' => ['payment', 'pay', 'fee', 'cost', 'where to pay', 'bank', 'treasurer'],
        'answer' => 'Payment Information:
                    
                    After your application is assessed, you can pay your fees at:
                    
                    • Municipal Treasurer\'s Office\
                    
                    • Authorized partner banks (e.g., Landbank, PNB)
                    
                    Important: Always get an official receipt. You will need to upload a clear photo or scan of it to proceed with your application.
                    
                    Fees:
                    
                    • New Business Permit: ₱500-₱2,000
                    
                    • Renewal: ₱300-₱1,500
                    
                    • Amendment: ₱200-₱1,000
                    
                    *Fees vary by business type and location',
        'choices' => [
            ['text' => 'How do I upload the receipt?', 'action' => 'how_to_pay'],
            ['text' => 'What if I have payment issues?', 'action' => 'payment_issues'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'how_to_pay',
        'keywords' => ['how to pay', 'payment process', 'upload receipt'],
        'answer' => 'How to Pay & Upload Receipt:
        
                    1. Wait for a notification that your application has been assessed.
                    
                    2. Go to "My Applications" and wait for the staff to send a notification about your application if you must pay and upload your receipt.
                    
                    3. Secure the Official Receipt.
                    
                    4. Go to your My application and click edit  and upload your Payment Receipt" and submit a clear photo or scan.
                    
                    5. Our team will verify your payment within 1-2 business days.',
        'choices' => [
            ['text' => 'Where can I pay?', 'action' => 'payment_methods'],
            ['text' => 'What if I have issues?', 'action' => 'payment_issues'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'payment_issues',
        'keywords' => ['payment problem', 'receipt issue', 'upload failed', 'payment error', 'verification'],
        'answer' => 'If You Have Payment/Receipt Issues:
                    • Receipt upload failed: Ensure the file is a clear JPG, PNG, or PDF and under 50MB.
                     Try again with a stable internet connection.
                     
                     • Payment not verified: Verification takes 1-2 business days. If it takes longer, please contact support with a copy of your receipt.
                     
                     • Incorrect amount paid: Contact support immediately to resolve any discrepancies.',
        'choices' => [
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Where to pay?', 'action' => 'payment_methods'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'post_approval',
        'keywords' => ['after approval', 'get permit', 'download permit', 'print permit', 'release'],
        'answer' => "After your application status is 'Approved', our team will prepare the final permit for release. Once it's ready, the status will change to 'Complete'.
                     At the 'Complete' stage, you can download and print your official Business Permit directly from your dashboard.",
        'choices' => [
            ['text' => 'How do I print the permit?', 'action' => 'how_to_print'],
            ['text' => 'How to check status?', 'action' => 'check_status'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'how_to_print',
        'keywords' => ['print', 'download'],
        'answer' => "To print your permit:
                    
        1. Go to 'My Applications' on your dashboard.
        
        2. Find the application with a 'Complete' status.
        
        3. Click 'View' to open the application details.
        
        4. Click the 'Print Permit' or 'Download Permit' button.",
        'choices' => [
            ['text' => 'What happens after approval?', 'action' => 'post_approval'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'technical_support',
        'keywords' => ['technical', 'error', 'bug', 'website', 'slow', 'upload failed', 'button not working'],
        'answer' => "Having technical issues? Here are some common fixes:
        
                    • Website is slow: Try clearing your browser's cache and cookies, or check your internet connection.
                    
                    • Can't upload a file: Ensure the file is a clear JPG, PNG, or PDF and under 50MB. Try a different browser.
                    
                    • Button not working: Refresh the page (Ctrl+R or Cmd+R). If the issue persists, please report it.
                    
                    For payment-related issues, please see our payment help section.",
        'choices' => [
            ['text' => 'Payment issues', 'action' => 'payment_issues'],
            ['text' => 'Report a problem', 'action' => 'report_problem'],
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'contact_support',
        'keywords' => ['contact', 'support', 'help', 'assistance', 'phone', 'email'],
        'answer' => 'Contact Our Support Team:
                    
                    📞 Phone: (02) 1234-5678
                    
                    📧 Email: @OnlineBiz Permit Support
                    
                    💬 Live Chat: Available 24/7
                    
                    🏢 Office: Municipal of SanMiguel, Business Permit Office
                    
                    ⏰ Hours: Monday-Friday, 8AM-5PM
                    
                    For urgent matters, please call our hotline.',
        'choices' => [
            ['text' => 'Report a technical problem', 'action' => 'technical_support'],
            ['text' => 'Schedule appointment', 'action' => 'schedule_appointment'],
            ['text' => 'Is my data safe?', 'action' => 'data_privacy'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'report_problem',
        'keywords' => ['report', 'problem', 'issue', 'bug', 'complaint'],
        'answer' => 'Report a Problem:
                    
                    1. Go to "Feedback" section
                    
                    2. Select "Report Problem"
                    
                    3. Describe the issue
                    
                    4. Provide your contact info
                    
                    5. Submit the report\'ll investigate and respond within 24 hours.',
        'choices' => [
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Schedule appointment', 'action' => 'schedule_appointment'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'data_privacy',
        'keywords' => ['privacy', 'security', 'safe', 'data', 'secure'],
        'answer' => "We take your data privacy very seriously. All information you submit is encrypted during transmission and stored securely on our servers. 
                     We comply with the Data Privacy Act of 2012 (RA 10173).
                     We will never share your personal information with third parties without your explicit consent, except as required by law for processing your permit.",
        'choices' => [
            ['text' => 'Contact support', 'action' => 'contact_support'],
            ['text' => 'Back to main menu', 'action' => 'welcome']
        ]
    ],
    [
        'id' => 'fallback',
        'keywords' => [], // No keywords, used as a default
        'answer' => "I'm sorry, I couldn't find an answer to your question. Would you like to try rephrasing it, or would you prefer to talk to a staff member for assistance?",
        'choices' => [
            ['text' => 'How to apply?', 'action' => 'apply_process'],
            ['text' => 'What are the requirements?', 'action' => 'required_documents']
        ]
    ]
];

// Function to get FAQ by ID
function getFaqById($id, $faqs) {
    foreach ($faqs as $faq) {
        if ($faq['id'] === $id) {
            return $faq;
        }
    }
    return null;
}

// Function to get FAQ by keywords
function getFaqByKeywords($keywords, $faqs) {
    $lowerCaseInput = strtolower(trim($keywords));
    if (empty($lowerCaseInput)) {
        return null;
    }

    $bestMatch = null;
    $maxMatchCount = 0;

    foreach ($faqs as $faq) {
        $currentMatchCount = 0;
        foreach ($faq['keywords'] as $keyword) {
            // Use strpos as mb_strpos might not be available on all hosts.
            // Also check if keyword is not empty to prevent errors.
            if (!empty($keyword) && strpos($lowerCaseInput, $keyword) !== false) {
                $currentMatchCount++;
            }
        }

        if ($currentMatchCount > $maxMatchCount) {
            $maxMatchCount = $currentMatchCount;
            $bestMatch = $faq;
        }
    }

    return $bestMatch;
}
?>
