<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$timeDivisor = $_ENV['TIME_DIVISOR'] ?? 'Not set';
date_default_timezone_set('Asia/Manila');

$pageMap = [
    'authentication' => [
        'login' => 'login.php',
        'logout' => 'logout.php'
    ],
    'accounts' => [
        'manage_accounts' => 'manage_accounts.php',
    ],
    'planner' => [
        'mef_form' => 'mef_form.php',
        'muffler_form' => 'muffler_form.php',
        'customer_form' => 'customer_form.php',
        'form_history' => 'form_history.php'
    ],
    'delivery' => [
        'for_delivery' => 'for_delivery.php',
        'delivered_history' => 'delivered_history.php'
    ],
    'assembly' => [
        'assembly_accounts' => 'accounts.php',
        'assembly_assign_pi_sku' => 'sku_pi_assign.php',
        'assembly_pi_kbn_sku' => 'sku_pi_kbn.php',
        'assembly_manpower_efficiency' => 'manpower_efficiency.php',
        'assembly_sectional_efficiency' => 'sectional_efficiency.php',
        'assembly_assign_pi_components' => 'component_pi_assign.php',
        'assembly_pi_kbn_component' => 'component_pi_kbn.php',

    ],
    'rm' => [

        'issue_rm' => 'issue_rm.php',
        'issued_history' => 'issued_history.php',

    ],
    'stamping' => [
        'stamping_accounts' => 'accounts.php',
        'stamping_assign_pi_kbn' => 'assign_pi_kbn.php',
        'stamping_pi_kbn'   => 'pi_kbn.php',
        'components_inventory' => 'components_inventory.php',
        'stamping_sectional_efficiency' => 'sectional_efficiency.php',
        'stamping_manpower_efficiency' => 'manpower_efficiency.php',
    ],
    'qc' => [
        'qc_accounts' => 'accounts.php',
        'qc_pi_kbn' => 'pi_kbn.php',
        'qc_ncp'    => 'ncp.php',
        'qc_direct_ok' => 'direct_ok.php',
        'qc_direct_ok_sectional'    => 'sectional_direct_ok.php',
    ],
    'fg' => [
        'materials_inventory' => 'materials_inventory.php',
        'for_pulling'    => 'for_pulling.php',
        'pulling_history'    => 'pulling_history.php',
    ],
    'painting' => [
        'painting_accounts' => 'accounts.php',
        'painting_assign_pi_kbn' => 'assign_pi_kbn.php',
        'pi_kbn_painting' => 'pi_kbn_sku.php',
        'painting_manpower_efficiency' => 'manpower_efficiency.php',
        'painting_sectional_efficiency' => 'sectional_efficiency.php',
    ],
    'finishing' => [
        'finishing_accounts' => 'accounts.php',
        'finishing_assign_pi_sku' => 'pi_kbn_assign_sku.php',
        'finishing_assign_pi_component' => 'pi_kbn_assign_component.php',
        'finishing_assign_rework' => 'rework_assign.php',


        'finishing_pi_kbn_sku' => 'pi_kbn_sku.php',
        'finishing_pi_kbn_component' => 'pi_kbn_component.php',
        'finishing_for_rework' => 'rework.php',
        'finishing_manpower_efficiency' => 'manpower_efficiency.php',
        'finishing_sectional_efficiency' => 'sectional_efficiency.php',
    ],
    'announcement' => [
        'announcement_list' => 'manageAnnouncement.php'
    ],
    'masterlistData' => [
        'manage_sku' => 'manage_sku.php',
        'manage_component' => 'manage_component.php',
        'manage_rm' => 'manage_rm.php'
    ],




];

$requestedPage = $_GET['page_active'] ?? null;
$requestedPage = $requestedPage ? basename($requestedPage) : null;
$GLOBALS['page_active'] = $requestedPage;
$_SESSION['page_active'] = $requestedPage;

$publicPages = ['login', 'register', 'forgot_password'];
if (!in_array($requestedPage, $publicPages, true)) {
    if (
        !isset($_COOKIE['AuthToken'], $_SESSION['auth_token']) ||
        $_COOKIE['AuthToken'] !== $_SESSION['auth_token']
    ) {
        // destroy session + cookie
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        // Clear AuthToken cookie (set to past time)
        setcookie('AuthToken', '', time() - 1, '/');

        header('Location: /mes/index.php?page_active=login');
        exit();
    } else {
        // Refresh cookie expiration to 24 hours from now if still valid
        setcookie('AuthToken', $_SESSION['auth_token'], [
            'expires' => time() + 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}


define('MES_ACCESS', true);

$found = false;
$pageFolder = null;
$file = null;

foreach ($pageMap as $folder => $pages) {
    if ($requestedPage && array_key_exists($requestedPage, $pages)) {
        $file = "pages/{$folder}/{$pages[$requestedPage]}";
        if (file_exists($file)) {
            $pageFolder = $folder;
            $found = true;
            break;
        }
    }
}

if (!$found) {
    header('Location: /mes/index.php?page_active=manage_accounts');
    exit();
}

if ($pageFolder !== 'authentication') {
    include 'components/session.php';
    include 'components/header.php';
}

include $file;

if ($pageFolder !== 'authentication') {
    include 'components/footer.php';
}
