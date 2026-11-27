<?php
/* =========================================================
   api/committees.php — manage official committee names
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../committee_volunteer_store.php';

$action = $_GET['action'] ?? '';
$user = require_officer();
if ($action !== 'committee_save') out(['ok' => false, 'error' => 'Unknown action.'], 400);
if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage committees.'], 403);

$name = trim((string)($in['name'] ?? ''));
$original = $in['original'] ?? null;
$expected = $in['expected'] ?? null;
if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !is_array($expected)
    || ($original !== null && !is_string($original))) {
    out(['ok' => false, 'error' => 'Enter a committee name between 2 and 100 characters.'], 400);
}

$pdo = db();
committee_volunteers_ensure_schema($pdo);
$pdo->beginTransaction();
try {
    $defaults = json_encode(sk_default_committees(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
        VALUES ('skCommittees', ?, ?) ON DUPLICATE KEY UPDATE data_key = VALUES(data_key)")
        ->execute([$defaults, $user['username']]);
    $committees = sk_committee_names($pdo, true);
    if (!sk_role_equal($expected, $committees)) {
        throw new DomainException('The committee list changed in another session. Reload and try again.');
    }

    $targetIndex = null;
    if ($original !== null) {
        if (in_array($original, sk_default_committees(), true) && $name !== $original) {
            throw new RuntimeException('The required committees cannot be renamed.');
        }
        foreach ($committees as $index => $committee) {
            if ($committee === $original) {
                $targetIndex = $index;
                break;
            }
        }
        if ($targetIndex === null) throw new DomainException('That committee no longer exists.');
    } elseif (count($committees) >= 50) {
        throw new RuntimeException('A maximum of 50 committees is allowed.');
    }

    foreach ($committees as $index => $committee) {
        if ($index !== $targetIndex && mb_strtolower($committee) === mb_strtolower($name)) {
            throw new RuntimeException('A committee with that name already exists.');
        }
    }

    $roles = sk_role_rows($pdo, true);
    $budgetData = null;
    $budgetChanged = false;
    if ($targetIndex === null) {
        $committees[] = $name;
    } else {
        $oldName = $committees[$targetIndex];
        $committees[$targetIndex] = $name;
        if ($oldName !== $name) {
            $pdo->prepare('UPDATE committee_volunteers SET committee = ? WHERE committee = ?')
                ->execute([$name, $oldName]);
            foreach (['skCommitteeChairs', 'skCommitteeViceChairs', 'skCommitteeMembers'] as $key) {
                $assignmentQuery = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ? FOR UPDATE');
                $assignmentQuery->execute([$key]);
                $assignmentRaw = $assignmentQuery->fetchColumn();
                $assignmentMap = $assignmentRaw === false ? [] : json_decode((string)$assignmentRaw, true);
                if (!is_array($assignmentMap) || ($assignmentMap !== [] && array_keys($assignmentMap) === range(0, count($assignmentMap) - 1))) {
                    throw new RuntimeException('Committee assignment data is invalid.');
                }
                if (array_key_exists($oldName, $assignmentMap)) {
                    $assignmentMap[$name] = $assignmentMap[$oldName];
                    unset($assignmentMap[$oldName]);
                    $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?')
                        ->execute([json_encode($assignmentMap, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user['username'], $key]);
                }
            }
            $preferenceQuery = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skCommitteePreferences' FOR UPDATE");
            $preferenceRaw = $preferenceQuery->fetchColumn();
            if ($preferenceRaw !== false) {
                $preferenceMap = json_decode((string)$preferenceRaw, true);
                if (!is_array($preferenceMap) || ($preferenceMap !== [] && array_keys($preferenceMap) === range(0, count($preferenceMap) - 1))) {
                        throw new RuntimeException('Committee preference data is invalid.');
                }
                $preferencesChanged = false;
                foreach ($preferenceMap as $roleId => $preferences) {
                        if (!is_array($preferences) || ($preferences !== [] && array_keys($preferences) !== range(0, count($preferences) - 1))) {
                            throw new RuntimeException('Committee preference data is invalid.');
                        }
                        foreach ($preferences as $index => $preference) {
                            if ($preference === $oldName) {
                                $preferenceMap[$roleId][$index] = $name;
                                $preferencesChanged = true;
                            }
                        }
                }
                if ($preferencesChanged) {
                        $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skCommitteePreferences'")
                            ->execute([json_encode($preferenceMap, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user['username']]);
                }
            }
        }
        if ($oldName !== $name) {
            foreach ($roles as $index => $role) {
                $changed = false;
                $oldRole = $role;
                if (($role['committee'] ?? null) === $oldName) {
                    $roles[$index]['committee'] = $name;
                    $changed = true;
                }
                if (($role['preferredCommittee'] ?? null) === $oldName) {
                    $roles[$index]['preferredCommittee'] = $name;
                    $changed = true;
                }
                if (isset($role['preferredCommittees']) && is_array($role['preferredCommittees'])) {
                    $renamedPreferences = array_map(function ($preference) use ($oldName, $name) {
                        return $preference === $oldName ? $name : $preference;
                    }, $role['preferredCommittees']);
                    if ($renamedPreferences !== $role['preferredCommittees']) {
                        $roles[$index]['preferredCommittees'] = $renamedPreferences;
                        $changed = true;
                    }
                }
                if (!$changed) continue;
                if (!isset($roles[$index]['history']) || !is_array($roles[$index]['history'])) {
                    $roles[$index]['history'] = [];
                }
                $roles[$index]['history'][] = [
                    'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                    'action' => 'Committee assignment updated after rename',
                    'by' => $user['username'],
                    'note' => $oldName . ' renamed to ' . $name . '.',
                ];
                sk_role_write($pdo, $roles[$index], $user['username']);
                sk_audit($pdo, $user, (string)($role['kkId'] ?? ''), 'committee_renamed', $oldRole, $roles[$index]);
            }
            $budgetQuery = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skTransparencyBudget' FOR UPDATE");
            $budgetRaw = $budgetQuery->fetchColumn();
            if ($budgetRaw !== false) {
                $budgetData = json_decode((string)$budgetRaw, true);
                if (!is_array($budgetData) || !isset($budgetData['budgets']) || !is_array($budgetData['budgets'])) {
                    throw new RuntimeException('Transparency budget data is invalid.');
                }
                foreach ($budgetData['budgets'] as $year => &$yearBudget) {
                    if (!is_array($yearBudget) || !isset($yearBudget['projects'])) continue;
                    if (!is_array($yearBudget['projects'])) {
                        throw new RuntimeException('A fiscal-year project list is invalid.');
                    }
                    foreach ($yearBudget['projects'] as &$project) {
                        if (!is_array($project)) {
                            throw new RuntimeException('A project budget record is invalid.');
                        }
                        if (($project['committee'] ?? null) === $oldName) {
                            $project['committee'] = $name;
                            $budgetChanged = true;
                        }
                    }
                    unset($project);
                }
                unset($yearBudget);
                if ($budgetChanged) {
                    $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skTransparencyBudget'")
                        ->execute([json_encode($budgetData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $user['username']]);
                }
            }
            sk_audit($pdo, $user, null, 'committee_renamed', ['committee' => $oldName], ['committee' => $name]);
        }
    }

    $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skCommittees'")
        ->execute([json_encode($committees, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username']]);
    if ($targetIndex === null) {
        sk_audit($pdo, $user, null, 'committee_created', null, ['committee' => $name]);
    }
    $readAssignments = function ($key) use ($pdo) {
        $query = $pdo->prepare('SELECT data_value FROM app_data WHERE data_key = ?');
        $query->execute([$key]);
        $raw = $query->fetchColumn();
        if ($raw === false) return ['raw' => null, 'data' => []];
        $data = json_decode((string)$raw, true);
        if (!is_array($data) || ($data !== [] && array_keys($data) === range(0, count($data) - 1))) {
            throw new RuntimeException('Committee assignment data is invalid.');
        }
        return ['raw' => (string)$raw, 'data' => $data];
    };
    $chairState = $readAssignments('skCommitteeChairs');
    $viceState = $readAssignments('skCommitteeViceChairs');
    $memberState = $readAssignments('skCommitteeMembers');
    $preferenceQuery = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skCommitteePreferences'");
    $preferenceRaw = $preferenceQuery->fetchColumn();
    $preferences = $preferenceRaw === false ? null : json_decode((string)$preferenceRaw, true);
    if ($preferences !== null && (!is_array($preferences) || ($preferences !== [] && array_keys($preferences) === range(0, count($preferences) - 1)))) {
        throw new RuntimeException('Committee preference data is invalid.');
    }
    $pdo->commit();
    out([
        'ok' => true,
        'committees' => $committees,
        'roles' => array_values($roles),
        'assignments' => (object)$chairState['data'],
        'viceAssignments' => (object)$viceState['data'],
        'memberAssignments' => (object)$memberState['data'],
        'rawAssignments' => $chairState['raw'],
        'rawViceAssignments' => $viceState['raw'],
        'rawMemberAssignments' => $memberState['raw'],
        'preferences' => $preferences,
        'rawPreferences' => $preferenceRaw === false ? null : (string)$preferenceRaw,
        'budget' => $budgetChanged ? $budgetData : null,
        'rawBudget' => $budgetChanged ? json_encode($budgetData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : null,
    ]);
} catch (DomainException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 409);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the committee.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the committee.'], 500);
}
