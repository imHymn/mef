<?php
require_once __DIR__ . '/../model/AccountModel.php';

class AccountController
{
    private $accountModel;

    public function __construct()
    {
        $db = new DatabaseClass();
        $this->accountModel = new AccountModel($db);
    }
    public function resetUsers()
    {
        $users = $this->accountModel->getAccounts(); // fetch all users

        foreach ($users as $user) {
            $hashedPassword = password_hash('12345', PASSWORD_DEFAULT);

            $this->accountModel->resetUsers($user['id'], $hashedPassword);
        }

        echo json_encode([
            'success' => true,
            'message' => 'All user passwords have been reset to "12345".'
        ]);
    }

    public function getAccounts()
    {
        $users = $this->accountModel->getAccounts();
        echo json_encode($users);
    }
    public function getAssemblyAccounts()
    {
        $users = $this->accountModel->getAssemblyAccounts();
        echo json_encode($users);
    }
    public function getQCAccounts()
    {
        $users = $this->accountModel->getQCAccounts();
        echo json_encode($users);
    }
    public function getStampingAccounts()
    {
        $users = $this->accountModel->getStampingAccounts();
        echo json_encode($users);
    }
    public function getFinishingAccounts()
    {
        $users = $this->accountModel->getFinishingAccounts();
        echo json_encode($users);
    }
    public function getPaintingAccounts()
    {
        $users = $this->accountModel->getPaintingAccounts();
        echo json_encode($users);
    }
    public function changePassword()
    {
        global $input;

        if (!isset($input['user_id'], $input['currentPassword'], $input['newPassword'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        $userId = trim($input['user_id']);
        $currentPassword = $input['currentPassword'];
        $newPassword = $input['newPassword'];

        try {
            $user = $this->accountModel->getUserByUserId($userId);
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }

            if (!password_verify($currentPassword, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updated = $this->accountModel->updatePassword($userId, $hashedPassword);

            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    }
    public function login()
    {
        $user_id  = $_POST['user_id'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($user_id) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password required.']);
            return;
        }

        $result = $this->accountModel->login($user_id, $password);

        if ($result['success']) {
            session_start();

            $_SESSION['user_id']    = $result['user']['user_id'];
            $_SESSION['username']   = $result['user']['name'];
            $_SESSION['role']       = strtolower($result['user']['role']);
            $_SESSION['department'] = $result['user']['department'] ?? '';

            // ✅ Normalize section (always array of lowercase values)
            $section = [];
            if (!empty($result['user']['section'])) {
                $raw = is_string($result['user']['section'])
                    ? json_decode($result['user']['section'], true)
                    : $result['user']['section'];

                if (is_array($raw)) {
                    $section = array_map('strtolower', $raw);
                }
            }
            $_SESSION['section'] = $section;

            // ✅ Normalize specific_section (always array of lowercase values)
            $specific_section = [];
            if (!empty($result['user']['specific_section'])) {
                $raw = is_string($result['user']['specific_section'])
                    ? json_decode($result['user']['specific_section'], true)
                    : $result['user']['specific_section'];

                if (is_array($raw)) {
                    $specific_section = array_map('strtolower', $raw);
                }
            }
            $_SESSION['specific_section'] = $specific_section;

            // 🔐 auth token
            $_SESSION['auth_token'] = bin2hex(random_bytes(16));
            setcookie('AuthToken', $_SESSION['auth_token'], [
                'expires'  => time() + 86400,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            // 🔹 decide default page
            $role = $_SESSION['role'];
            $page_active = 'manage_accounts'; // fallback

            if ($role === 'administrator' || $role === 'account manager') {
                $page_active = 'manage_accounts';
            } elseif ($role === 'planner') {
                $page_active = 'mef_form';
            } elseif ($role === 'delivery') {
                $page_active = 'for_delivery';
            } elseif ($role === 'fg warehouse') {
                $page_active = 'for_pulling';
            } elseif ($role === 'rm warehouse') {
                $page_active = 'issue_rm';
            } elseif ($role === 'supervisor' || $role === 'line leader') {
                if (in_array('assembly', $section, true)) {
                    $page_active = 'assembly_assign_pi_sku';
                } elseif (in_array('stamping', $section, true)) {
                    $page_active = 'stamping_pi_kbn';
                } elseif (in_array('qc', $section, true)) {
                    $page_active = 'qc_pi_kbn';
                } elseif ($role === 'line leader' && in_array('painting', $section, true)) {
                    $page_active = 'painting_accounts';
                } elseif ($role === 'line leader' && in_array('finishing', $section, true)) {
                    $page_active = 'finishing_accounts';
                }
            }

            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id'          => $_SESSION['user_id'],
                    'username'         => $_SESSION['username'],
                    'role'             => $_SESSION['role'],
                    'department'       => $_SESSION['department'],
                    'section'          => $_SESSION['section'],          // ✅ clean array, no JSON string
                    'specific_section' => $_SESSION['specific_section'],
                    'page_active' => $_SESSION['page_active']  // ✅ clean array, no JSON string
                ],
                'page_active' => $page_active
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Invalid credentials'
            ]);
        }
    }



    public function register()
    {
        global $input;

        $data = [
            'name' => $this->trimOrNull($input['name'] ?? null),
            'user_id' => $this->trimOrNull(isset($input['user_id']) ? preg_replace('/\s+/', '', $input['user_id']) : null),
            'password' => $input['password'] ?? null,
            'department' => $this->trimOrNull($input['department'] ?? null),
            'role' => $this->trimOrNull($input['role'] ?? null),
            'section' => $input['section'] ?? null,
            'specific_section' => $input['specific_section'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Check duplicate user_id
            if ($this->accountModel->getUserByUserId($data['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'User ID already in use.']);
                return;
            }
            $data['section'] = $this->encodeArrayOrNull($data['section']);
            $data['specific_section'] = $this->encodeArrayOrNull($data['specific_section']);

            // Insert
            $inserted = $this->accountModel->createUser($data);

            if ($inserted) {
                echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Insert failed.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    public function updateAccount()
    {
        global $input;

        $id = $input['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing account ID.']);
            return;
        }

        $data = [
            'name' => $this->trimOrNull($input['name'] ?? null),
            'user_id' => $this->trimOrNull(isset($input['user_id']) ? preg_replace('/\s+/', '', $input['user_id']) : null),
            'password' => $input['password'] ?? null, // nullable → only update if provided
            'department' => $this->trimOrNull($input['department'] ?? null),
            'role' => $this->trimOrNull($input['role'] ?? null),
            'section' => $input['section'] ?? null,
            'specific_section' => $input['specific_section'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            // ✅ Check if user exists
            $existing = $this->accountModel->getUserById($id);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Account not found.']);
                return;
            }

            // ✅ Prevent duplicate user_id (exclude current user)
            $otherUser = $this->accountModel->getUserByUserId($data['user_id']);
            if ($otherUser && $otherUser['id'] != $id) {
                echo json_encode(['success' => false, 'message' => 'User ID already in use.']);
                return;
            }

            // ✅ Encode arrays
            $data['section'] = $this->encodeArrayOrNull($data['section']);
            $data['specific_section'] = $this->encodeArrayOrNull($data['specific_section']);

            // ✅ If password is empty, don’t update it
            // ✅ If password is empty, don’t update it
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // ✅ Run update
            $updated = $this->accountModel->updateUser($id, $data);

            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'Account updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    public function deleteAccount()
    {
        global $input;

        $id = $input['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing account ID.']);
            return;
        }

        try {
            // ✅ Check if account exists
            $existing = $this->accountModel->getUserById($id);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Account not found.']);
                return;
            }

            // ✅ Perform delete
            $deleted = $this->accountModel->deleteUser($id);

            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function updateQRGenerated()
    {
        // Make sure we have the input data
        global $input;

        try {
            // ✅ use the property, not $this->$accountModel
            $this->accountModel->updateQRGenerated($input['user_id'], $input['generated_at']);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
        }
    }

    private function trimOrNull($value)
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }
        return null;
    }


    private function encodeArrayOrNull($value): ?string
    {
        if (is_array($value)) {
            return count($value) > 0 ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
        } elseif (is_string($value) && $value !== '') {
            return json_encode([$value], JSON_UNESCAPED_UNICODE);
        }
        return null;
    }
}
