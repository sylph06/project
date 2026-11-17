<?php
/* =========================================================
   api.php — Router
   Dispatches each action to its dedicated module file under api/.
   This follows the Separation of Concerns (SoC) principle:
   each module handles one domain of the system independently.

   Modules:
     api/auth.php         → login, password
     api/registration.php → register (retired public-registration endpoint)
     api/events.php       → event_register, event_attendance, event_walkin
     api/meetings.php     → meeting_minutes_save/approve/reopen
     api/roles.php        → role_assign/update/end/archive/archive_expired
     api/accounts.php     → member account management and username recovery
     api/members.php      → member_add/update/delete/restore/register
     api/member-portal.php → member portal, KK ID, QR verification
     api/members.php      → member_data_save (atomic JSON-store updates)
     api/storage.php      → set, remove (generic key-value storage)
   ========================================================= */

$action = $_GET['action'] ?? '';

/* Map each action to its module file */
$routes = [
    /* Authentication */
    'login'                   => 'api/auth.php',
    'password'                => 'api/auth.php',

    /* Retired public registration */
    'register'                => 'api/registration.php',

    /* Events */
    'event_register'          => 'api/events.php',
    'event_attendance'        => 'api/events.php',
    'event_walkin'            => 'api/events.php',

    /* Meeting minutes */
    'meeting_minutes_save'    => 'api/meetings.php',
    'meeting_minutes_approve' => 'api/meetings.php',
    'meeting_minutes_reopen'  => 'api/meetings.php',
    'meeting_attendance_save' => 'api/meetings.php',
    'meeting_attendance_delete' => 'api/meetings.php',

    /* SK roles */
    'role_assign'             => 'api/roles.php',
    'role_update'             => 'api/roles.php',
    'role_end'                => 'api/roles.php',
    'role_archive'            => 'api/roles.php',
    'role_archive_expired'    => 'api/roles.php',
    'committee_save'          => 'api/committees.php',
    'committee_chairs_save'   => 'api/committee_chairs.php',

    /* Member accounts */
    'accounts_unlinked'       => 'api/accounts.php',
    'account_link'            => 'api/accounts.php',
    'account_create'          => 'api/accounts.php',
    'account_reset'           => 'api/accounts.php',
    'account_reset_member'    => 'api/accounts.php',
    'account_username_member' => 'api/accounts.php',
    'account_deactivate'      => 'api/accounts.php',

    /* KK members (members table) */
    'member_add'              => 'api/members.php',
    'member_update'           => 'api/members.php',
    'member_delete'           => 'api/members.php',
    'member_restore'          => 'api/members.php',
    'member_register'         => 'api/members.php',
    'member_data_save'        => 'api/members.php',

    /* Member portal and KK ID */
    'member_portal_data'       => 'api/member-portal.php',
    'member_portal_photo'      => 'api/member-portal.php',
    'kk_id_token'              => 'api/member-portal.php',
    'kk_id_print'              => 'api/member-portal.php',
    'kk_id_prints'             => 'api/member-portal.php',
    'kk_id_scan'               => 'api/member-portal.php',

    /* Generic storage */
    'set'                     => 'api/storage.php',
    'remove'                  => 'api/storage.php',
];

if (isset($routes[$action])) {
    require __DIR__ . '/' . $routes[$action];
    exit;
}

/* Unknown action */
header('Content-Type: application/json; charset=utf-8');
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
