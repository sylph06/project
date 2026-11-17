<?php

function transparency_budget_year_total($budget) {
    if (!is_array($budget)) return null;

    $projects = $budget['projects'] ?? null;
    if (is_array($projects) && $projects) {
        $total = 0.0;
        $hasAmount = false;
        foreach ($projects as $project) {
            if (!is_array($project) || !isset($project['amount'])
                || !is_numeric($project['amount']) || !is_finite((float)$project['amount'])) continue;
            $total += max(0, (float)$project['amount']);
            if (!is_finite($total)) return null;
            $hasAmount = true;
        }
        if ($hasAmount) return $total;
    }

    return isset($budget['amount']) && is_numeric($budget['amount']) && is_finite((float)$budget['amount'])
        ? max(0, (float)$budget['amount'])
        : null;
}
