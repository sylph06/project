<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/members_table.php';
require_once __DIR__ . '/role_store.php';

function google_oauth_configured() {
    if (GOOGLE_OAUTH_CLIENT_ID === '' || GOOGLE_OAUTH_CLIENT_SECRET === '') return false;
    $uri = parse_url(GOOGLE_OAUTH_REDIRECT_URI);
    if (!is_array($uri) || empty($uri['scheme']) || empty($uri['host'])
        || empty($uri['path']) || isset($uri['user']) || isset($uri['pass'])
        || isset($uri['query']) || isset($uri['fragment'])) return false;
    $scheme = strtolower((string)$uri['scheme']);
    $host = strtolower((string)$uri['host']);
    if ($scheme === 'https') return true;
    return APP_ENV !== 'production'
        && $scheme === 'http'
        && in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function google_oauth_schema_ready($pdo = null) {
    $pdo = $pdo ?: db();
    $statement = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'google_account_links')
            + (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_sub')");
    $statement->execute();
    return (int)$statement->fetchColumn() === 2;
}

function google_oauth_link_requests_schema_ready($pdo = null) {
    $pdo = $pdo ?: db();
    $statement = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'google_account_link_requests'");
    $statement->execute();
    return (int)$statement->fetchColumn() === 1;
}

function google_auth_normalize_email($email) {
    if (!is_string($email)) return null;
    $email = strtolower(trim($email));
    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
    $domain = substr(strrchr($email, '@'), 1);
    $blockedDomains = ['example.com', 'example.net', 'example.org', 'test.com', 'localhost'];
    if (in_array($domain, $blockedDomains, true)) return null;
    foreach (['.example', '.invalid', '.localhost', '.test'] as $suffix) {
        if (substr($domain, -strlen($suffix)) === $suffix) return null;
    }
    return $email;
}

function google_auth_match_member_email($members, $email) {
    $normalizedEmail = google_auth_normalize_email($email);
    if ($normalizedEmail === null) return ['status' => 'invalid_email'];
    if (!is_array($members)) return ['status' => 'invalid_member_data'];

    $matches = [];
    foreach ($members as $member) {
        if (!is_array($member)) return ['status' => 'invalid_member_data'];
        if (google_auth_normalize_email($member['email'] ?? null) === $normalizedEmail) {
            $matches[] = $member;
        }
    }
    if (!$matches) return ['status' => 'not_found'];
    if (count($matches) !== 1) return ['status' => 'ambiguous'];
    if (($matches[0]['status'] ?? 'Active') !== 'Active') return ['status' => 'inactive'];
    return ['status' => 'matched', 'member' => $matches[0]];
}

function google_auth_load_members($pdo, $lock = false) {
    if (USE_MEMBERS_TABLE) {
        if (!$lock) return kk_members_array_from_table($pdo);
        $rows = $pdo->query('SELECT * FROM members ORDER BY id FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC);
        $members = [];
        foreach ($rows as $row) {
            $members[] = json_decode(json_encode(
                kk_member_row_to_object($row),
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            ), true, 512, JSON_THROW_ON_ERROR);
        }
        return $members;
    }

    $statement = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers'"
        . ($lock ? ' FOR UPDATE' : ''));
    $raw = $statement->fetchColumn();
    if ($raw === false) throw new RuntimeException('KK member data is missing.');
    $members = json_decode((string)$raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($members)) {
        throw new RuntimeException('KK member data is invalid.');
    }
    return $members;
}

function google_auth_save_member($pdo, $member, $updatedBy, $members = null) {
    if (!is_array($member) || !isset($member['id'])) {
        throw new RuntimeException('The KK member profile is invalid.');
    }
    if (USE_MEMBERS_TABLE) {
        return kk_member_upsert($pdo, $member, $updatedBy);
    }

    $members = is_array($members) ? $members : google_auth_load_members($pdo, true);
    $found = false;
    foreach ($members as $index => $existingMember) {
        if (is_array($existingMember) && (string)($existingMember['id'] ?? '') === (string)$member['id']) {
            $members[$index] = $member;
            $found = true;
            break;
        }
    }
    if (!$found) throw new RuntimeException('The KK member profile could not be found.');

    $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'kkMembers'")
        ->execute([
            json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
            $updatedBy,
        ]);
    return 'updated';
}

function google_auth_changed_email_is_unique($members, $memberId, $email) {
    $normalizedEmail = google_auth_normalize_email($email);
    if ($normalizedEmail === null) return false;
    foreach ($members as $member) {
        if (!is_array($member) || (string)($member['id'] ?? '') === (string)$memberId) continue;
        if (google_auth_normalize_email($member['email'] ?? null) === $normalizedEmail) return false;
    }
    return true;
}

function google_auth_profile_email_binding_state($profileEmail, $googleEmail) {
    $normalizedGoogleEmail = google_auth_normalize_email($googleEmail);
    if ($normalizedGoogleEmail === null) return 'invalid_google_email';
    $profileEmail = is_string($profileEmail) ? trim($profileEmail) : '';
    if ($profileEmail === '') return 'set_profile_email';
    return google_auth_normalize_email($profileEmail) === $normalizedGoogleEmail
        ? 'already_matches'
        : 'replace_profile_email';
}

function google_auth_member_email_matches($members, $memberId, $email) {
    $normalizedEmail = google_auth_normalize_email($email);
    if ($normalizedEmail === null || !is_array($members)) return false;
    foreach ($members as $member) {
        if (!is_array($member)) return false;
        if ((string)($member['id'] ?? '') !== (string)$memberId) continue;
        return ($member['status'] ?? 'Active') === 'Active'
            && google_auth_normalize_email($member['email'] ?? null) === $normalizedEmail
            && google_auth_changed_email_is_unique($members, $memberId, $normalizedEmail);
    }
    return false;
}

function google_auth_account_is_member_linked($account) {
    return is_array($account)
        && isset($account['legacy_id'])
        && $account['legacy_id'] !== null
        && (string)$account['legacy_id'] !== '';
}

function google_auth_registration_ticket(
    $storedTicket,
    $submittedToken,
    $submittedEmail = null,
    $expectedPurpose = 'registration',
    $expectedMemberId = null
) {
    if (!is_array($storedTicket) || !is_string($submittedToken) || $submittedToken === ''
        || !is_string($storedTicket['token'] ?? null) || !is_string($storedTicket['subject'] ?? null)
        || $storedTicket['subject'] === '' || strlen($storedTicket['subject']) > 255
        || !is_string($storedTicket['email'] ?? null) || !is_int($storedTicket['expires_at'] ?? null)
        || ($storedTicket['purpose'] ?? 'registration') !== $expectedPurpose
        || ($expectedMemberId !== null && (string)($storedTicket['member_id'] ?? '') !== (string)$expectedMemberId)) {
        return null;
    }
    if ($storedTicket['expires_at'] < time()
        || !hash_equals($storedTicket['token'], $submittedToken)
        || google_auth_normalize_email($storedTicket['email']) === null
        || ($submittedEmail !== null && google_auth_normalize_email($submittedEmail) !== $storedTicket['email'])) {
        return null;
    }
    return [
        'subject' => $storedTicket['subject'],
        'email' => $storedTicket['email'],
        'purpose' => $expectedPurpose,
        'member_id' => $expectedMemberId === null ? null : (string)$expectedMemberId,
    ];
}

function google_registration_popup_result($ok, $message, $ticket = null, $email = null, $purpose = null, $memberId = null) {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $result = [
        'type' => 'kk-google-registration',
        'ok' => (bool)$ok,
        'message' => (string)$message,
        'ticket' => $ticket,
        'email' => $email,
        'purpose' => $purpose,
        'memberId' => $memberId,
    ];
    $json = json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
        . '<title>Bind Google account</title><body><p>' . htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8')
        . '</p><p>Return to Settings to finish binding your account.</p>'
        . '<script>if (window.opener && !window.opener.closed) { window.opener.postMessage('
        . $json . ', window.location.origin); window.close(); }</script></body></html>';
    exit;
}

function google_auth_password_login_status($pdo, $account) {
    if (!google_auth_account_is_member_linked($account)) return 'allowed';
    if (kk_effective_role($account) !== 'KK Member') return 'allowed';
    if (!google_oauth_schema_ready($pdo)) {
        return GOOGLE_KK_LOGIN_REQUIRED ? 'setup_missing' : 'allowed';
    }
    if (GOOGLE_KK_LOGIN_REQUIRED) return 'google_required';

    $statement = $pdo->prepare('SELECT 1 FROM google_account_links WHERE user_id = ?');
    $statement->execute([(int)$account['id']]);
    return $statement->fetchColumn() ? 'google_required' : 'allowed';
}

function google_oauth_request_json($url, $method, $fields = null, $accessToken = null) {
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL is required for Google sign-in.');
    $headers = ['Accept: application/json'];
    if ($accessToken !== null) $headers[] = 'Authorization: Bearer ' . $accessToken;
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&', PHP_QUERY_RFC3986));
    }
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status < 200 || $status >= 300) {
        error_log('Google OAuth HTTP request failed' . ($curlError !== '' ? ': ' . $curlError : ' (HTTP ' . $status . ').'));
        throw new RuntimeException('Google sign-in could not be completed.');
    }
    $data = json_decode($body, true);
    if (!is_array($data)) throw new RuntimeException('Google returned an invalid response.');
    return $data;
}

function google_oauth_verify_id_token($idToken) {
    if (!is_string($idToken) || $idToken === '' || strlen($idToken) > 8192) {
        throw new RuntimeException('Google did not provide a valid ID token.');
    }
    $claims = google_oauth_request_json(
        'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken),
        'GET'
    );
    return google_oauth_validate_id_token_claims($claims, GOOGLE_OAUTH_CLIENT_ID);
}

function google_oauth_validate_id_token_claims($claims, $clientId) {
    if (!is_array($claims) || !is_string($clientId) || $clientId === '') {
        throw new RuntimeException('Google ID token verification failed.');
    }
    $issuer = $claims['iss'] ?? '';
    $audience = $claims['aud'] ?? '';
    $expiresAt = filter_var($claims['exp'] ?? null, FILTER_VALIDATE_INT);
    $issuedAt = filter_var($claims['iat'] ?? null, FILTER_VALIDATE_INT);
    $verified = ($claims['email_verified'] ?? false) === true
        || ($claims['email_verified'] ?? null) === 'true';
    if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
        || !is_string($audience) || !hash_equals($clientId, $audience)
        || $expiresAt === false || $expiresAt <= time()
        || $issuedAt === false || $issuedAt > time() + 60
        || !$verified) {
        throw new RuntimeException('Google ID token verification failed.');
    }
    $subject = $claims['sub'] ?? null;
    $email = google_auth_normalize_email($claims['email'] ?? null);
    if (!is_string($subject) || $subject === '' || strlen($subject) > 255 || $email === null) {
        throw new RuntimeException('Google ID token identity is invalid.');
    }
    return [
        'sub' => $subject,
        'email' => $email,
        'email_verified' => true,
    ];
}

function google_oauth_fetch_profile($code) {
    $tokens = google_oauth_request_json('https://oauth2.googleapis.com/token', 'POST', [
        'code' => $code,
        'client_id' => GOOGLE_OAUTH_CLIENT_ID,
        'client_secret' => GOOGLE_OAUTH_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ]);
    return google_oauth_verify_id_token($tokens['id_token'] ?? null);
}
