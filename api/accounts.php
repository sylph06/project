<?php
/* =========================================================
   api/accounts.php — member account create, link, reset, deactivate
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

if (!in_array($action, ['accounts_unlinked', 'account_link', 'account_create', 'account_reset', 'account_deactivate'], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage member accounts.'], 403);
$pdo = db();
if ($action === 'accounts_unlinked') {
    $rows = $pdo->query('SELECT id, username, full_name, role, is_active FROM users WHERE legacy_id IS NULL ORDER BY full_name')
        ->fetchAll(PDO::FETCH_ASSOC);
    $linked = $pdo->query('SELECT legacy_id FROM users WHERE legacy_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    out(['ok' => true, 'accounts' => $rows, 'linkedLegacyIds' => $linked]);
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
        $account = $pdo->prepare('SELECT id, username, legacy_id, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
        $account->execute([$accountId]);
        $target = $account->fetch(PDO::FETCH_ASSOC);
        if (!$target || $target['legacy_id'] !== null || !(int)$target['is_active']) {
            throw new RuntimeException('That account is already linked or inactive.');
        }
        $pdo->prepare('UPDATE users SET legacy_id = ? WHERE id = ?')->execute([$legacyId, $accountId]);
        sk_audit($pdo, $user, $legacyId, 'account_linked', ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => null],
            ['account_id' => $accountId, 'username' => $target['username'], 'legacy_id' => $legacyId]);
        $pdo->commit();
        out(['ok' => true]);
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
        $temporaryPassword = bin2hex(random_bytes(12));
        $name = trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return (string)$part !== ''; })));
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
            'account_id' => $newId, 'username' => $username, 'must_change_password' => true,
            'guardian_consent' => $consent, 'consent_version' => $version,
        ]);
        $pdo->commit();
        out(['ok' => true, 'accountId' => $newId, 'temporaryPassword' => $temporaryPassword]);
    }

    $targetId = filter_var($in['accountId'] ?? null, FILTER_VALIDATE_INT);
    if (!$targetId) throw new RuntimeException('A valid account id is required.');
    $account = $pdo->prepare('SELECT id, username, legacy_id, is_active, must_change_password FROM users WHERE id = ? FOR UPDATE');
    $account->execute([$targetId]);
    $target = $account->fetch(PDO::FETCH_ASSOC);
    if (!$target) throw new RuntimeException('The account no longer exists.');
    if ($action === 'account_reset') {
        $temporaryPassword = bin2hex(random_bytes(12));
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?')
            ->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $targetId]);
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

