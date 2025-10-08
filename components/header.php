<?php
// session_start();
require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$role = '';
$section = '';
$assemblyTarget = $_ENV['ASSEMBLY_TARGETMPEFF'] ?? null;
$stampingTarget = $_ENV['STAMPING_TARGETMPEFF'] ?? null;
$qcTarget       = $_ENV['QC_TARGETMPEFF'] ?? null;

$user_id = $_SESSION['user_id'] ?? 'UNKNOWN';
$role = strtolower($_SESSION['role'] ?? '');
$section = [];
if (!empty($_SESSION['section'])) {
    if (is_array($_SESSION['section'])) {
        $section = array_map('strtolower', $_SESSION['section']);
    } else {
        $section = [strtolower($_SESSION['section'])];
    }
}


$specific_section = [];
if (!empty($_SESSION['specific_section'])) {
    if (is_array($_SESSION['specific_section'])) {
        $specific_section = array_map('strtolower', $_SESSION['specific_section']);
    } else {
        $specific_section = [strtolower($_SESSION['specific_section'])];
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MES</title>
    <link rel="icon" type="image/png" href="assets/images/roberts_logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/demo1.style.css">
    <script src="assets/js/feather.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="/mes/components/reusable/sweetalert.js"></script>
    <script src="/mes/assets/js/sweetalert2@11.js"></script>
    <link href="./assets/css/bootstrap-icons.css" rel="stylesheet">
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <style>
        .swal-sm {
            max-width: 300px !important;
            /* smaller width */
            font-size: 0.9rem;
            /* smaller text */
            padding: 0.8rem !important;
        }

        @media (min-width: 768px) {
            .swal-sm {
                max-width: 400px !important;
                /* medium screens */
            }
        }

        @media (min-width: 992px) {
            .swal-sm {
                max-width: 500px !important;
                /* large screens */
            }
        }
    </style>

    <?php include 'announcement.php' ?>

<body>
    <div class="main-wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="sidebar-brand">
                    MES<span></span>
                </a>
                <div class="sidebar-toggler not-active">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            <div class="sidebar-body">
                <ul class="nav">
                    <li class="nav-item nav-category">Main-Section</li>
                    <?php
                    $roleFileMap = [
                        'administrator' => 'administrator.php',
                        'account manager' => 'general.php',
                        'planner' => 'general.php',
                        'delivery' => 'general.php',
                        'supervisor' => 'supervisor.php',
                        'line leader' => 'line_leader.php',
                        'fg warehouse' => 'general.php',
                        'rm warehouse' => 'general.php'
                    ];
                    $roleKey = strtolower($role);
                    if (isset($roleFileMap[$roleKey])) {
                        include __DIR__ . '/header/' . $roleFileMap[$roleKey];
                    } else {
                        echo '<!-- No sidebar for this role -->';
                    }
                    ?>
                </ul>
            </div>
        </nav>
        <div class="page-wrapper">

            <nav class="navbar">
                <a href="#" class="sidebar-toggler">
                    <span>&#9776;</span>
                </a>
                <div class="navbar-content d-flex align-items-center justify-content-between">
                    <span id="selectedModelDisplay" class="my-3 font-weight-bold text-dark" style="font-size:18px"></span>

                    <ul class="navbar-nav d-flex align-items-center">
                        <li class="nav-item me-3">
                            <a href="#" class="nav-link" id="announcementTrigger" title="Announcements">
                                <span>&#128276;</span> <!-- 🔔 Bell icon -->
                            </a>
                        </li>
                        <li class="nav-item dropdown nav-profile">
                            <a href="#" class="nav-link" id="profileDropdownToggle">
                                <span>&#128100;</span>
                            </a>
                            <div class="dropdown-menu" id="profileDropdownMenu" style="display: none;">
                                <div class="dropdown-header d-flex flex-column align-items-center">
                                    <div class="figure mb-3">
                                        <span>&#128100;</span>
                                    </div>
                                    <div class="info text-center">
                                        <p class="name font-weight-bold mb-0"><?php echo strtoupper($user_id ?? 'UNKNOWN'); ?></p>
                                        <p class="name font-weight-bold mb-0"><?php echo strtoupper($role ?? ''); ?></p>
                                        <?php if (!empty($displaySection)): ?>
                                            <p class="name font-weight-bold mb-0">(<?php echo strtoupper(implode(', ', $displaySection)); ?>)</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="dropdown-body">
                                    <ul class="profile-nav p-0 pt-3">
                                        <?php if (!empty($displaySpecificSection)): ?>
                                            <li class="nav-item">
                                                <span class="nav-link">
                                                    <span>&#128205;</span>
                                                    <span><?php echo strtoupper(implode(', ', $displaySpecificSection)); ?></span>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($role !== 'operator'): ?>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link" data-toggle="modal" data-target="#changePasswordModal">
                                                    <span>&#128273;</span> Change Password
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li class="nav-item">
                                            <a href="?page_active=logout" class="nav-link" id="logoutLink">
                                                <span>&#128274;</span> Log Out
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </li>

                    </ul>
                </div>
            </nav>

            <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <form id="changePasswordForm">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                </div>
                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <script>
                const sessionData = {
                    user_id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
                    role: <?php echo json_encode($_SESSION['role'] ?? null); ?>,
                    department: <?php echo json_encode($_SESSION['department'] ?? null); ?>,
                    section: <?php echo json_encode($_SESSION['section'] ?? []); ?>,
                    specific_section: <?php echo json_encode($_SESSION['specific_section'] ?? []); ?>,
                    page_active: <?php echo json_encode($_SESSION['page_active'] ?? []); ?>
                };

                console.log("Session Data:", sessionData);
                const urlParams = new URLSearchParams(window.location.search);
                const pageActive = urlParams.get('page_active');

                if (pageActive) {
                    const navLinks = document.querySelectorAll('.nav-link[data-page]');
                    navLinks.forEach(link => {
                        if (link.getAttribute('data-page') === pageActive) {
                            link.classList.add('active');
                            const parentCollapse = link.closest('.collapse');
                            if (parentCollapse && !parentCollapse.classList.contains('show')) {
                                parentCollapse.classList.add('show');
                                const parentLink = parentCollapse.previousElementSibling;
                                if (parentLink) {
                                    parentLink.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }
                    });
                }
                document.addEventListener("DOMContentLoaded", () => {
                    const form = document.getElementById("changePasswordForm");

                    form.addEventListener("submit", async (e) => {
                        e.preventDefault();
                        const sessionUserId = <?php echo json_encode($user_id); ?>;
                        const currentPassword = document.getElementById("currentPassword").value.trim();
                        const newPassword = document.getElementById("newPassword").value.trim();
                        const confirmPassword = document.getElementById("confirmPassword").value.trim();

                        if (newPassword !== confirmPassword) {
                            showAlert("error", "Mismatch", "New password and confirmation do not match!");
                            return;
                        }


                        try {
                            const response = await fetch("api/accounts/changePassword", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    user_id: sessionUserId,
                                    currentPassword,
                                    newPassword
                                })
                            });

                            const result = await response.json();


                            if (result.success) {
                                showAlert("success", "Password Updated", "Password updated successfully!");

                                form.reset();
                                // Close modal manually since we're not using jQuery
                                const modalEl = document.getElementById("changePasswordModal");
                                document.activeElement.blur();

                                modalEl.classList.remove("show");
                                modalEl.style.display = "none";
                                document.body.classList.remove("modal-open");
                                document.querySelector(".modal-backdrop").remove();
                            } else {
                                showAlert("error", "Update Failed", result.message || "Error updating password.");
                            }
                        } catch (err) {
                            console.error("Error:", err);
                            showAlert("error", "Server Error", "Something went wrong. Please try again.");
                        }
                    });
                });


                document.addEventListener('DOMContentLoaded', function() {
                    const toggle = document.getElementById('profileDropdownToggle');
                    const menu = document.getElementById('profileDropdownMenu');
                    const logoutLink = document.getElementById('logoutLink');

                    // Toggle dropdown manually
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                    });

                    // Logout functionality
                    logoutLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        localStorage.clear();
                        sessionStorage.clear();
                        window.location.href = this.href;
                    });

                    // Optional: close menu if clicked outside
                    document.addEventListener('click', function(e) {
                        if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                            menu.style.display = 'none';
                        }
                    });



                    localStorage.setItem("ASSEMBLY_TARGETMPEFF", "<?php echo $assemblyTarget; ?>");
                    localStorage.setItem("STAMPING_TARGETMPEFF", "<?php echo $stampingTarget; ?>");
                    localStorage.setItem("QC_TARGETMPEFF", "<?php echo $qcTarget; ?>");
                    let lastTriggerTime = new Date().toISOString();
                    const selectedModel = localStorage.getItem('selectedModel') || 'L300 DIRECT';
                });
                document.addEventListener("DOMContentLoaded", () => {
                    feather.replace(); // safely replace icons after DOM is ready
                });
            </script>
</body>
</head>

</html>