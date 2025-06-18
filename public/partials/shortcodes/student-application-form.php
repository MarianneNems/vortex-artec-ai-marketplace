<?php
/**
 * Template part for displaying the student application form
 *
 * @link       https://www.vortexaimarketplace.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-student-application-wrapper">
    <div class="vortex-student-tabs">
        <ul>
            <li class="active" data-tab="application-form"><span>1</span> Apply</li>
            <li data-tab="check-status"><span>2</span> Check Status</li>
        </ul>
    </div>
    
    <div class="vortex-student-tab-content">
        <!-- Application Form Tab -->
        <div id="application-form" class="tab-content active">
            <div class="vortex-form-intro">
                <h3>Student Verification</h3>
                <p>Get verified as a student to access exclusive discounts on AI tools and models. Fill out the form below with accurate information and upload your student ID or enrollment verification document.</p>
            </div>
            
            <form id="vortex-student-application-form" method="post" enctype="multipart/form-data">
                <div class="form-status-message"></div>
                
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                    <p class="field-note">Use your academic email if possible for faster verification.</p>
                </div>
                
                <div class="form-group">
                    <label for="institution">School/University <span class="required">*</span></label>
                    <input type="text" id="institution" name="institution" required>
                </div>
                
                <div class="form-group">
                    <label for="program">Program/Major <span class="required">*</span></label>
                    <input type="text" id="program" name="program" required>
                </div>
                
                <div class="form-group">
                    <label for="graduation_year">Expected Graduation Year <span class="required">*</span></label>
                    <select id="graduation_year" name="graduation_year" required>
                        <option value="">Select Year</option>
                        <?php
                        $current_year = date('Y');
                        for ($i = $current_year; $i <= $current_year + 7; $i++) {
                            echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="student_id">Student ID (optional)</label>
                    <input type="text" id="student_id" name="student_id">
                </div>
                
                <div class="form-group file-upload">
                    <label for="verification_document">Upload Student ID or Enrollment Verification</label>
                    <input type="file" id="verification_document" name="verification_document" accept=".pdf,.jpg,.jpeg,.png">
                    <p class="field-note">Accepted formats: PDF, JPG, PNG (max 5MB)</p>
                    <div class="upload-preview"></div>
                </div>
                
                <div class="form-group verification-code">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" placeholder="If you have a special verification code, enter it here">
                    <p class="field-note">If you received a verification code from your institution or an event, enter it here.</p>
                </div>
                
                <div class="form-group checkbox">
                    <input type="checkbox" id="confirm_student" name="confirm_student" value="yes" required>
                    <label for="confirm_student">I confirm that I am currently enrolled as a student <span class="required">*</span></label>
                </div>
                
                <div class="form-group checkbox">
                    <input type="checkbox" id="agree_terms" name="agree_terms" value="yes" required>
                    <label for="agree_terms">I agree to the <a href="/terms-and-conditions/" target="_blank">Terms and Conditions</a> and understand that my student status will be verified before discounts are applied <span class="required">*</span></label>
                </div>
                
                <?php wp_nonce_field('vortex_student_application_nonce', 'student_application_nonce'); ?>
                <input type="hidden" name="action" value="vortex_submit_student_application">
                <input type="hidden" name="security" value="<?php echo wp_create_nonce('vortex_student_application_nonce'); ?>">
                
                <div class="form-group submit-group">
                    <button type="submit" class="vortex-button primary">Submit Application</button>
                </div>
            </form>
        </div>
        
        <!-- Check Status Tab -->
        <div id="check-status" class="tab-content">
            <div class="vortex-form-intro">
                <h3>Check Application Status</h3>
                <p>Enter your email address to check the status of your student verification application.</p>
            </div>
            
            <form id="vortex-student-status-form" method="post">
                <div class="status-result"></div>
                
                <div class="form-group">
                    <label for="status_email">Email Address <span class="required">*</span></label>
                    <input type="email" id="status_email" name="email" required>
                </div>
                
                <?php wp_nonce_field('vortex_student_status_nonce', 'student_status_nonce'); ?>
                <input type="hidden" name="action" value="vortex_check_student_status">
                <input type="hidden" name="security" value="<?php echo wp_create_nonce('vortex_student_status_nonce'); ?>">
                
                <div class="form-group submit-group">
                    <button type="submit" class="vortex-button secondary">Check Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabs = document.querySelectorAll('.vortex-student-tabs li');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab content
                const tabContents = document.querySelectorAll('.tab-content');
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Show selected tab content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // File upload preview
        const fileInput = document.getElementById('verification_document');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const preview = document.querySelector('.upload-preview');
                preview.innerHTML = '';
                
                if (this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    const fileSize = Math.round(this.files[0].size / 1024); // Convert to KB
                    
                    preview.innerHTML = `<div class="file-info">
                        <span class="file-name">${fileName}</span>
                        <span class="file-size">${fileSize} KB</span>
                        <button type="button" class="remove-file">Remove</button>
                    </div>`;
                    
                    // Add remove button functionality
                    const removeButton = preview.querySelector('.remove-file');
                    removeButton.addEventListener('click', function() {
                        fileInput.value = '';
                        preview.innerHTML = '';
                    });
                }
            });
        }
    });
</script>

<style>
.vortex-student-application-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.vortex-form-header {
    margin-bottom: 30px;
    text-align: center;
}

.vortex-form-header h2 {
    color: #333;
    margin-bottom: 10px;
}

.form-row {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-group small {
    display: block;
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 3px;
    margin-right: 10px;
}

.vortex-button {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.vortex-button:hover {
    background-color: #45a049;
}

.vortex-button:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.success-message {
    padding: 15px;
    background-color: #dff0d8;
    border: 1px solid #d6e9c6;
    color: #3c763d;
    border-radius: 4px;
    margin-bottom: 20px;
}

.error-message {
    padding: 15px;
    background-color: #f2dede;
    border: 1px solid #ebccd1;
    color: #a94442;
    border-radius: 4px;
    margin-bottom: 20px;
}

.required {
    color: #e74c3c;
}

@media (max-width: 768px) {
    .vortex-student-application-container {
        padding: 15px;
    }
    
    .vortex-button {
        width: 100%;
    }
}
</style> 