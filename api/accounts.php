<?php
/* =========================================================
   api/accounts.php — member account management and username recovery
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../member_account_logic.php';
require_once __DIR__ . '/../google_auth.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

if (!in_array($action, [
    'accounts_unlinked', 'account_link',
    'account_create', 'account_reset', 'account_reset_member', 'account_reset_official',
    'account_username_member', 'account_deactivate',
    'account_google_unbind', 'account_google_clear_email',
], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

if (in_array($action, ['account_google_unbind', 'account_google_clear_email'], true)) {
    if (!can('unbind_google')) {
        out(['ok' => false, 'error' => 'You do not have permission to unbind Google accounts.'], 403);
    }
} elseif ($action === 'account_reset_member') {
    if (!can('reset_member_password')) {
        out(['ok' => false, 'error' => 'You do not have permission to reset KK member passwords.'], 403);
    }
} elseif (!can_manage_sk_roles()) {
    out(['ok' => false, 'error' => 'Only the SK Chairperson or SK Secretary can manage member accounts.'], 403);
}
$pdo = db();
if ($action === 'account_reset_member') {
    $noticeTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_password_reset_notices'");
    $noticeTable->execute();
    if ((int)$noticeTable->fetchColumn() !== 1) {
        out(['ok' => false, 'error' => 'Delegated password reset is not configured. Run the required database migration.'], 503);
    }
}
if ($action === 'accounts_unlinked') {
    $rows = $pdo->query('SELECT id, username, full_name, role, is_active FROM users WHERE legacy_id IS NULL ORDER BY full_name')
        ->fetchAll(PDO::FETCH_ASSOC);
    $linked = $pdo->query('SELECT legacy_id FROM users WHERE legacy_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    out(['ok' => true, 'accounts' => $rows, 'linkedLegacyIds' => $linked]);
}
if (in_array($action, ['account_google_unbind', 'account_google_clear_email'], true)
    && !google_oauth_schema_ready($pdo)) {
    out(['ok' => false, 'error' => 'Google account recovery is not configured.'], 503);
}
if (in_array($action, ['account_google_unbind', 'account_google_clear_email'], true)) {
    $googleUnbindReason = trim((string)($in['reason'] ?? ''));
    if (mb_strlen($googleUnbindReason) < 5 || mb_strlen($googleUnbindReason) > 240) {
        out(['ok' => false, 'error' => 'Enter a reason between 5 and 240 characters.'], 400);
    }
}
$pdo->beginTransaction();
try {
    if ($action === 'account_link') {
        $accountId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
        $legacyId = (string)($in['legacyId'] ?? '');
        $member = $legacyId === '' ? null : sk_member_for_role_change($pdo, $legacyId, true);
        if (!$accountId || !$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Choose an active KK member and a valid unlinked account.');
        }
        $account = $pdo->prepare('SELECT id, username, legacy_id, role, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
        $account->execute([$accountId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target || $target['legacy_id'] !== null || !(int)$target['is_active']) {
            throw new RuntimeException('That account is already linked or inactive.');
        }
        if (kk_effective_role($target) !== 'KK Member') {
            throw new RuntimeException('Only a KK member login can be linked to a KK member profile.');
        }
        $pdo->prepare('UPDATE users SET legacy_id = ? WHERE id = ?')->execute([$legacyId, $accountId]);
        sk_audit($pdo, $user, $legacyId, 'account_linked', ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => null],
            ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => $legacyId]);
        $pdo->commit();
        out(['ok' => true]);
    }

    if ($action === 'account_google_unbind' || $action === 'account_google_clear_email') {
        $legacyId = trim((string)($in['legacyId'] ?? ''));
        if ($legacyId === '') throw new RuntimeException('A valid KK member profile is required.');

        $accountsQuery = $pdo->prepare('SELECT id FROM users WHERE legacy_id = ? ORDER BY id FOR UPDATE');
        $accountsQuery->execute([$legacyId]);
        $accountIds = array_map('intval', $accountsQuery->fetchAll(PDO::FETCH_COLUMN));

        $members = google_auth_load_members($pdo, true);
        $memberIndex = null;
        foreach ($members as $index => $candidate) {
            if (is_array($candidate) && (string)($candidate['id'] ?? '') === $legacyId) {
                $memberIndex = $index;
                break;
            }
        }
        if ($memberIndex === null) throw new RuntimeException('The KK member profile could not be found.');
        $member = $members[$memberIndex];
        $targetAccountQuery = $pdo->prepare('SELECT id, username, full_name, role, is_active, legacy_id
            FROM users WHERE legacy_id = ? AND is_active = 1 ORDER BY id LIMIT 1 FOR UPDATE');
        $targetAccountQuery->execute([$legacyId]);
        $targetAccount = $targetAccountQuery->fetch(PDO::FETCH_ASSOC);
        if (is_array($GLOBALS['SK_ACTIVE_DELEGATION'] ?? null)) {
            if (!$targetAccount || kk_effective_role($targetAccount) !== 'KK Member') {
                throw new RuntimeException('Delegated Google unbinding is available only for KK member accounts.');
            }
        }
        $oldProfileEmail = trim((string)($member['email'] ?? ''));

        $links = [];
        foreach ($accountIds as $accountId) {
            $linkQuery = $pdo->prepare('SELECT google_email FROM google_account_links WHERE user_id = ? FOR UPDATE');
            $linkQuery->execute([$accountId]);
            $linkedEmail = $linkQuery->fetchColumn();
            if ($linkedEmail !== false) {
                $links[] = ['user_id' => $accountId, 'google_email' => (string)$linkedEmail];
            }
        }
        if ($action === 'account_google_unbind' && !$links) {
            throw new RuntimeException('This KK member has no bound Google account.');
        }
        if ($action === 'account_google_clear_email' && !$links && $oldProfileEmail === '') {
            throw new RuntimeException('This KK member has no profile email or bound Google account to clear.');
        }

        foreach ($links as $link) {
            $pdo->prepare('DELETE FROM google_account_links WHERE user_id = ?')->execute([$link['user_id']]);
        }
        if ($accountIds) {
            $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
            $pdo->prepare("UPDATE users SET google_sub = NULL WHERE id IN ($placeholders)")
                ->execute($accountIds);
        }
        if ($links) {
            sk_audit($pdo, $user, $legacyId, 'google_account_unbound', $links, [
                'unbound_count' => count($links),
                'profile_email_preserved' => $action === 'account_google_unbind',
                'reason' => $googleUnbindReason,
            ]);
        }
        if ($action === 'account_google_clear_email' && $oldProfileEmail !== '') {
            $member['email'] = '';
            $members[$memberIndex] = $member;
            google_auth_save_member($pdo, $member, $user['username'], $members);
            sk_audit($pdo, $user, $legacyId, 'google_profile_email_cleared', [
                'email' => $oldProfileEmail,
            ], [
                'email' => null,
                'google_links_removed' => count($links),
                'reason' => $googleUnbindReason,
            ]);
        }
        $pdo->commit();
        out(['ok' => true, 'unboundCount' => count($links), 'profileEmailCleared' => $action === 'account_google_clear_email' && $oldProfileEmail !== '']);
    }

    if ($action === 'account_create') {
        $legacyId = (string)($in['legacyId'] ?? '');
        $member = $legacyId === '' ? null : sk_member_for_role_change($pdo, $legacyId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can receive an account.');
        }
        $username = trim((string)($in['username'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {
            throw new RuntimeException('Username must be 4 to 50 letters, numbers, dots, underscores, or hyphens.');
        }
        $consent = !empty($in['guardianConsent']);
        $birthDate = (string)($member['birthDate'] ?? '');
        if ($birthDate !== '') {
            $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
            if ($birth && $birth->diff(new DateTimeImmutable('today'))->y < 18 && !$consent) {
                throw new RuntimeException('Guardian consent is required to create an account for a member under 18.');
            }
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE legacy_id = ? OR username = ? FOR UPDATE');
        $exists->execute([$legacyId, $username]);
        if ($exists->fetchColumn() !== false) throw new RuntimeException('A member can have only one account, and usernames must be unique.');
        $name = trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return (string)$part !== ''; })));
        $temporaryPassword = kk_member_temporary_password($member['lastName'] ?? '');
        $version = $consent ? trim((string)($in['consentVersion'] ?? '')) : null;
        if ($consent && $version === '') throw new RuntimeException('Consent version is required when guardian consent is recorded.');
        $pdo->prepare('INSERT INTO users
            (username, password, full_name, role, is_active, legacy_id, must_change_password,
             guardian_consent, consent_at, consent_version, created_by)
            VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?)')
            ->execute([
                $username, password_hash($temporaryPassword, PASSWORD_DEFAULT), $name, 'KK Member', $legacyId,
                $consent ? 1 : 0, $consent ? date('Y-m-d H:i:s') : null, $version, $user['id'],
            ]);
        $newId = (int)$pdo->lastInsertId();
        sk_audit($pdo, $user, $legacyId, 'account_created', null, [
            'account_id' => $newId, 'username' => $username, 'google_sign_in_required' => true,
            'guardian_consent' => $consent, 'consent_version' => $version,
        ]);
        $pdo->commit();
        out([
            'ok' => true,
            'accountId' => $newId,
            'googleSignInRequired' => GOOGLE_KK_LOGIN_REQUIRED,
            'temporaryPassword' => $temporaryPassword,
        ]);
    }

    $member = null;
    if ($action === 'account_username_member') {
        $legacyId = trim((string)($in['legacyId'] ?? ''));
        if ($legacyId === '') throw new RuntimeException('A valid KK member profile is required.');
        $member = sk_member_for_role_change($pdo, $legacyId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can have a login username retrieved.');
        }
        $account = $pdo->prepare('SELECT id, username FROM users WHERE legacy_id = ? AND is_active = 1 FOR UPDATE');
        $account->execute([$legacyId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('This member has no active login account.');
        sk_audit($pdo, $user, $legacyId, 'account_username_retrieved', null, [
            'account_id' => (int)$target['id'],
        ]);
        $pdo->commit();
        out(['ok' => true, 'username' => $target['username']]);
    }

    if ($action === 'account_reset_member' || $action === 'account_reset_official') {
        $legacyId = trim((string)($in['legacyId'] ?? ''));
        if ($legacyId === '') throw new RuntimeException('A valid KK member profile is required.');
        $member = sk_member_for_role_change($pdo, $legacyId, true);
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only an active KK member can have a login password reset.');
        }
        $account = $pdo->prepare('SELECT id, username, legacy_id, role, is_active, must_change_password
            FROM users WHERE legacy_id = ? AND is_active = 1 FOR UPDATE');
        $account->execute([$legacyId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('This member has no active login account to reset.');
        $targetRole = kk_effective_role($target);
        if ($action === 'account_reset_member' && $targetRole !== 'KK Member') {
            throw new RuntimeException('This reset action is limited to KK member accounts, not SK officials.');
        }
        if ($action === 'account_reset_official'
            && !in_array($targetRole, ['SK Chairman', 'SK Chairperson', 'SK Secretary'], true)) {
            throw new RuntimeException('This reset action is limited to SK official accounts.');
        }
        $targetId = (int)$target['id'];
    } else {
        $targetId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
        if (!$targetId) throw new RuntimeException('A valid account id is required.');
        $account = $pdo->prepare('SELECT id, username, legacy_id, role, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
        $account->execute([$targetId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new RuntimeException('The account no longer exists.');
    }
    if (in_array($action, ['account_reset', 'account_reset_member', 'account_reset_official'], true)) {
        if ($member === null && $target['legacy_id'] !== null) {
            $member = sk_member_for_role_change($pdo, (string)$target['legacy_id'], true);
        }
        $targetRole = kk_effective_role($target);
        if ($action === 'account_reset' || $action === 'account_reset_official') {
            $actorRole = (string)($user['role'] ?? '');
            $isChair = in_array($actorRole, ['SK Chairman', 'SK Chairperson'], true);
            $isSecretary = $actorRole === 'SK Secretary';
            $targetIsChair = in_array($targetRole, ['SK Chairman', 'SK Chairperson'], true);
            $targetIsSecretary = $targetRole === 'SK Secretary';
            if (($targetIsChair || $targetIsSecretary)
                && !(($isChair && $targetIsSecretary) || ($isSecretary && $targetIsChair))) {
                throw new RuntimeException('An official account can only be reset by the other SK Chairperson or Secretary.');
            }
            if ((int)$target['id'] === (int)$user['id']) {
                throw new RuntimeException('You cannot reset your own account through this action.');
            }
        }
        $googleLoginStatus = in_array($action, ['account_reset_member', 'account_reset_official'], true)
            ? 'allowed'
            : google_auth_password_login_status($pdo, $target);
        if ($googleLoginStatus !== 'allowed') {
            throw new RuntimeException($googleLoginStatus === 'setup_missing'
                ? 'Google sign-in is required for KK members, but its database setup is incomplete.'
                : 'This KK account uses Google sign-in. The member can sign in with Google and set a new password in Settings.');
        }
        $temporaryPassword = bin2hex(random_bytes(12));
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?')
            ->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $targetId]);
        if ($action === 'account_reset_member') {
            $resetBy = trim((string)($user['name'] ?? ''));
            if ($resetBy === '') $resetBy = (string)$user['username'];
            $pdo->prepare('INSERT INTO user_password_reset_notices (user_id, reset_by_name, reset_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE reset_by_name = VALUES(reset_by_name), reset_at = VALUES(reset_at)')
                ->execute([$targetId, mb_substr($resetBy, 0, 120)]);
        }
        sk_audit($pdo, $user, $target['legacy_id'], 'account_password_reset', ['must_change_password' => (bool)$target['must_change_password']],
            ['account_id' => $targetId, 'username' => $target['username'], 'must_change_password' => true]);
        $pdo->commit();
        out(['ok' => true, 'temporaryPassword' => $temporaryPassword]);
    }
    if ($targetId === $user['id']) throw new RuntimeException('You cannot deactivate your own account.');
    $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$targetId]);
    sk_audit($pdo, $user, $target['legacy_id'], 'account_deactivated',
        ['account_id' => $targetId, 'is_active' => (bool)$target['is_active']],
        ['account_id' => $targetId, 'is_active' => false]);
    $pdo->commit();
    out(['ok' => true]);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($ex instanceof PDOException && (string)$ex->getCode() === '23000') {
        out(['ok' => false, 'error' => 'This account or profile is already linked.'], 409);
    }
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Member account operation failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not update the member account.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Member account operation failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not update the member account.'], 500);
}

