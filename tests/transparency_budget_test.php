<?php
require_once __DIR__ . '/../transparency_budget.php';

$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) throw new RuntimeException($message);
};

$assert(transparency_budget_year_total([
    'projects' => [
        ['amount' => 1000],
        ['amount' => '250.50'],
        ['amount' => -20],
    ],
]) === 1250.5, 'Project budgets must be summed for dashboard totals.');
$assert(transparency_budget_year_total(['amount' => '700.25']) === 700.25, 'Legacy year-wide budgets must remain supported.');
$assert(transparency_budget_year_total(['projects' => [['amount' => 0]]]) === 0.0, 'A saved zero project amount must remain a valid total.');
$assert(transparency_budget_year_total(['projects' => [], 'amount' => 500]) === 500.0, 'A legacy total must be used when no project breakdown exists.');
$assert(transparency_budget_year_total(['projects' => [['amount' => INF]]]) === null, 'Invalid project amounts must not publish a dashboard total.');
$assert(transparency_budget_year_total(null) === null, 'Missing budget data must not be reported as a published budget.');

echo 'All transparency budget checks passed: ' . $checks . PHP_EOL;
