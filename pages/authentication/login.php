<?php

if (isset($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Roberts Prod</title>
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/slim.css">
    <style>
        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            background-color: #0d6efd;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;

        }

        .password-toggle-btn:hover {
            color: #000;
        }

        #loginPassword:focus+.password-toggle-btn {
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="signin-wrapper w-100" style="max-width: 520px;">
            <form>
                <div class="signin-box w-100">
                    <div class="d-flex flex-column align-items-start text-start my-4">
                        <img src="/mes/assets/images/roberts_logo.png" alt="Roberts Logo" width="280">
                        <h1 style="color: #28a745; letter-spacing: 1px; font-size: 28px; font-weight: 900; font-style: italic;">
                            <a href="index.php" class="text-decoration-none" style="color: inherit;">
                                MFG EXECUTION SYSTEM
                            </a>
                        </h1>
                    </div>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <div class="signin d-flex flex-column align-items-center text-center px-3">
                        <div id="loginError" class="alert alert-danger w-100 mb-3" style="display:none;"></div>

                        <div class="form-group w-100 mb-3">
                            <input type="text" name="user_id" class="form-control" placeholder="Enter your username" required>
                        </div>

                        <div class="form-group w-100 mb-4 position-relative">
                            <input type="password"
                                name="password"
                                class="form-control pe-5"
                                placeholder="Enter your password"
                                id="loginPassword"
                                required>
                            <button type="button"
                                id="togglePassword"
                                class="password-toggle-btn btn btn-sm border-0 bg-transparent"
                                tabindex="-1">
                                <i class="bi bi-eye" id="togglePasswordIcon" style="font-size:20px;"></i>
                            </button>
                        </div>
                        <button class=" btn btn-primary w-100" type="submit">Sign In</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="forgotPasswordForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Enter your username or email to reset your password:</p>
                        <input type="text" id="resetUsername" class="form-control" placeholder="Username or Email" required>
                        <div id="forgotError" class="alert alert-danger mt-2" style="display:none;"></div>
                        <div id="forgotSuccess" class="alert alert-success mt-2" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    const passwordInput = document.getElementById('loginPassword');
    const togglePassword = document.getElementById('togglePassword');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    togglePassword.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });


    document.addEventListener('DOMContentLoaded', () => {
        const forgotLink = document.getElementById('forgotPasswordLink');
        const forgotModal = document.getElementById('forgotPasswordModal');
        const forgotForm = document.getElementById('forgotPasswordForm');
        const forgotError = document.getElementById('forgotError');
        const forgotSuccess = document.getElementById('forgotSuccess');

        // Show modal using native Bootstrap API
        forgotLink.addEventListener('click', (e) => {
            e.preventDefault();
            const modalInstance = new bootstrap.Modal(forgotModal);
            modalInstance.show();
            forgotError.style.display = 'none';
            forgotSuccess.style.display = 'none';
            document.getElementById('resetUsername').value = '';
        });
    });


    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('.signin-wrapper form');
        const errorBox = document.getElementById('loginError');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.style.display = 'none';

            const data = new FormData(form);
            try {
                const res = await fetch('/mes/api/login', {
                    method: 'POST',
                    body: data,
                    credentials: 'include'
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    showError(json.message || 'Login failed');
                    return;
                }
                console.log(json)
                if (json.success && json.page_active) {
                    const url = `https://10.0.6.5/mes/index.php?page_active=${json.page_active}`;
                    window.location.replace(url);
                }

            } catch (err) {
                showError('Network error – please try again.');
            }
        });

        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.style.display = 'block';
        }
    });
</script>