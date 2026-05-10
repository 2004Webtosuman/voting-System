<?php require_once '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary) 0%, #2c5282 100%);
            padding: 2rem 0;
        }
        .form-container {
            max-width: 600px;
        }
        .registration-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .step {
            display: none;
        }
        .step.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="registration-header">
            <h2>Voter Registration</h2>
            <p style="color: var(--text-secondary);">Fill out the form to register for the upcoming election.</p>
        </div>

        <div class="progress-container">
            <div class="progress-step active" id="indicator-1">1</div>
            <div class="progress-step" id="indicator-2">2</div>
            <div class="progress-step" id="indicator-3">3</div>
        </div>

        <form action="insertNewAccountcheck.php" method="POST" enctype="multipart/form-data" id="regForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- Step 1: Personal Info -->
            <div class="step active" id="step-1">
                <h3>Personal Information</h3>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="username">Username (4-8 chars)</label>
                    <input type="text" id="username" name="username" class="form-control" minlength="4" maxlength="8" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-control" required>
                    <small style="color: var(--text-secondary);">You must be at least 18 years old.</small>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <label style="font-weight: normal;"><input type="radio" name="gender" value="male" required> Male</label>
                        <label style="font-weight: normal;"><input type="radio" name="gender" value="female" required> Female</label>
                        <label style="font-weight: normal;"><input type="radio" name="gender" value="other" required> Other</label>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn btn-primary" onclick="nextStep(1, 2)">Next Step <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Step 2: Contact & Identity -->
            <div class="step" id="step-2">
                <h3>Contact & Identity</h3>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="address">Full Address</label>
                    <textarea id="address" name="address" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number (10 digits)</label>
                    <input type="tel" id="phone" name="phone" class="form-control" pattern="[0-9]{10}" required>
                </div>
                <div class="form-group">
                    <label for="voters_id_number">Voter ID Number (10 digits)</label>
                    <input type="text" id="voters_id_number" name="voters_id_number" class="form-control" pattern="[0-9]{10}" required>
                </div>
                <div class="form-group">
                    <label for="voters_id">Upload Voter ID Proof (JPG/PNG/PDF, max 2MB)</label>
                    <input type="file" id="voters_id" name="voters_id" class="form-control" accept=".jpg,.png,.pdf" required>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" onclick="nextStep(2, 1)"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(2, 3)">Next Step <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Step 3: Security -->
            <div class="step" id="step-3">
                <h3>Security Setup</h3>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="retype_password">Retype Password</label>
                    <input type="password" id="retype_password" name="retype_password" class="form-control" required>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" onclick="nextStep(3, 2)"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="submit" class="btn btn-accent" id="submitBtn">Complete Registration <i class="fas fa-check"></i></button>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                Already have an account? <a href="loginpage.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login here</a>
            </div>
            <div style="text-align: center; margin-top: 1rem; font-size: 0.875rem;">
                <a href="../home.php" style="color: var(--text-secondary); text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </form>
    </div>

    <script>
        function nextStep(current, next) {
            // Basic validation before moving next
            if (current < next) {
                const currentStepDiv = document.getElementById(`step-${current}`);
                const inputs = currentStepDiv.querySelectorAll('input[required], textarea[required]');
                let valid = true;
                inputs.forEach(input => {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        valid = false;
                    }
                });
                
                // Password match check
                if (current === 3) {
                    const pass = document.getElementById('password').value;
                    const retype = document.getElementById('retype_password').value;
                    if (pass !== retype) {
                        alert("Passwords do not match.");
                        valid = false;
                    }
                }
                
                if (!valid) return;
            }

            document.getElementById(`step-${current}`).classList.remove('active');
            document.getElementById(`step-${next}`).classList.add('active');
            
            // Update indicators
            document.querySelectorAll('.progress-step').forEach((el, index) => {
                if (index < next) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });
        }
        
        // Prevent default submit on enter to avoid skipping steps
        document.getElementById('regForm').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
