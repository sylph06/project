<?php
/* =========================================================
   SHARED SETTINGS  (every .php page loads this first)
   - change the 4 database values below if your MySQL differs
     (XAMPP default: user "root", empty password)
   ========================================================= */
const DB_HOST = 'localhost';
const DB_NAME = 'kk';
const DB_USER = 'root';
const DB_PASS = '';
const USE_MEMBERS_TABLE = false;
const USE_SK_ROLES_TABLE = false;
const APP_WRITE_ROLES = ['SK Chairman', 'SK Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad', 'SK Vice Chairperson'];
const MEMBER_DATA_ADMIN_ROLES = ['SK Chairman', 'SK Secretary'];
const BACKGROUND_HISTORY_ROLES = ['SK Chairman', 'SK Secretary'];

date_default_timezone_set('Asia/Manila');   // so dates match the barangay
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function kk_effective_role($account) {
    if ($account['legacy_id'] === null) return (string)$account['role'];

    $memberStatus = null;
    if (USE_MEMBERS_TABLE) {
        $member = db()->prepare('SELECT status FROM members WHERE legacy_id = ?');
        $member->execute([$account['legacy_id']]);
        $memberStatus = $member->fetchColumn();
    } else {
        $raw = db()->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers'")->fetchColumn();
        if ($raw === false) throw new RuntimeException('KK member data is missing.');
        $members = json_decode($raw, true);
        if (!is_array($members)) throw new RuntimeException('KK member data is invalid.');
        foreach ($members as $member) {
            if (!is_array($member)) throw new RuntimeException('A KK member record is invalid.');
            if ((string)($member['id'] ?? '') === (string)$account['legacy_id']) {
                $memberStatus = $member['status'] ?? 'Active';
                break;
            }
        }
    }
    if ($memberStatus === false || $memberStatus === null || $memberStatus === 'Archived') return 'KK Member';

    if (USE_SK_ROLES_TABLE) {
        $st = db()->prepare("SELECT position FROM sk_roles
            WHERE kk_id = ? AND status = 'Active'
              AND (term_start IS NULL OR term_start <= ?)
              AND (term_end IS NULL OR term_end >= ?)");
        $today = date('Y-m-d');
        $st->execute([$account['legacy_id'], $today, $today]);
        $positions = $st->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $raw = db()->query("SELECT data_value FROM app_data WHERE data_key = 'skRoles'")->fetchColumn();
        if ($raw === false) throw new RuntimeException('SK role data is missing.');
        $roles = json_decode($raw, true);
        if (!is_array($roles)) throw new RuntimeException('SK role data is invalid.');
        $today = date('Y-m-d');
        $positions = [];
        foreach ($roles as $role) {
            if (!is_array($role)) throw new RuntimeException('An SK role record is invalid.');
            if ((string)($role['kkId'] ?? '') !== (string)$account['legacy_id']
                || ($role['status'] ?? '') !== 'Active'
                || (!empty($role['termStart']) && $role['termStart'] > $today)
                || (!empty($role['termEnd']) && $role['termEnd'] < $today)) continue;
            $positions[] = $role['position'] ?? '';
        }
    }

    $rank = [
        'SK Chairman' => 5,
        'SK Secretary' => 4,
        'SK Treasurer' => 3,
        'SK Kagawad' => 2,
        'SK Vice Chairperson' => 1,
    ];
    $mapped = [];
    foreach ($positions as $position) {
        if ($position === 'SK Chairperson' || $position === 'SK Chairman') $mapped[] = 'SK Chairman';
        elseif (isset($rank[$position])) $mapped[] = $position;
        else throw new RuntimeException('An active SK role has an unsupported position.');
    }
    usort($mapped, function ($left, $right) use ($rank) { return $rank[$right] <=> $rank[$left]; });
    return $mapped[0] ?? 'KK Member';
}

/* Resolve identity and permissions from the database once per request; session roles are never authoritative. */
function current_user() {
    static $resolved = false;
    static $user = null;
    if ($resolved) return $user;
    $resolved = true;
    $sessionUser = $_SESSION['user'] ?? null;
    if (!$sessionUser || empty($sessionUser['id'])) return null;

    $st = db()->prepare('SELECT id, username, full_name, role, is_active, legacy_id, must_change_password
                         FROM users WHERE id = ?');
    $st->execute([(int)$sessionUser['id']]);
    $account = $st->fetch(PDO::FETCH_ASSOC);
    if (!$account || !(int)$account['is_active']) {
        unset($_SESSION['user']);
        return null;
    }
    $user = [
        'id' => (int)$account['id'],
        'username' => $account['username'],
        'name' => $account['full_name'],
        'role' => kk_effective_role($account),
        'legacy_id' => $account['legacy_id'],
        'must_change_password' => (bool)$account['must_change_password'],
    ];
    $_SESSION['user'] = ['id' => $user['id']];
    return $user;
}

function can_write_app_data() {
    $user = current_user();
    return $user && in_array($user['role'] ?? '', APP_WRITE_ROLES, true);
}

function can_manage_sk_roles() {
    $user = current_user();
    return $user && in_array($user['role'] ?? '', ['SK Chairman', 'SK Secretary'], true);
}

function can_view_background_history() {
    $user = current_user();
    return $user && in_array($user['role'] ?? '', BACKGROUND_HISTORY_ROLES, true);
}

/* a visitor who clicked "Continue as Public Viewer" (view only) */
function is_public() { return !current_user() && !empty($_SESSION['public']); }

/* KK accounts without an active SK position have the same limited page access as public viewers. */
function is_limited_member() {
    $user = current_user();
    return $user && ($user['role'] ?? '') === 'KK Member';
}

function is_limited_viewer() {
    return is_public() || is_limited_member();
}

function limited_viewer_can_open_page($page) {
    return in_array(basename((string)$page), ['index.php', 'announcements.php', 'officials.php'], true);
}

/* Limited viewers can open the public landing page, announcements, and officials only. */
function require_viewer() {
    $user = current_user();
    if ($user && !empty($user['must_change_password'])) {
        header('Location: change-password.php');
        exit;
    }
    if (($_GET['view'] ?? '') === 'public') $_SESSION['public'] = true;
    if ($user && !is_limited_member()) return;
    if (is_limited_viewer()) {
        if (limited_viewer_can_open_page($_SERVER['SCRIPT_NAME'] ?? '')) return;
        header('Location: announcements.php');
        exit;
    }
    header('Location: login.php'); exit;
}

/* pages that anyone may open with no login and no "public viewer" click */
function open_to_everyone() {
    if (!current_user() && empty($_SESSION['public'])) $_SESSION['public'] = true;
}

/* officer-only pages: public viewers are sent back to the dashboard */
function require_official() {
    $user = current_user();
    if ($user && !empty($user['must_change_password'])) {
        header('Location: change-password.php');
        exit;
    }
    if ($user && !is_limited_member()) return;
    if (is_limited_viewer()) {
        header('Location: announcements.php');
        exit;
    }
    header('Location: ' . (is_public() ? 'dashboard.php' : 'login.php')); exit;
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function uname() { $u = current_user(); return $u ? $u['name'] : (is_public() ? 'Public Viewer' : ''); }
function urole() { $u = current_user(); return $u ? $u['role'] : (is_public() ? 'View Only' : ''); }
function uinit() {
    if (is_public()) return 'PV';
    $parts = preg_split('/\s+/', trim(uname()));
    $i = '';
    foreach ($parts as $p) { if ($p !== '' && strlen($i) < 2) $i .= mb_strtoupper(mb_substr($p, 0, 1)); }
    return $i === '' ? '?' : $i;
}