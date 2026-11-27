<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Participation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">

    <style>
:root {
    --mem-bg: var(--ui-bg, #f8fafc);
    --mem-surface: var(--ui-paper, #ffffff);
    --mem-surface-alt: var(--ui-surface, #f1f5f9);
    --mem-ink: var(--ui-ink, #1e293b);
    --mem-muted: var(--ui-ink-muted, #64748b);
    --mem-brand: var(--ui-brand, #1e40af);
    --mem-brand-soft: rgba(30,64,175,.1);
    --mem-brand-rgb: 30,64,175;
    --mem-brand-text-emphasis: var(--ui-brand-text-emphasis, rgba(30,64,175,.75));
    --mem-radius: var(--ui-radius, 12px);
    --mem-line: var(--ui-line, #e2e8f0);
}

html[data-bs-theme="dark"] {
    --mem-bg: #111827;
    --mem-surface: #1f2937;
    --mem-surface-alt: #374151;
    --mem-ink: #f9fafb;
    --mem-muted: #9ca3af;
    --mem-brand: #3b82f6;
    --mem-brand-soft: rgba(59,130,246,.1);
    --mem-brand-rgb: 59,130,246;
    --mem-brand-text-emphasis: rgba(59,130,246,.8);
    --mem-line: #374151;
}

* { box-sizing:border-box; }

body.participation-page {
    margin:0;
    background:var(--mem-bg)!important;
    color:var(--mem-ink)!important;
    font-family:Arial,sans-serif;
    font-size:15px;
    line-height:1.45;
}

.top-strip {
    height:7px;
    background:var(--mem-brand)!important;
}

.header {
    height:72px;
    background:var(--mem-surface)!important;
    border-bottom:1px solid var(--mem-line)!important;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 30px;
    gap:14px;
}
.header-title {
    font-weight:800;
    font-size:1.3rem;
    color:var(--mem-ink)!important;
}
.header small {
    color:var(--mem-muted)!important;
}
.content {
    width:100%;
    max-width:1600px;
    margin:0 auto;
    padding:24px clamp(16px,2.6vw,40px)!important;
}

        .card-box {
            height:100%;
            overflow:hidden;
            background:var(--mem-surface)!important;
            border:1px solid var(--mem-line)!important;
            border-radius:var(--mem-radius)!important;
            box-shadow:0 10px 30px rgba(0,0,0,.07);
        }

        .card-head {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:1rem;
            padding:18px 22px 0;
        }
        .card-head h2 {
            margin:0;
            font-size:1.05rem;
            font-weight:700;
            color:var(--mem-ink)!important;
            line-height:1.2;
        }
        .card-sub {
            margin:.25rem 0 0;
            font-size:.85rem;
            color:var(--mem-muted)!important;
        }
        .card-pad {
            padding:16px 22px 22px;
        }

        .participation-hero {
            position:relative;
            isolation:isolate;
            display:flex;
            flex-direction:column;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            width:100%;
            height:100%;
            min-height:320px;
            padding:24px;
            overflow:hidden;
            border:0;
            border-radius:16px;
            background:var(--mem-brand)!important;
            color:#fff;
            box-shadow:0 10px 26px rgba(30,64,175,.2);
        }
        .participation-hero::before,
        .participation-hero::after {
            position:absolute;
            z-index:0;
            border:22px solid rgba(255,255,255,.08);
            border-radius:50%;
            content:"";
            pointer-events:none;
        }
        .participation-hero::before {
            top:-168px;
            right:-62px;
            width:255px;
            height:255px;
        }
        .participation-hero::after {
            right:-70px;
            bottom:-182px;
            width:260px;
            height:260px;
        }
        .participation-hero-copy {
            position:relative;
            z-index:1;
        }
        .participation-hero-kicker {
            display:block;
            margin-bottom:10px;
            color:rgba(255,255,255,.7);
            font-size:.73rem;
            font-weight:700;
            letter-spacing:.1em;
            text-transform:uppercase;
        }
        .participation-hero h1 {
            margin:0;
            color:#fff;
            font-size:clamp(1.35rem,1.8vw,1.7rem);
            font-weight:750;
            line-height:1.2;
        }
        .participation-hero p {
            max-width:320px;
            margin:10px 0 0;
            color:rgba(255,255,255,.82);
            font-size:.9rem;
        }
        .participation-hero-metrics {
            position:relative;
            z-index:1;
            width:100%;
            margin-top:auto;
        }
        .participation-hero-metric {
            display:grid;
            grid-template-columns:30px minmax(0,1fr) auto;
            align-items:center;
            gap:9px;
            padding:7px 0;
            border-top:1px solid rgba(255,255,255,.24);
        }
        .participation-hero-metric-icon {
            display:grid;
            place-items:center;
            width:30px;
            height:30px;
            border-radius:50%;
            background:rgba(255,255,255,.16);
            color:#fff;
            font-size:.88rem;
        }
        .participation-hero-metric-label {
            color:rgba(255,255,255,.86);
            font-size:.76rem;
            font-weight:650;
            line-height:1.2;
        }
        .participation-hero-metric-value {
            color:#fff;
            font-size:1rem;
            font-weight:850;
            font-variant-numeric:tabular-nums;
        }
        .participation-hero-icon {
            position:absolute;
            z-index:0;
            right:20px;
            bottom:18px;
            color:rgba(255,255,255,.16);
            font-size:4rem;
            line-height:1;
        }
        .participation-hero .btn {
            position:relative;
            z-index:1;
        }
        .member-engagement-card {
            height:100%;
            min-width:0;
        }
        .member-engagement-card .card-head {
            align-items:flex-end;
            flex-wrap:wrap;
            padding:16px 18px 0;
        }
        .member-engagement-card .card-pad {
            padding:10px 18px 16px;
        }
        .member-engagement-heading {
            display:flex;
            align-items:center;
            gap:8px;
        }
        .member-engagement-heading i {
            color:var(--mem-brand)!important;
        }
        .member-engagement-search {
            width:min(270px,100%);
        }
        .member-engagement-search .form-control {
            min-height:38px;
            border-radius:8px;
        }
        .member-engagement-tabs {
            display:flex;
            flex-wrap:wrap;
            gap:7px;
            margin:0 0 8px;
        }
        .member-engagement-tab {
            display:inline-flex;
            align-items:center;
            gap:7px;
            padding:6px 11px;
            border:1px solid var(--mem-line)!important;
            border-radius:999px;
            background:var(--mem-surface)!important;
            color:var(--mem-ink)!important;
            font-size:.78rem;
            font-weight:650;
            white-space:nowrap;
        }
        .member-engagement-tab.active {
            border-color:var(--mem-brand)!important;
            background:var(--mem-brand)!important;
            color:#fff!important;
        }
        .member-engagement-tab-count {
            font-weight:800;
        }
        .member-engagement-table-wrap {
            max-height:360px;
            overflow:auto;
        }
        .member-engagement-table {
            min-width:760px;
            margin:0;
            font-size:.82rem;
        }
        .member-engagement-table thead th {
            position:sticky;
            top:0;
            z-index:1;
            padding:8px;
            background:var(--mem-surface)!important;
            color:var(--mem-muted)!important;
            font-size:.7rem;
            letter-spacing:.04em;
            text-transform:uppercase;
            white-space:nowrap;
            border:1px solid var(--mem-line)!important;
        }
        .member-engagement-table td {
            padding:8px;
            vertical-align:middle;
            border:1px solid var(--mem-line)!important;
            color:var(--mem-ink)!important;
        }
        .member-engagement-name {
            display:block;
            color:var(--mem-ink)!important;
            font-weight:700;
        }
        .member-engagement-id {
            display:block;
            color:var(--mem-muted)!important;
            font-size:.72rem;
        }
        .member-engagement-rate {
            display:flex;
            align-items:center;
            gap:8px;
            min-width:120px;
        }
        .member-engagement-progress {
            flex:1;
            height:7px;
            overflow:hidden;
            border-radius:999px;
            background:var(--mem-line)!important;
        }
        .member-engagement-progress span {
            display:block;
            height:100%;
            border-radius:inherit;
            background:var(--mem-brand)!important;
        }
        .member-engagement-rate strong {
            min-width:34px;
            color:var(--mem-ink)!important;
            font-size:.75rem;
            text-align:right;
        }
        .member-engagement-status {
            display:inline-block;
            padding:3px 8px;
            border-radius:999px;
            font-size:.72rem;
            font-weight:700;
            white-space:nowrap;
        }
        .member-engagement-status.regular {
            background:#e2eee7;
            color:#416d53;
        }
        .member-engagement-status.occasional {
            background:#f4eedf;
            color:#876b34;
        }
        .member-engagement-status.none {
            background:var(--mem-brand-soft)!important;
            color:var(--mem-brand)!important;
        }
        .member-engagement-footer {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-top:8px;
            color:var(--mem-muted)!important;
            font-size:.76rem;
        }
        .member-engagement-note {
            display:block;
            margin-top:6px;
            color:var(--mem-muted)!important;
            font-size:.72rem;
        }
        .participation-overview-card .card-head {
            padding:16px 18px 0;
        }
        .participation-overview-card .card-pad {
            padding:12px 18px 18px;
        }

        .chart-box {
            position:relative;
            height:290px;
        }
        .chart-empty {
            position:absolute;
            inset:0;
            display:grid;
            place-items:center;
            padding:1rem;
            color:var(--mem-muted)!important;
            font-size:.9rem;
            text-align:center;
        }

        .attendance-overview-layout {
            display:grid;
            grid-template-columns:1fr;
            gap:14px;
        }
        .attendance-doughnut {
            height:270px;
        }
        .attendance-overview-legend {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
            gap:12px;
            min-width:0;
        }
        .attendance-overview-item {
            display:grid;
            grid-template-columns:12px minmax(0,1fr) auto;
            align-items:center;
            gap:8px;
            min-width:0;
            color:var(--mem-muted)!important;
            font-size:.88rem;
        }
        .attendance-overview-item>span:nth-child(2) {
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .attendance-overview-dot {
            width:12px;
            height:12px;
            border-radius:4px;
        }
        .attendance-overview-item strong {
            color:var(--mem-ink)!important;
            white-space:nowrap;
        }

        /* ===== Filters ===== */
        .filter-bar {
            display:flex;
            flex-wrap:wrap;
            align-items:flex-end;
            gap:14px;
            padding:16px 20px;
            background:var(--mem-surface)!important;
            border:1px solid var(--mem-line)!important;
            border-radius:var(--mem-radius)!important;
            box-shadow:0 10px 30px rgba(0,0,0,.05);
        }
        .filter-bar .form-label {
            font-size:.82rem;
            font-weight:700;
            margin-bottom:.25rem;
            color:var(--mem-muted)!important;
        }
        .filter-bar .form-control,
        .filter-bar .form-select {
            min-height:42px;
            border-radius:var(--mem-radius);
            border:1px solid var(--mem-line)!important;
            color:var(--mem-ink)!important;
            background-color:var(--mem-surface)!important;
        }
        .filter-title {
            display:flex;
            align-items:center;
            gap:.5rem;
            font-weight:800;
            font-size:1.05rem;
            color:var(--mem-ink)!important;
            padding-bottom:.55rem;
        }
        body.participation-page .form-control:focus,
        body.participation-page .form-select:focus {
            border-color:var(--mem-brand)!important;
            box-shadow:0 0 0 .2rem rgba(var(--mem-brand-rgb),.28)!important;
        }

        /* ===== Heatmap ===== */
        .heat-scroll {
            max-width:100%;
            overflow:auto;
            border:1px solid var(--mem-line)!important;
            border-radius:10px;
        }
        .heat-grid {
            display:grid;
            gap:4px;
            min-width:max-content;
            padding:8px;
            font-size:.88rem;
            text-align:center;
        }
        .heat-grid .heat-corner,
        .heat-grid .heat-col-head,
        .heat-grid .heat-row-head,
        .heat-grid .heat-cell,
        .heat-grid .heat-total {
            display:flex;
            min-height:44px;
            align-items:center;
            justify-content:center;
            padding:.4rem .6rem;
        }
        .heat-grid .heat-corner,
        .heat-grid .heat-col-head {
            position:sticky;
            top:0;
            z-index:2;
            background:var(--mem-surface)!important;
            color:var(--mem-muted)!important;
            font-weight:700;
            font-size:.82rem;
            white-space:nowrap;
        }
        .heat-grid .heat-corner,
        .heat-grid .heat-row-head {
            position:sticky;
            left:0;
            z-index:3;
            justify-content:flex-start;
            background:var(--mem-surface)!important;
            text-align:left;
        }
        .heat-grid .heat-row-head {
            min-width:150px;
            border-right:1px solid var(--mem-line)!important;
            font-weight:700;
            color:var(--mem-ink)!important;
        }
        .heat-grid .heat-col-head span {
            display:block;
            max-width:110px;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .heat-grid .heat-cell {
            min-width:60px;
            border-radius:8px;
            font-weight:800;
            font-size:.95rem;
        }
        .heat-grid button.heat-cell {
            width:100%;
            border:0;
            font-family:inherit;
            font-size:.95rem;
            font-weight:800;
            cursor:pointer;
        }
        .heat-grid .heat-cell.is-selected {
            outline:2px solid var(--mem-brand);
            outline-offset:1px;
        }
        .heat-grid button.heat-cell:focus-visible {
            outline:2px solid var(--mem-brand);
            outline-offset:1px;
        }
        .heat-grid .heat-total {
            font-weight:800;
            color:var(--mem-ink)!important;
        }
        .heat-l0{background:var(--h0);color:var(--hc0)}.heat-l1{background:var(--h1);color:var(--hc1)}.heat-l2{background:var(--h2);color:var(--hc2)}
        .heat-l3{background:var(--h3);color:var(--hc3)}.heat-l4{background:var(--h4);color:var(--hc4)}.heat-l5{background:var(--h5);color:var(--hc5)}
        .heat-scale{display:flex;align-items:center;gap:.6rem;margin-top:.9rem;color:var(--muted);font-size:.82rem}
        .heat-scale-blocks{display:inline-flex;gap:3px}
        .heat-scale-blocks span{width:22px;height:12px;border-radius:3px;background:var(--h0)}
        .heat-scale-blocks span:nth-child(2){background:var(--h1)}.heat-scale-blocks span:nth-child(3){background:var(--h2)}
        .heat-scale-blocks span:nth-child(4){background:var(--h3)}.heat-scale-blocks span:nth-child(5){background:var(--h5)}

        /* Table styling */
        body.participation-page .table {
            font-size:.92rem;
            --bs-table-bg:transparent;
            --bs-table-color:var(--mem-ink);
            --bs-table-border-color:var(--mem-line);
            color:var(--mem-ink);
        }
        body.participation-page .table th {
            background:var(--mem-surface-alt)!important;
            color:var(--mem-muted)!important;
            font-size:.82rem;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.04em;
            white-space:nowrap;
            border:1px solid var(--mem-line)!important;
        }
        body.participation-page .table td {
            vertical-align:middle;
            border:1px solid var(--mem-line)!important;
            color:var(--mem-ink)!important;
        }
        body.participation-page .table tbody tr:hover>* {
            background-color:var(--mem-surface-alt)!important;
        }
        body.participation-page .table .text-muted {
            color:var(--mem-muted)!important;
        }
        .record-wrap {
            max-height:62vh;
            overflow:auto;
            border:1px solid var(--mem-line)!important;
            border-radius:10px;
        }
        .record-table thead th {
            position:sticky;
            top:0;
            z-index:2;
        }
        .record-count {
            display:flex;
            align-items:center;
            min-height:42px;
            padding:.45rem .8rem;
            border-radius:8px;
            background:var(--mem-brand-soft)!important;
            color:var(--mem-brand)!important;
            font-size:.9rem;
            font-weight:800;
        }
        .attendance-record-grid {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:10px;
            max-height:62vh;
            overflow:auto;
            padding:2px;
        }
        .participation-records-panel {
            border:1px solid var(--mem-line)!important;
            border-radius:16px;
            overflow:hidden;
        }
        .participation-records-panel .card-head {
            align-items:center;
            padding:16px 18px 8px;
            border-bottom:0;
        }
        .participation-records-panel .card-head h2 {
            padding-left:0;
            border-left:0;
            font-size:1.05rem;
        }
        .participation-records-header-icon {
            display:grid;
            place-items:center;
            width:38px;
            height:38px;
            border-radius:10px;
            background:var(--mem-brand-soft)!important;
            color:var(--mem-brand)!important;
            flex:none;
        }
        .participation-records-panel .card-pad {
            padding:10px 18px 18px;
        }
        .participation-records-toolbar {
            display:flex;
            align-items:flex-end;
            gap:10px;
            margin-bottom:10px;
        }
        .participation-records-toolbar .search-wrap {
            flex:1 1 280px;
        }
        .participation-records-toolbar .status-wrap {
            width:210px;
        }
        .participation-records-toolbar .record-count {
            min-height:40px;
        }
        .participation-records-panel .attendance-record-grid {
            grid-template-columns:1fr;
            max-height:330px;
            gap:0;
            border-top:1px solid var(--mem-line)!important;
        }
        .participation-records-panel .attendance-record-card {
            border:0;
            border-bottom:1px solid var(--mem-line)!important;
            border-radius:0;
            padding:11px 4px;
            box-shadow:none;
        }
        .participation-records-panel .attendance-record-card:first-child {
            border-top:0;
        }
        .participation-records-panel .attendance-record-card:hover {
            transform:none;
            box-shadow:none;
            background:var(--mem-surface-alt)!important;
        }
        .participation-records-panel .attendance-date-chip {
            width:48px;
            height:48px;
        }
        .records-kpi {
            padding:0;
            border:1px solid var(--mem-line)!important;
            background:var(--mem-surface)!important;
            width:100%;
            text-align:left;
        }
        .records-kpi .kpi-body {
            padding:20px 22px;
        }
        .participation-records-modal .modal-dialog {
            max-width:min(980px,calc(100vw - 2rem));
        }
        .participation-records-modal .modal-content {
            border:1px solid var(--mem-line)!important;
            border-radius:16px;
            background:var(--mem-surface)!important;
        }
        .participation-records-modal .modal-header {
            border-bottom:1px solid var(--mem-line)!important;
        }
        .participation-records-modal .modal-body {
            padding:18px;
        }
        .participation-records-modal .attendance-record-grid {
            grid-template-columns:repeat(2,minmax(0,1fr));
            max-height:65vh;
            gap:10px;
        }
        .participation-records-modal .attendance-record-card {
            border:1px solid var(--mem-line)!important;
            border-radius:12px;
            padding:12px;
        }
        .attendance-record-card {
            display:flex;
            align-items:center;
            gap:12px;
            min-width:0;
            padding:12px;
            border:1px solid var(--mem-line)!important;
            border-radius:12px;
            background:var(--mem-surface)!important;
            text-decoration:none;
            color:var(--mem-ink)!important;
        }
        .attendance-record-card:hover,
        .attendance-record-card:focus-visible {
            border-color:var(--mem-brand)!important;
            outline:none;
        }
        .attendance-date-chip {
            display:flex;
            flex:none;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            width:52px;
            height:52px;
            border-radius:10px;
            background:var(--mem-brand-soft)!important;
            color:var(--mem-brand)!important;
            font-size:.68rem;
            font-weight:800;
            text-transform:uppercase;
            line-height:1.1;
        }
        .attendance-date-chip strong {
            font-size:1.1rem;
            margin-top:2px;
        }
        .attendance-record-info {
            min-width:0;
            flex:1;
        }
        .attendance-record-name {
            display:block;
            overflow:hidden;
            color:var(--mem-ink)!important;
            font-size:.92rem;
            font-weight:800;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .attendance-record-event {
            display:block;
            overflow:hidden;
            color:var(--mem-muted)!important;
            font-size:.78rem;
            text-overflow:ellipsis;
            white-space:nowrap;
        }
        .attendance-record-meta {
            display:flex;
            align-items:center;
            gap:5px;
            flex-wrap:wrap;
            margin-top:6px;
            color:var(--mem-muted)!important;
            font-size:.72rem;
        }
        .attendance-record-status {
            flex:none;
        }
        .attendance-record-arrow {
            flex:none;
            color:var(--mem-brand)!important;
        }

        .member-roster-table{width:100%;border-collapse:separate;border-spacing:0 6px}
        .member-roster-table th{padding:8px 10px;color:var(--mem-muted)!important;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;border:0!important}
        .member-roster-table td{padding:10px;border-top:1px solid var(--mem-line)!important;border-bottom:1px solid var(--mem-line)!important;background:var(--mem-surface)!important;vertical-align:middle;color:var(--mem-ink)!important}
        .member-roster-table td:first-child{border-left:1px solid var(--mem-line)!important;border-radius:10px 0 0 10px}
        .member-roster-table td:last-child{border-right:1px solid var(--mem-line)!important;border-radius:0 10px 10px 0;text-align:right}
        .member-roster-name{display:block;font-weight:800;color:var(--mem-ink)!important}
        .member-roster-no{display:block;font-size:.78rem;color:var(--mem-muted)!important}
        .member-roster-purok{color:var(--mem-brand)!important;font-weight:800}
        .roster-action-toolbar{display:grid;grid-template-columns:minmax(220px,1fr) minmax(190px,260px);align-items:end;gap:12px;margin:12px 0}
        .roster-main-table-wrap{max-height:55vh;overflow:auto;border:1px solid var(--mem-line)!important;border-radius:10px}
        .roster-main-table thead th{position:sticky;top:0;z-index:2}
        .roster-main-table td:last-child{text-align:left}
        .roster-main-table td{min-width:120px}
        .roster-main-table td:first-child{min-width:210px}
        .roster-main-table td:nth-child(2){min-width:110px}
        .roster-main-table td:nth-child(3){white-space:nowrap}
        .attendance-status-select.is-staged{border-color:var(--mem-brand)!important;box-shadow:0 0 0 .15rem var(--mem-brand-text-emphasis)!important}
        .roster-save-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px}
        .roster-save-hint{color:var(--mem-muted)!important;font-size:.82rem}
        .all-activities-summary{display:flex;align-items:center;gap:14px;padding:22px;border:1px solid var(--mem-line)!important;border-radius:10px;background:var(--mem-brand-soft)!important}
        .all-activities-summary-icon{display:grid;place-items:center;flex:none;width:46px;height:46px;border-radius:13px;background:var(--mem-brand)!important;color:var(--mem-brand-text-emphasis)!important;font-size:1.25rem}
        .all-activities-summary-copy{flex:1}
        .all-activities-summary-label{display:block;color:var(--mem-ink)!important;font-size:.95rem;font-weight:800}
        .all-activities-summary-note{display:block;margin-top:3px;color:var(--mem-muted)!important;font-size:.82rem}
        .all-activities-summary-value{color:var(--mem-brand)!important;font-size:2rem;font-weight:900;line-height:1}
        .roster-section{margin-top:10px}
        .roster-section-title{display:flex;align-items:center;gap:8px;margin-bottom:4px}
        .roster-section-title h3{margin:0;font-size:.95rem;font-weight:800;color:var(--mem-ink)!important}
        .roster-count{display:inline-grid;place-items:center;min-width:24px;height:24px;padding:0 6px;border-radius:20px;background:var(--mem-brand-soft)!important;color:var(--mem-brand)!important;font-size:.78rem;font-weight:800}
        .attendance-status-select{min-width:130px}
        .attendance-overview-layout{display:grid;grid-template-columns:1fr;gap:14px}
        .attendance-doughnut{height:270px}
        .attendance-overview-legend{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;min-width:0}
        .attendance-overview-item{display:grid;grid-template-columns:12px minmax(0,1fr) auto;align-items:center;gap:8px;min-width:0;color:var(--mem-muted)!important;font-size:.88rem}
        .attendance-overview-item>span:nth-child(2){min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .attendance-overview-dot{width:12px;height:12px;border-radius:4px}
        .attendance-overview-item strong{color:var(--mem-ink)!important;white-space:nowrap}
        .member-roster-table .btn{white-space:nowrap}
        @media(max-width:767.98px){
            .member-roster-table{min-width:620px}
            .participation-hero{min-height:360px}
            .member-engagement-footer{align-items:flex-start;flex-direction:column}
            .attendance-doughnut{height:230px}
            .roster-action-toolbar{grid-template-columns:1fr}
            .roster-save-bar{align-items:stretch;flex-direction:column}
        }
        @media(max-width:767.98px){
            .participation-records-modal .attendance-record-grid{grid-template-columns:1fr}
        }
        @media(max-width:767.98px){
            .participation-records-toolbar{flex-wrap:wrap}
            .participation-records-toolbar .search-wrap,.participation-records-toolbar .status-wrap{width:100%;flex-basis:100%}
        }
        @media(max-width:991.98px){.attendance-record-grid{grid-template-columns:1fr}}
        body.participation-page label.form-label{color:var(--mem-muted)!important}
        .footer-note{text-align:center;color:var(--mem-muted)!important;font-size:.85rem;padding:24px}

        @media(max-width:767.98px){
            .header{padding:0 14px}
            body.participation-page>main.page-content>.content{padding:14px!important}
        }

        /* Mobile table-to-cards pattern — max-width 575px */
        @media(max-width:575px){
            .participation-page .table-responsive,
            .participation-page table.table{
                display:block;
                width:100%;
            }
            .participation-page table.table,
            .participation-page table.table thead,
            .participation-page table.table tbody,
            .participation-page table.table tr,
            .participation-page table.table th,
            .participation-page table.table td{
                display:block;
            }
            .participation-page table.table thead tr{
                position:absolute;
                top:-9999px;
                left:-9999px;
            }
            .participation-page table.table tr{
                margin-bottom:14px;
            }
            .participation-page table.table td{
                text-align:right;
                position:relative;
                padding-left:50%;
                border:0;
                border-bottom:1px solid var(--mem-line)!important;
            }
            .participation-page table.table td:before{
                content:attr(data-label);
                position:absolute;
                left:10px;
                top:50%;
                transform:translateY(-50%);
                text-align:left;
                width:45%;
                padding-right:10px;
                white-space:nowrap;
                color:var(--mem-muted)!important;
                font-size:.8rem;
                font-weight:800;
                text-transform:uppercase;
                letter-spacing:.04em;
            }
            .participation-page table.table tr{
                border:1px solid var(--mem-line)!important;
                border-radius:10px;
                background:var(--mem-surface)!important;
                overflow:hidden;
            }
            .participation-page table.table tr td:last-child{
                border-bottom:0;
            }
        }

        @media(max-width:575px){
            .heat-grid{
                gap:4px;
            }
            .heat-cell{
                min-width:14px;
                min-height:14px;
                font-size:.62rem;
            }
        }

        @media(max-width:767.98px){
            .member-roster-table{min-width:620px}
            .participation-hero{min-height:360px}
            .member-engagement-footer{align-items:flex-start;flex-direction:column}
            .attendance-doughnut{height:230px}
            .roster-action-toolbar{grid-template-columns:1fr}
            .roster-save-bar{align-items:stretch;flex-direction:column}
        }
        @media(max-width:767.98px){
            .participation-records-modal .attendance-record-grid{grid-template-columns:1fr}
        }
        @media(max-width:767.98px){
            .participation-records-toolbar{flex-wrap:wrap}
            .participation-records-toolbar .search-wrap,.participation-records-toolbar .status-wrap{width:100%;flex-basis:100%}
        }
        @media(max-width:991.98px){.attendance-record-grid{grid-template-columns:1fr}}
    </style>
<?php include __DIR__ . '/init.php'; ?>
<?php
require_once __DIR__ . '/participation_logic.php';
$participationMembers = json_decode($__data['kkMembers'] ?? '[]', true);
$participationMembers = is_array($participationMembers) ? $participationMembers : [];
$participationCopCategories = ['Health', 'Education', 'Economic Empowerment', 'Social Inclusion and Equity', 'Peace-Building and Security', 'Governance', 'Active Citizenship', 'Environment', 'Global Mobility', 'Agriculture'];
function participation_event_centers($event, $categories) {
    $centers = is_array($event['centers'] ?? null) ? $event['centers'] : [];
    $legacy = [
        'General' => 'Governance', 'Community' => 'Active Citizenship', 'Youth' => 'Active Citizenship',
        'Program' => 'Education', 'Meeting' => 'Governance', 'Event' => 'Active Citizenship',
        'Emergency' => 'Peace-Building and Security', 'Other' => 'Governance',
    ];
    $category = (string)($event['category'] ?? '');
    if (!$centers) $centers[] = $legacy[$category] ?? $category;
    return array_values(array_unique(array_filter($centers, function ($center) use ($categories) {
        return is_string($center) && in_array($center, $categories, true);
    })));
}
$participationRegistrations = json_decode($__data['skEventRegistrations'] ?? '[]', true);
$participationRegistrations = is_array($participationRegistrations) ? $participationRegistrations : [];
$participationAnnouncements = json_decode($__data['skAnnouncements'] ?? '[]', true);
$participationAnnouncements = is_array($participationAnnouncements) ? $participationAnnouncements : [];
$participationWalkins = json_decode($__data['skEventAttendance'] ?? '[]', true);
$participationWalkins = is_array($participationWalkins) ? $participationWalkins : [];
$participationMembersById = [];
foreach ($participationMembers as $member) {
    if (is_array($member) && isset($member['id'])) $participationMembersById[(string)$member['id']] = $member;
}
$participationEventsById = [];
foreach ($participationAnnouncements as $event) {
    if (is_array($event) && isset($event['id'])) $participationEventsById[(string)$event['id']] = $event;
}
function participation_full_name($member) {
    return trim(implode(' ', array_filter([
        $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
    ], function ($part) { return trim((string)$part) !== ''; })));
}
$participationHeatmapRows = [];
$participationEventOptions = [];
$participationAttendedSignupMemberEvents = [];
$participationWalkinMemberEvents = [];
$participationRemovedWalkins = array_values(array_filter($participationWalkins, function ($record) {
    return is_array($record) && !participation_walkin_is_active($record);
}));
$participationActiveMembers = 0;
foreach ($participationMembers as $member) {
    if (is_array($member) && ($member['status'] ?? '') === 'Active') $participationActiveMembers++;
}
foreach ($participationRegistrations as $signup) {
    if (!is_array($signup)) continue;
    $eventId = (string)($signup['eventId'] ?? '');
    $event = $participationEventsById[$eventId] ?? [];
    $eventTitle = trim((string)($signup['eventTitle'] ?? $event['title'] ?? 'Unknown event'));
    if ($eventTitle === '') $eventTitle = 'Unknown event';
    $activityCategory = trim((string)($event['category'] ?? ''));
    if ($activityCategory === '') $activityCategory = 'Uncategorized';
    $eventDate = (string)($event['date'] ?? '');
    $memberId = isset($signup['memberId']) ? (string)$signup['memberId'] : '';
    $member = $memberId !== '' ? ($participationMembersById[$memberId] ?? null) : null;
    $name = $member ? participation_full_name($member) : trim((string)($signup['name'] ?? ''));
    if ($name === '') $name = 'Unnamed participant';
    $status = in_array($signup['attendanceStatus'] ?? '', ['Attended', 'Did not attend'], true) ? $signup['attendanceStatus'] : 'Not recorded';
    if ($status === 'Attended') {
        if ($member) {
            $participationAttendedSignupMemberEvents[$eventId . ':' . $memberId] = true;
        }
    }
    $age = participation_age_at_event($member, $eventDate, $signup['age'] ?? null);
    $ageGroup = participation_age_group($age);
    $memberPurok = $member ? trim((string)($member['purok'] ?? '')) : '';
    $purok = $memberPurok !== '' ? $memberPurok : trim((string)($signup['purok'] ?? ''));
    if ($purok === '') $purok = 'Not recorded';
    $participationHeatmapRows[] = [
        'source' => 'signup',
        'recordId' => (string)($signup['id'] ?? ''),
        'memberKey' => $member ? $memberId : 'signup:' . (string)($signup['id'] ?? $signup['name'] ?? 'unlinked'),
        'memberId' => $member ? $memberId : '',
        'memberNo' => $member ? (string)($member['memberNo'] ?? $memberId) : '',
        'memberName' => $member ? $name : '',
        'eventTitle' => $eventTitle, 'eventId' => $eventId, 'eventDate' => $eventDate,
        'centers' => participation_event_centers($event, $participationCopCategories),
        'activityCategory' => $activityCategory, 'attendanceStatus' => $status,
        'ageGroup' => $ageGroup, 'purok' => $purok,
    ];
    if ($eventId !== '') $participationEventOptions[$eventId] = $eventTitle;
}
foreach ($participationWalkins as $walkin) {
    if (!participation_walkin_is_active($walkin)) continue;
    $eventId = (string)($walkin['eventId'] ?? '');
    $memberId = (string)($walkin['memberId'] ?? '');
    if (!participation_walkin_should_count($eventId, $memberId, $participationAttendedSignupMemberEvents, $participationWalkinMemberEvents)) continue;
    if ($memberId !== '') $participationWalkinMemberEvents[$eventId . ':' . $memberId] = true;
    $member = $memberId !== '' ? ($participationMembersById[$memberId] ?? null) : null;
    $event = $participationEventsById[$eventId] ?? [];
    $eventTitle = trim((string)($walkin['eventTitle'] ?? $event['title'] ?? 'Unknown event'));
    if ($eventTitle === '') $eventTitle = 'Unknown event';
    $activityCategory = trim((string)($event['category'] ?? ''));
    if ($activityCategory === '') $activityCategory = 'Uncategorized';
    $name = $member ? participation_full_name($member) : 'KK member';
    $eventDate = (string)($event['date'] ?? '');
    $age = participation_age_at_event($member, $eventDate);
    $ageGroup = participation_age_group($age);
    $purok = trim((string)($member['purok'] ?? ''));
    if ($purok === '') $purok = 'Not recorded';
    $participationHeatmapRows[] = [
        'source' => 'walkin',
        'recordId' => (string)($walkin['id'] ?? ''),
        'memberKey' => $member ? $memberId : 'walkin:' . (string)($walkin['id'] ?? $walkin['name'] ?? 'unlinked'),
        'memberId' => $member ? $memberId : '',
        'memberNo' => $member ? (string)($member['memberNo'] ?? $memberId) : '',
        'memberName' => $member ? $name : '',
        'eventTitle' => $eventTitle, 'eventId' => $eventId, 'eventDate' => $eventDate,
        'centers' => participation_event_centers($event, $participationCopCategories),
        'activityCategory' => $activityCategory, 'attendanceStatus' => 'Attended',
        'ageGroup' => $ageGroup, 'purok' => $purok,
    ];
    if ($eventId !== '') $participationEventOptions[$eventId] = $eventTitle;
}
foreach ($participationRemovedWalkins as $walkin) {
    $eventId = (string)($walkin['eventId'] ?? '');
    $event = $participationEventsById[$eventId] ?? [];
    $eventTitle = trim((string)($walkin['eventTitle'] ?? $event['title'] ?? ''));
    if ($eventId !== '' && $eventTitle !== '') $participationEventOptions[$eventId] = $eventTitle;
}
$today = date('Y-m-d');
$currentTime = date('H:i');
foreach ($participationEventsById as $eventId => $event) {
    $eventDate = (string)($event['date'] ?? '');
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $eventDate, $dateParts) ||
        !checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1]) ||
        !empty($event['cancelled'])) continue;
    if (is_public() && (
        ($event['publishState'] ?? '') !== 'Published' ||
        (!empty($event['publishDate']) && (string)$event['publishDate'] > $today)
    )) continue;
    $endTime = (string)($event['endTime'] ?? '23:59');
    if (!preg_match('/^\d{2}:\d{2}$/', $endTime)) $endTime = '23:59';
    if ($eventDate > $today || ($eventDate === $today && $endTime >= $currentTime)) continue;
    $eventTitle = trim((string)($event['title'] ?? ''));
    if ($eventTitle !== '') $participationEventOptions[(string)$eventId] = $eventTitle;
}
asort($participationEventOptions, SORT_NATURAL | SORT_FLAG_CASE);
$participationRosterMembers = array_values(array_filter($participationMembers, function ($member) {
    if (!is_array($member)) return false;
    if (($member['status'] ?? '') === 'Active') return true;
    if (is_public()) return false;
    foreach ($participationRemovedWalkins as $walkin) {
        if ((string)($walkin['memberId'] ?? '') === (string)($member['id'] ?? '')) return true;
    }
    return false;
}));
usort($participationRosterMembers, function ($a, $b) {
    return strcmp(participation_full_name($a), participation_full_name($b));
});
$participationEventAnalytics = [];
foreach ($participationEventsById as $eventId => $event) {
    $memberAgeGroups = [];
    if (!is_public()) {
        foreach ($participationRosterMembers as $member) {
            if (($member['status'] ?? '') !== 'Active' || !isset($member['id'])) continue;
            $memberAgeGroups[(string)$member['id']] = participation_age_group(
                participation_age_at_event($member, (string)($event['date'] ?? ''))
            );
        }
    }
    $participationEventAnalytics[$eventId] = [
        'title' => (string)($event['title'] ?? ''),
        'date' => (string)($event['date'] ?? ''),
        'endTime' => (string)($event['endTime'] ?? ''),
        'cancelled' => !empty($event['cancelled']),
        'published' => !is_public() || (
            ($event['publishState'] ?? '') === 'Published' &&
            (empty($event['publishDate']) || (string)$event['publishDate'] <= date('Y-m-d'))
        ),
        'category' => (string)($event['category'] ?? ''),
        'centers' => participation_event_centers($event, $participationCopCategories),
        'memberAgeGroups' => $memberAgeGroups,
    ];
}
?>
</head>

<body class="participation-page">

<div class="top-strip"></div>

<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=20"></script>

<main class="main page-content">

<header class="header">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
            <div class="header-title">Participation</div>
            <small>Youth involvement in SK activities and programs</small>
        </div>
    </div>
    <div class="header-account-area">
        <div class="header-avatar"><?= e(uinit()) ?></div>
        <div class="d-none d-md-block header-identity">
            <div class="header-identity-name"><?= e(uname()) ?></div>
            <div class="header-identity-role"><?= e(strtoupper(urole())) ?></div>
        </div>
        <a href="<?= is_public() ? 'login.php' : 'logout.php' ?>" class="btn btn-sm header-logout">
            <i class="bi bi-box-arrow-right me-1"></i><?= is_public() ? 'Official Login' : 'Logout' ?>
        </a>
    </div>
</header>

<div class="content">

    <!-- 1. FILTERS (isang lugar lang, para sa buong page) -->
    <div class="filter-bar mb-3">
        <div class="filter-title"><i class="bi bi-funnel"></i>Filters</div>
        <div style="min-width:220px;flex:1 1 220px">
            <label class="form-label" for="participationActivityFilter">Activity</label>
            <select class="form-select" id="participationActivityFilter">
                <option value="">All activities</option>
                <?php foreach ($participationEventOptions as $eventId => $eventTitle): ?>
                    <option value="<?= e($eventId) ?>"><?= e($eventTitle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label class="form-label" for="participationDateFrom">From</label><input type="date" class="form-control" id="participationDateFrom"></div>
        <div><label class="form-label" for="participationDateTo">To</label><input type="date" class="form-control" id="participationDateTo"></div>
        <div><button class="btn btn-primary" type="button" id="clearParticipationFilters" style="min-height:40px;border-radius:10px"><i class="bi bi-arrow-counterclockwise me-1"></i>Clear</button></div>
        <div id="participationFilterError" class="small text-danger w-100" role="alert" aria-live="polite"></div>
    </div>

    <div class="row g-3 mb-3 align-items-stretch">
        <div class="col-12 <?= is_public() ? '' : 'col-xl-3' ?> d-flex">
            <section class="participation-hero">
                <div class="participation-hero-copy">
                    <span class="participation-hero-kicker">Youth programs</span>
                    <h1>Youth Participation</h1>
                    <p>Track involvement and attendance across SK activities.</p>
                </div>
                <div class="participation-hero-metrics" aria-label="Participation summary">
                    <div class="participation-hero-metric">
                        <span class="participation-hero-metric-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <span class="participation-hero-metric-label">Active KK Members</span>
                        <strong class="participation-hero-metric-value" id="heroActiveMembers"><?= (int)$participationActiveMembers ?></strong>
                    </div>
                    <div class="participation-hero-metric">
                        <span class="participation-hero-metric-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                        <span class="participation-hero-metric-label">Confirmed Attendance</span>
                        <strong class="participation-hero-metric-value" id="heroConfirmedAttendance">0</strong>
                    </div>
                    <div class="participation-hero-metric">
                        <span class="participation-hero-metric-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                        <span class="participation-hero-metric-label">Activities tracked</span>
                        <strong class="participation-hero-metric-value" id="heroActivitiesTracked">0</strong>
                    </div>
                </div>
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#participationRecordsModal">
                    <i class="bi bi-clipboard-check me-1"></i>Open records
                </button>
                <a href="event-attendance.php" class="btn btn-primary"><i class="bi bi-qr-code me-1"></i>QR Scan Attendance</a>
                <i class="bi bi-people-fill participation-hero-icon" aria-hidden="true"></i>
            </section>
        </div>
        <?php if (!is_public()): ?>
        <div class="col-12 col-xl-9">
            <section class="card-box member-engagement-card" id="memberEngagementCard">
                <div class="card-head">
                    <div>
                        <h2 class="member-engagement-heading"><i class="bi bi-people-fill" aria-hidden="true"></i>Member engagement</h2>
                        <p class="card-sub" id="memberEngagementScope">Based on completed activities and selected filters</p>
                    </div>
                    <div class="member-engagement-search">
                        <label class="visually-hidden" for="memberEngagementSearch">Search members</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" class="form-control" id="memberEngagementSearch" placeholder="Search name, ID, or Purok">
                        </div>
                    </div>
                </div>
                <div class="card-pad">
                    <div class="member-engagement-tabs" id="memberEngagementTabs" role="group" aria-label="Filter members by engagement">
                        <button type="button" class="member-engagement-tab active" data-engagement-filter="all" aria-pressed="true">All <span class="member-engagement-tab-count" id="engagementCountAll">0</span></button>
                        <button type="button" class="member-engagement-tab" data-engagement-filter="Regular" aria-pressed="false">Regular <span class="member-engagement-tab-count" id="engagementCountRegular">0</span></button>
                        <button type="button" class="member-engagement-tab" data-engagement-filter="Occasional" aria-pressed="false">Occasional <span class="member-engagement-tab-count" id="engagementCountOccasional">0</span></button>
                        <button type="button" class="member-engagement-tab" data-engagement-filter="Not yet attended" aria-pressed="false">Not yet attended <span class="member-engagement-tab-count" id="engagementCountNone">0</span></button>
                    </div>
                    <div class="member-engagement-table-wrap">
                        <table class="table table-sm table-hover member-engagement-table">
                            <thead><tr><th>Member</th><th>Purok</th><th>Attended</th><th>Rate</th><th>Last attended</th><th>Status</th></tr></thead>
                            <tbody id="memberEngagementRows"></tbody>
                        </table>
                        <div class="text-center text-muted py-4" id="memberEngagementEmpty" hidden>No members match these filters.</div>
                    </div>
                    <div class="member-engagement-footer">
                        <span id="memberEngagementResultCount">Showing 0 members</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="memberEngagementToggle" hidden>Show all</button>
                    </div>
                    <small class="member-engagement-note">Regular = attended at least half of completed activities. Occasional = attended some. Not yet attended = none.</small>
                </div>
            </section>
        </div>
        <?php endif; ?>
    </div>

    <!-- Participation heatmap and doughnut chart -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <section class="card-box">
                <div class="card-head align-items-center flex-wrap">
                    <h2 class="d-flex align-items-center gap-2"><i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i>Participation heatmap</h2>
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        <div class="btn-group btn-group-sm" id="heatDimGroup" role="group" aria-label="Group heatmap rows by">
                            <button type="button" class="btn btn-outline-primary" data-dim="center">Center</button>
                            <button type="button" class="btn btn-outline-primary" data-dim="ageGroup">Age</button>
                            <button type="button" class="btn btn-outline-primary active" data-dim="purok">Purok</button>
                            <button type="button" class="btn btn-outline-primary" data-dim="attendance">Attendance</button>
                        </div>
                        <div class="btn-group btn-group-sm" id="heatModeGroup" role="group" aria-label="Heatmap columns">
                            <button type="button" class="btn btn-outline-primary active" data-mode="month">By month</button>
                            <button type="button" class="btn btn-outline-primary" data-mode="activity">By activity</button>
                        </div>
                        <select class="form-select form-select-sm w-auto" id="heatYear" aria-label="Heatmap year"></select>
                    </div>
                </div>
                <div class="card-pad">
                    <p class="card-sub mb-3" id="heatCaption">Darker means more attendance.</p>
                    <div class="heat-scroll" tabindex="0" role="region" aria-label="Participation heatmap, scroll horizontally to see all columns">
                        <div class="heat-grid" id="purokHeatmap" aria-live="polite"></div>
                    </div>
                    <div class="heat-scale justify-content-end"><span>Less</span><span class="heat-scale-blocks" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></span><span>More</span></div>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-5">
            <section class="card-box participation-overview-card">
                <div class="card-head"><div><h2>Attendance Overview</h2><p class="card-sub" id="attendanceOverviewSubtitle">Select a heatmap cell to filter this chart.</p></div></div>
                <div class="card-pad">
                    <div class="attendance-overview-layout">
                        <div class="chart-box attendance-doughnut">
                            <canvas id="attendanceOverviewChart" role="img" aria-label="Doughnut chart of participation by selected heatmap dimension"></canvas>
                            <div class="chart-empty" id="attendanceOverviewChartEmpty" hidden></div>
                        </div>
                        <div class="attendance-overview-legend" id="attendanceOverviewLegend" aria-live="polite"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script type="application/json" id="participationAnalyticsData"><?= json_encode($participationHeatmapRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
    <script type="application/json" id="participationRosterMembers"><?= json_encode(array_map(function ($member) {
        return [
            'id' => (string)($member['id'] ?? ''),
            'memberNo' => (string)($member['memberNo'] ?? $member['id'] ?? ''),
            'name' => participation_full_name($member),
            'purok' => trim((string)($member['purok'] ?? '')) ?: 'Not recorded',
            'status' => (string)($member['status'] ?? ''),
        ];
    }, $participationRosterMembers), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
    <script type="application/json" id="participationRemovedWalkins"><?= json_encode(is_public() ? [] : $participationRemovedWalkins, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
    <script type="application/json" id="participationEventAnalytics"><?= json_encode($participationEventAnalytics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>


    <div class="footer-note">KK Youth Profiling System • SK Barangay Sikat, Alfonso, Cavite • FY 2026–2027</div>
</div>

</main>

<div class="modal fade participation-records-modal" id="participationRecordsModal" tabindex="-1" aria-labelledby="participationRecordsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="participation-records-header-icon"><i class="bi bi-calendar-check"></i></span>
                    <div><h2 class="modal-title fs-5 fw-bold" id="participationRecordsModalTitle">Youth Participation Records</h2><p class="card-sub mb-0">Each activity has its own roster. Recorded attendance keeps its status; members without a record stay Not yet attended.</p></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="filter-bar records-filter-bar mb-3">
                    <div class="filter-title"><i class="bi bi-funnel"></i>Filters</div>
                    <div style="min-width:220px;flex:1 1 220px">
                        <label class="form-label" for="recordActivityFilter">Activity</label>
                        <select class="form-select" id="recordActivityFilter">
                            <option value="">All activities</option>
                            <?php foreach ($participationEventOptions as $eventId => $eventTitle): ?>
                                <option value="<?= e($eventId) ?>"><?= e($eventTitle) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="form-label" for="recordDateFromFilter">From</label><input type="date" class="form-control" id="recordDateFromFilter"></div>
                    <div><label class="form-label" for="recordDateToFilter">To</label><input type="date" class="form-control" id="recordDateToFilter"></div>
                    <div><button class="btn btn-primary" type="button" id="recordsClearFilters" style="min-height:40px;border-radius:10px"><i class="bi bi-arrow-counterclockwise me-1"></i>Clear</button></div>
                    <div id="recordsFilterError" class="small text-danger w-100" role="alert" aria-live="polite"></div>
                </div>
                <div class="all-activities-summary mb-3" id="allActivitiesSummary" hidden>
                    <span class="all-activities-summary-icon"><i class="bi bi-percent"></i></span>
                    <span class="all-activities-summary-copy">
                        <span class="all-activities-summary-label">Attendance rate</span>
                        <span class="all-activities-summary-note" id="allActivitiesSummaryNote">Across completed activities</span>
                    </span>
                    <strong class="all-activities-summary-value" id="allActivitiesAttendanceRate">—</strong>
                </div>
                <div class="roster-action-toolbar" id="rosterActionToolbar" hidden>
                    <div class="search-wrap">
                        <label class="form-label small fw-bold mb-1" for="memberRosterSearch">Find member</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" id="memberRosterSearch" class="form-control" placeholder="Search name or KK number...">
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-bold mb-1" for="rosterAttendanceFilter">Attendance status</label>
                        <select class="form-select" id="rosterAttendanceFilter">
                            <option value="">All statuses</option>
                            <option value="Attended">Attended</option>
                            <option value="Not yet attended">Not yet attended</option>
                        </select>
                    </div>
                </div>
                <p class="small text-muted mb-3" id="rosterActivitySummary">Choose an activity to manage attendance.</p>
                <div class="roster-main-table-wrap" id="rosterMainTableWrap" hidden>
                    <table class="member-roster-table roster-main-table">
                        <thead><tr><th>Member</th><th>Purok</th><th>Activity date</th><th>Attendance status</th></tr></thead>
                        <tbody id="rosterMemberRows"></tbody>
                    </table>
                </div>
                <div class="roster-save-bar" id="rosterSaveBar" hidden>
                    <span class="roster-save-hint" id="rosterSaveHint">Set attendee statuses, then save your changes.</span>
                    <?php if (!is_public()): ?><button type="button" class="btn btn-primary" id="saveRosterAttendance" disabled><i class="bi bi-save2 me-1"></i>Save Attendance</button><?php endif; ?>
                </div>
                <section class="roster-section" id="removedRosterSection" hidden>
                    <div class="roster-section-title"><h3>Removed</h3><span class="roster-count" id="removedSectionCount">0</span></div>
                    <div class="table-responsive"><table class="member-roster-table"><thead><tr><th>Member</th><th>Purok</th><th>Removed</th><?php if (!is_public()): ?><th class="text-end">Manage</th><?php endif; ?></tr></thead><tbody id="removedMemberRows"></tbody></table></div>
                </section>
                <div class="text-center text-muted py-4" id="emptyRosterMessage" hidden>No active KK members found.</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const copCategories = <?= json_encode($participationCopCategories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const participationData = JSON.parse(document.getElementById('participationAnalyticsData').textContent || '[]');
const participationRosterMembers = JSON.parse(document.getElementById('participationRosterMembers').textContent || '[]');
const participationRemovedWalkins = JSON.parse(document.getElementById('participationRemovedWalkins').textContent || '[]');
const participationEventAnalytics = JSON.parse(document.getElementById('participationEventAnalytics').textContent || '{}');
const ageGroups = ['15-17', '18-20', '21-24', '25-30'];
const activityFilter = document.getElementById('participationActivityFilter');
const dateFromFilter = document.getElementById('participationDateFrom');
const dateToFilter = document.getElementById('participationDateTo');
const filterError = document.getElementById('participationFilterError');
const memberRosterActivity = document.getElementById('recordActivityFilter');
const recordDateFromFilter = document.getElementById('recordDateFromFilter');
const recordDateToFilter = document.getElementById('recordDateToFilter');
const recordsFilterError = document.getElementById('recordsFilterError');
const memberRosterSearch = document.getElementById('memberRosterSearch');
const rosterAttendanceFilter = document.getElementById('rosterAttendanceFilter');
const rosterSaveButton = document.getElementById('saveRosterAttendance');
const charts = {};
const pendingRosterChanges = new Map();
let memberEngagementFilter = 'all';
let memberEngagementExpanded = false;
const overviewPalette = ['#3b82f6', '#7c5ce6', '#ff7a59', '#22b8cf', '#f5a524', '#10b981', '#ec4899', '#64748b', '#8b5cf6', '#06b6d4', '#84cc16'];
const activeKkMembers = <?= (int)$participationActiveMembers ?>;
const isPublicView = <?= is_public() ? 'true' : 'false' ?>;
if (typeof Chart !== 'undefined') { Chart.defaults.font.size = 13; Chart.defaults.font.weight = '600'; }

function esc(value) {
    return String(value).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
}
function isValidIsoDate(value) {
    const m = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return false;
    const d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
    return d.getUTCFullYear() === +m[1] && d.getUTCMonth() === +m[2] - 1 && d.getUTCDate() === +m[3];
}
function percent(part, whole) { return whole > 0 ? Math.min(100, Math.round(part / whole * 100)) : 0; }
function syncRecordFilterValues() {
    memberRosterActivity.value = activityFilter.value;
    recordDateFromFilter.value = dateFromFilter.value;
    recordDateToFilter.value = dateToFilter.value;
}
function syncRecordFilterError() {
    recordsFilterError.textContent = filterError.textContent;
}
function normalizedGroup(value, fallback) {
    const label = String(value || '').trim().replace(/\s+/g, ' ');
    return label || fallback;
}
function eventKey(row) {
    return String(row.eventId || (String(row.eventTitle || 'Unknown event') + '|' + String(row.eventDate || '')));
}
function attendanceKey(row) {
    return String(row.memberKey || row.memberId || row.memberName || 'unknown') + '|' + eventKey(row);
}
function distinctAttendances(rows) {
    const seen = new Set();
    return rows.filter(function (row) {
        if (row.attendanceStatus !== 'Attended') return false;
        const key = attendanceKey(row);
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}
function completedActivityAttendanceSummary() {
    const now = new Date();
    const completedEventIds = Object.keys(participationEventAnalytics).filter(function (id) {
        const event = participationEventAnalytics[id] || {};
        if (!event.date || event.cancelled || event.published === false || !isValidIsoDate(event.date)) return false;
        if ((dateFromFilter.value && event.date < dateFromFilter.value) ||
            (dateToFilter.value && event.date > dateToFilter.value)) return false;
        const endTime = event.endTime && /^\d{2}:\d{2}$/.test(event.endTime) ? event.endTime : '23:59';
        const eventEnd = new Date(event.date + 'T' + endTime + ':00');
        return !Number.isNaN(eventEnd.getTime()) && eventEnd < now;
    });
    const activeMemberIds = new Set(participationRosterMembers.filter(function (member) {
        return member.status === 'Active';
    }).map(function (member) { return String(member.id || ''); }));
    const completedEventSet = new Set(completedEventIds);
    const attended = distinctAttendances(participationData.filter(function (row) {
        return completedEventSet.has(String(row.eventId || '')) && activeMemberIds.has(String(row.memberId || ''));
    }));
    const possibleAttendances = activeMemberIds.size * completedEventIds.length;
    return {
        rate: possibleAttendances > 0 ? percent(attended.length, possibleAttendances) : null,
        completedActivityCount: completedEventIds.length,
        activeMemberCount: activeMemberIds.size
    };
}
function renderParticipationHeroMetrics() {
    const from = dateFromFilter.value;
    const to = dateToFilter.value;
    const selectedActivity = activityFilter.value;
    const now = new Date();
    const trackedEvents = Object.keys(participationEventAnalytics).filter(function (eventId) {
        const activity = participationEventAnalytics[eventId] || {};
        if (!activity.date || activity.cancelled || activity.published === false || !isValidIsoDate(activity.date)) return false;
        if (selectedActivity && String(eventId) !== selectedActivity) return false;
        if ((from && activity.date < from) || (to && activity.date > to)) return false;
        return true;
    });
    const completedEventIds = new Set(trackedEvents.filter(function (eventId) {
        const activity = participationEventAnalytics[eventId] || {};
        const endTime = activity.endTime && /^\d{2}:\d{2}$/.test(activity.endTime) ? activity.endTime : '23:59';
        const eventEnd = new Date(activity.date + 'T' + endTime + ':00');
        return !Number.isNaN(eventEnd.getTime()) && eventEnd < now;
    }));
    const activeMemberIds = new Set(participationRosterMembers.filter(function (member) {
        return member.status === 'Active';
    }).map(function (member) { return String(member.id || ''); }));
    const confirmedAttendance = distinctAttendances(participationData.filter(function (record) {
        return completedEventIds.has(String(record.eventId || '')) &&
            activeMemberIds.has(String(record.memberId || ''));
    })).length;

    document.getElementById('heroConfirmedAttendance').textContent = confirmedAttendance.toLocaleString('en-PH');
    document.getElementById('heroActivitiesTracked').textContent = trackedEvents.length.toLocaleString('en-PH');
}
function attendanceStatusRows(rows) {
    const statusByKey = new Map();
    const eventIds = new Set();
    rows.forEach(function (row) {
        const key = attendanceKey(row);
        eventIds.add(String(row.eventId || ''));
        const status = row.attendanceStatus === 'Attended' || row.source === 'walkin'
            ? 'Attended'
            : (row.attendanceStatus === 'Did not attend' ? 'Did not attend' : 'Not recorded');
        if (!statusByKey.has(key) || status === 'Attended') {
            statusByKey.set(key, Object.assign({}, row, { attendanceStatus: status }));
        }
    });

    const now = new Date();
    eventIds.forEach(function (eventId) {
        const event = participationEventAnalytics[eventId] || {};
        if (!event.date || event.cancelled) return;
        const endTime = event.endTime && /^\d{2}:\d{2}$/.test(event.endTime) ? event.endTime : '23:59';
        const eventEnd = new Date(event.date + 'T' + endTime + ':00');
        if (Number.isNaN(eventEnd.getTime()) || eventEnd > now) return;
        participationRosterMembers.forEach(function (member) {
            if (member.status !== 'Active') return;
            const key = attendanceKey({ eventId: eventId, memberKey: String(member.id || '') });
            const existing = statusByKey.get(key);
            if (existing && existing.attendanceStatus === 'Attended') return;
            statusByKey.set(key, Object.assign({}, existing || {
                eventId: eventId,
                eventTitle: event.title || '',
                eventDate: event.date,
                memberKey: String(member.id || ''),
                memberId: String(member.id || ''),
                memberNo: String(member.memberNo || member.id || ''),
                memberName: member.name || '',
                purok: member.purok || 'Not recorded',
                ageGroup: (event.memberAgeGroups || {})[String(member.id || '')] || '',
                centers: Array.isArray(event.centers) ? event.centers : [],
                activityCategory: event.category || 'Uncategorized',
            }, { attendanceStatus: 'Did not attend' }));
        });
    });
    return Array.from(statusByKey.values());
}
/* opts.dates / opts.activity = false para i-skip ang filter na iyon */
function getRows(opts) {
    opts = opts || {};
    const useDates = opts.dates !== false, useActivity = opts.activity !== false;
    const from = dateFromFilter.value, to = dateToFilter.value;
    if (from && to && from > to) {
        filterError.textContent = 'The start date must be on or before the end date.';
        return [];
    }
    filterError.textContent = '';
    const eventId = activityFilter.value;
    return participationData.filter(function (row) {
        if (useActivity && eventId && String(row.eventId || '') !== eventId) return false;
        if (useDates && (from || to)) {
            if (!isValidIsoDate(row.eventDate)) return false;
            if ((from && row.eventDate < from) || (to && row.eventDate > to)) return false;
        }
        return true;
    });
}
function groupsForRow(row, dimension) {
    if (dimension === 'attendance') {
        const status = row.attendanceStatus === 'Attended' || row.source === 'walkin'
            ? 'Attended'
            : (row.attendanceStatus === 'Did not attend' ? 'Did not attend' : 'Not recorded');
        return [status];
    }
    if (dimension === 'center') {
        const centers = Array.isArray(row.centers) ? row.centers : [];
        return centers.length ? centers.map(function (v) { return normalizedGroup(v, 'Not categorized'); }) : ['Not categorized'];
    }
    if (dimension === 'ageGroup') {
        const g = normalizedGroup(row.ageGroup, '');
        return ageGroups.indexOf(g) === -1 ? [] : [g];
    }
    const purok = normalizedGroup(row.purok, 'Not recorded');
    const match = purok.match(/^purok\s*(\d+)$/i);
    return [match ? 'Purok ' + match[1] : purok];
}
function chartTheme() {
    const s = getComputedStyle(document.body);
    return {
        text: s.getPropertyValue('--bs-body-color').trim() || '#20324d',
        muted: s.getPropertyValue('--muted').trim() || '#5c6e86',
        grid: s.getPropertyValue('--p-grid').trim() || '#e8e6f8',
        surface: s.getPropertyValue('--p-card').trim() || '#fff'
    };
}
function replaceChart(canvasId, config, hasData) {
    const canvas = document.getElementById(canvasId);
    const empty = document.getElementById(canvasId + 'Empty');
    if (charts[canvasId]) { charts[canvasId].destroy(); delete charts[canvasId]; }
    if (!hasData) {
        canvas.hidden = true; empty.hidden = false;
        empty.textContent = 'No confirmed attendance matches these filters.';
        return;
    }
    if (typeof Chart === 'undefined') {
        canvas.hidden = true; empty.hidden = false;
        empty.textContent = 'Charts could not load because Chart.js is unavailable.';
        return;
    }
    canvas.hidden = false; empty.hidden = true;
    charts[canvasId] = new Chart(canvas, config);
}

function renderAttendanceOverview(rows) {
    const theme = chartTheme();
    const statusLabels = ['Attended', 'Did not attend', 'Not recorded'];
    const statusColors = ['#16a34a', '#ef4444', '#94a3b8'];
    const labels = heatDimensionGroups[heatState.dim] || statusLabels;
    const selection = heatSelection && heatSelection.dim === heatState.dim &&
        heatSelection.mode === heatState.mode && heatSelection.year === heatState.year
        ? heatSelection : null;
    const chartRows = (heatState.dim === 'attendance' ? attendanceStatusRows(rows) : distinctAttendances(rows)).filter(function (row) {
        if (!selection) return true;
        const hasGroup = groupsForRow(row, selection.dim).indexOf(selection.group) !== -1;
        const hasColumn = selection.mode === 'month'
            ? String(row.eventDate || '').slice(5, 7) === selection.columnKey
            : eventKey(row) === selection.columnKey;
        return hasGroup && hasColumn;
    });
    const counts = labels.map(function (label) {
        const members = new Set();
        chartRows.forEach(function (row) {
            if (groupsForRow(row, heatState.dim).indexOf(label) !== -1) members.add(attendanceKey(row));
        });
        return members.size;
    });
    const colors = heatState.dim === 'attendance'
        ? statusColors
        : labels.map(function (_label, index) { return overviewPalette[index % overviewPalette.length]; });
    const total = counts.reduce(function (sum, count) { return sum + count; }, 0);
    const allActivitiesMode = !activityFilter.value;
    const scope = selection
        ? selection.group + ' · ' + selection.columnLabel + ' · ' + selection.year
        : 'All heatmap data · ' + heatState.year;
    document.getElementById('attendanceOverviewSubtitle').textContent =
        allActivitiesMode ? scope + ' · percentage distribution' : scope + ' · ' + total.toLocaleString('en-PH') + ' records';
    document.getElementById('attendanceOverviewLegend').innerHTML = labels.map(function (label, index) {
        const value = allActivitiesMode
            ? (total > 0 ? (counts[index] / total * 100).toFixed(1) + '%' : '0%')
            : counts[index].toLocaleString('en-PH');
        return '<div class="attendance-overview-item"><span class="attendance-overview-dot" style="background:' + colors[index] +
            '"></span><span title="' + esc(label) + '">' + esc(label) + '</span><strong>' +
            value + '</strong></div>';
    }).join('');
    document.getElementById('attendanceOverviewChart').setAttribute(
        'aria-label',
        'Doughnut chart showing ' + heatDimTitles[heatState.dim] + (allActivitiesMode ? ' percentage distribution' : ' counts')
    );
    replaceChart('attendanceOverviewChart', {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{ data: counts, backgroundColor: colors, borderColor: theme.surface, borderWidth: 3, hoverOffset: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '62%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (context) {
                    const value = Number(context.raw);
                    return ' ' + context.label + ': ' + (allActivitiesMode && total > 0
                        ? (value / total * 100).toFixed(1) + '%'
                        : value.toLocaleString('en-PH'));
                } } }
            }
        },
        plugins: [{
            id: 'doughnutCenterTotal',
            afterDraw: function (chart) {
                const area = chart.chartArea;
                if (!area) return;
                const centerX = (area.left + area.right) / 2;
                const centerY = (area.top + area.bottom) / 2;
                const countText = total.toLocaleString('en-PH');
                const countFont = Math.min(28, area.height * 0.18, area.width / (countText.length * 0.68));
                const labelFont = Math.max(9, Math.min(12, area.height * 0.075));
                const ctx = chart.ctx;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = theme.text;
                ctx.font = '800 ' + countFont + 'px system-ui, sans-serif';
                ctx.fillText(countText, centerX, centerY - labelFont * 0.55);
                ctx.fillStyle = theme.muted;
                ctx.font = '700 ' + labelFont + 'px system-ui, sans-serif';
                ctx.fillText('TOTAL', centerX, centerY + countFont * 0.72);
                ctx.restore();
            }
        }]
    }, total > 0);
}

/* ---------- HEATMAP ---------- */
const heatState = { dim: 'purok', mode: 'month', year: '' };
let heatSelection = null;
const heatDimTitles = { center: 'Center of Participation', ageGroup: 'Age group', purok: 'Purok', attendance: 'Attendance status' };
const heatDimensionGroups = {
    center: copCategories.concat(['Not categorized']),
    ageGroup: ageGroups.slice(),
    purok: ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7', 'Not recorded'],
    attendance: ['Attended', 'Did not attend', 'Not recorded']
};
const monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
function syncHeatButtons() {
    document.querySelectorAll('#heatDimGroup button').forEach(function (b) { b.classList.toggle('active', b.dataset.dim === heatState.dim); });
    document.querySelectorAll('#heatModeGroup button').forEach(function (b) { b.classList.toggle('active', b.dataset.mode === heatState.mode); });
}
function initHeatControls() {
    const thisYear = String(new Date().getFullYear());
    const years = new Set([thisYear]);
    participationData.forEach(function (r) { if (isValidIsoDate(r.eventDate)) years.add(r.eventDate.slice(0, 4)); });
    const sel = document.getElementById('heatYear');
    sel.innerHTML = Array.from(years).sort().reverse().map(function (y) { return '<option value="' + y + '">' + y + '</option>'; }).join('');
    heatState.year = thisYear;
    sel.value = thisYear;
    sel.addEventListener('change', function () { heatState.year = sel.value; heatSelection = null; renderHeatmap(); });
    document.getElementById('heatDimGroup').addEventListener('click', function (e) {
        const b = e.target.closest('button[data-dim]');
        if (!b) return;
        heatState.dim = b.dataset.dim; heatSelection = null; syncHeatButtons(); renderHeatmap();
    });
    document.getElementById('heatModeGroup').addEventListener('click', function (e) {
        const b = e.target.closest('button[data-mode]');
        if (!b) return;
        heatState.mode = b.dataset.mode; heatSelection = null; syncHeatButtons(); renderHeatmap();
    });
    document.getElementById('purokHeatmap').addEventListener('click', function (e) {
        const cell = e.target.closest('button[data-heat-group]');
        if (!cell) return;
        const selection = {
            dim: heatState.dim,
            mode: heatState.mode,
            year: heatState.year,
            group: cell.dataset.heatGroup,
            columnKey: cell.dataset.heatColumn,
            columnLabel: cell.dataset.heatLabel
        };
        const sameSelection = heatSelection &&
            heatSelection.dim === selection.dim && heatSelection.mode === selection.mode &&
            heatSelection.year === selection.year && heatSelection.group === selection.group &&
            heatSelection.columnKey === selection.columnKey;
        heatSelection = sameSelection ? null : selection;
        renderHeatmap();
    });
    syncHeatButtons();
}
function renderHeatmap() {
    const root = document.getElementById('purokHeatmap');
    const dim = heatState.dim, byMonth = heatState.mode === 'month';
    const rows = getRows({ dates: false }).filter(function (r) { return isValidIsoDate(r.eventDate) && r.eventDate.slice(0, 4) === heatState.year; });
    const attended = distinctAttendances(rows);
    const heatRows = dim === 'attendance' ? attendanceStatusRows(rows) : attended;
    document.getElementById('heatCaption').textContent = (dim === 'attendance' ? 'Attendance status' : 'Confirmed attendance') +
        ' in ' + heatState.year + ' by ' + (byMonth ? 'month' : 'activity') + '. Darker means more ' +
        (dim === 'attendance' ? 'records.' : 'attendance.');
    let cols;
    if (byMonth) {
        cols = monthShort.map(function (m, i) { return { key: String(i + 1).padStart(2, '0'), label: m }; });
    } else {
        const seen = new Map();
        heatRows.forEach(function (r) { const k = eventKey(r); if (!seen.has(k)) seen.set(k, { key: k, label: r.eventTitle || 'Activity', date: r.eventDate }); });
        cols = Array.from(seen.values()).sort(function (a, b) { return String(a.date).localeCompare(String(b.date)); });
    }
    const found = new Set(), cells = new Map(), rowTotals = new Map();
    heatRows.forEach(function (r) {
        const colKey = byMonth ? r.eventDate.slice(5, 7) : eventKey(r), pair = attendanceKey(r);
        groupsForRow(r, dim).forEach(function (g) {
            found.add(g);
            const key = g + '\u001f' + colKey;
            if (!cells.has(key)) cells.set(key, new Set());
            cells.get(key).add(pair);
        });
    });
    const groups = heatDimensionGroups[dim] || [];
    let max = 0;
    cells.forEach(function (set, key) {
        max = Math.max(max, set.size);
        const g = key.slice(0, key.indexOf('\u001f'));
        rowTotals.set(g, (rowTotals.get(g) || 0) + set.size);
    });
    root.style.gridTemplateColumns = 'minmax(150px,1.4fr) repeat(' + cols.length + ',minmax(' + (byMonth ? 64 : 84) + 'px,1fr)) 70px';
    let html = '<div class="heat-corner">' + esc(heatDimTitles[dim]) + '</div>';
    cols.forEach(function (c) { html += '<div class="heat-col-head" title="' + esc(c.label) + '"><span>' + esc(c.label) + '</span></div>'; });
    html += '<div class="heat-col-head">Total</div>';
    groups.forEach(function (g) {
        html += '<div class="heat-row-head"><span>' + esc(g) + '</span></div>';
        cols.forEach(function (c) {
            const count = (cells.get(g + '\u001f' + c.key) || new Set()).size;
            const lvl = count ? Math.max(1, Math.ceil(count / max * 5)) : 0;
            const label = g + ', ' + c.label + ': ' + count +
                (dim === 'attendance' ? ' attendance record' : ' confirmed attendance') + (count === 1 ? '' : 's');
            const selected = heatSelection && heatSelection.dim === dim && heatSelection.mode === heatState.mode &&
                heatSelection.year === heatState.year && heatSelection.group === g && heatSelection.columnKey === c.key;
            html += '<button type="button" class="heat-cell heat-l' + lvl + (selected ? ' is-selected' : '') +
                '" data-heat-group="' + esc(g) + '" data-heat-column="' + esc(c.key) + '" data-heat-label="' +
                esc(c.label) + '" aria-label="' + esc(label + '. Select to filter Attendance Overview') +
                '" aria-pressed="' + (selected ? 'true' : 'false') + '" title="' + esc(label) + '">' +
                (count || '') + '</button>';
        });
        html += '<div class="heat-total">' + (rowTotals.get(g) || 0) + '</div>';
    });
    root.innerHTML = html;
    renderAttendanceOverview(rows);
}

/* ---------- RECORDS TABLE ---------- */
function syncRosterSaveButton() {
    if (!rosterSaveButton) return;
    const eventId = memberRosterActivity.value;
    const hasPendingChanges = Array.from(pendingRosterChanges.values()).some(function (change) {
        return change.eventId === eventId;
    });
    rosterSaveButton.disabled = isPublicView || !eventId || !hasPendingChanges;
    const hint = document.getElementById('rosterSaveHint');
    if (hint) hint.textContent = hasPendingChanges
        ? 'You have unsaved attendance changes.'
        : 'Set attendee statuses, then save your changes.';
}

function renderRecords() {
    const eventId = memberRosterActivity.value;
    const memberBody = document.getElementById('rosterMemberRows');
    const removedBody = document.getElementById('removedMemberRows');
    const rosterActionToolbar = document.getElementById('rosterActionToolbar');
    const rosterMainTableWrap = document.getElementById('rosterMainTableWrap');
    const rosterSaveBar = document.getElementById('rosterSaveBar');
    const rosterActivitySummary = document.getElementById('rosterActivitySummary');
    const emptyMessage = document.getElementById('emptyRosterMessage');
    const activeMembers = participationRosterMembers.filter(function (member) { return member.status === 'Active'; });
    memberBody.innerHTML = '';
    removedBody.innerHTML = '';

    if (!eventId) {
        const summary = document.getElementById('allActivitiesSummary');
        const summaryNote = document.getElementById('allActivitiesSummaryNote');
        const invalidRange = dateFromFilter.value && dateToFilter.value && dateFromFilter.value > dateToFilter.value;
        const attendanceSummary = completedActivityAttendanceSummary();
        document.getElementById('allActivitiesAttendanceRate').textContent =
            !invalidRange && attendanceSummary.rate !== null ? attendanceSummary.rate + '%' : '—';
        summaryNote.textContent = invalidRange
            ? 'Correct the selected date range to calculate this percentage.'
            : (attendanceSummary.rate !== null
                ? 'Across completed activities'
                : 'No completed activities or active members in this range.');
        summary.hidden = false;
        rosterActionToolbar.hidden = true;
        rosterMainTableWrap.hidden = true;
        rosterSaveBar.hidden = true;
        rosterActivitySummary.hidden = true;
        document.getElementById('removedRosterSection').hidden = true;
        emptyMessage.hidden = true;
        document.getElementById('removedSectionCount').textContent = '0';
        syncRosterSaveButton();
        return;
    }
    document.getElementById('allActivitiesSummary').hidden = true;
    rosterActivitySummary.hidden = false;
    const eventDate = (participationEventAnalytics[eventId] || {}).date || '';
    const invalidRange = dateFromFilter.value && dateToFilter.value && dateFromFilter.value > dateToFilter.value;
    const outsideRange = (dateFromFilter.value && (!isValidIsoDate(eventDate) || eventDate < dateFromFilter.value)) ||
        (dateToFilter.value && (!isValidIsoDate(eventDate) || eventDate > dateToFilter.value));
    if (invalidRange || outsideRange) {
        rosterActivitySummary.textContent = invalidRange
            ? 'Correct the selected date range to view this activity.'
            : 'This activity is outside the selected date range.';
        emptyMessage.textContent = rosterActivitySummary.textContent;
        emptyMessage.hidden = false;
        rosterActionToolbar.hidden = true;
        rosterMainTableWrap.hidden = true;
        rosterSaveBar.hidden = true;
        document.getElementById('removedSectionCount').textContent = '0';
        document.getElementById('removedRosterSection').hidden = true;
        syncRosterSaveButton();
        return;
    }
    rosterActionToolbar.hidden = false;
    rosterSaveBar.hidden = isPublicView;
    emptyMessage.textContent = 'No active KK members found.';

    const searchTerm = memberRosterSearch.value.trim().toLocaleLowerCase();
    const statusFilter = rosterAttendanceFilter.value;
    const currentEntries = [];
    const removed = [];
    const changeKey = function (kind, recordId, memberId) {
        return eventId + ':' + kind + ':' + String(recordId || memberId || '');
    };
    const rowHtml = function (member, status, kind, recordId, memberId) {
        const key = changeKey(kind, recordId, memberId);
        const stagedChange = pendingRosterChanges.get(key);
        const shownStatus = stagedChange ? stagedChange.status : status;
        let controlHtml = '<span class="badge text-bg-light border">' + esc(shownStatus) + '</span>';
        if (!isPublicView) {
            const options = kind === 'signup'
                ? ['Attended', 'Did not attend', 'Not recorded']
                : (kind === 'walkin' ? ['Attended', 'Not yet attended'] : ['Not yet attended', 'Attended']);
            controlHtml = '<select class="form-select form-select-sm attendance-status-select' +
                (stagedChange ? ' is-staged' : '') + '" data-roster-status="true" data-change-key="' + esc(key) +
                '" data-change-kind="' + esc(kind) + '" data-record-id="' + esc(recordId || '') +
                '" data-member-id="' + esc(memberId || '') + '" data-original-status="' + esc(status) +
                '" aria-label="Attendance status for ' + esc(member.name || 'member') + '"' +
                ((kind === 'signup' || kind === 'walkin') && !recordId ? ' disabled' : '') + '>' +
                options.map(function (option) {
                    return '<option value="' + esc(option) + '"' + (option === shownStatus ? ' selected' : '') + '>' +
                        esc(option) + '</option>';
                }).join('') + '</select>';
        }
        const sourceLabel = kind === 'walkin' ? 'Walk-in' : (kind === 'signup' ? 'Registered' : 'No record');
        return '<tr class="member-roster-row' + (shownStatus === 'Attended' ? ' is-attended' : '') + '">' +
            '<td><span class="member-roster-name">' + esc(member.name || 'Member record unavailable') +
            '</span><span class="member-roster-no">' + esc(member.memberNo || member.id || '') + ' · ' + sourceLabel + '</span></td>' +
            '<td><span class="member-roster-purok">' + esc(member.purok || 'Not recorded') + '</span></td>' +
            '<td>' + esc(eventDate || 'Not recorded') + '</td><td>' + controlHtml + '</td></tr>';
    };
    const matchesSearch = function (member) {
        if (!searchTerm) return true;
        return [member.name, member.memberNo, member.id, member.purok].join(' ').toLocaleLowerCase().indexOf(searchTerm) !== -1;
    };
    activeMembers.forEach(function (member) {
        const memberId = String(member.id || '');
        const removedRecord = participationRemovedWalkins.find(function (record) {
            return String(record.eventId || '') === eventId && String(record.memberId || '') === memberId;
        });
        const signup = participationData.find(function (record) {
            return record.source === 'signup' && String(record.eventId || '') === eventId &&
                String(record.memberId || '') === memberId;
        });
        const walkin = participationData.find(function (record) {
            return record.source === 'walkin' && String(record.eventId || '') === eventId &&
                String(record.memberId || '') === memberId;
        });
        if (removedRecord && !signup && !walkin) {
            removed.push({ member: member, record: removedRecord });
            return;
        }
        const record = signup || walkin;
        const kind = signup ? 'signup' : (walkin ? 'walkin' : 'new');
        const status = signup ? (signup.attendanceStatus || 'Not recorded') : (walkin ? 'Attended' : 'Not yet attended');
        if (!matchesSearch(member)) return;
        const key = changeKey(kind, record && record.recordId, memberId);
        const stagedChange = pendingRosterChanges.get(key);
        const shownStatus = stagedChange ? stagedChange.status : status;
        if (statusFilter === 'Attended' && shownStatus !== 'Attended') return;
        if (statusFilter === 'Not yet attended' && shownStatus === 'Attended') return;
        currentEntries.push({
            member: member,
            status: status,
            kind: kind,
            recordId: record && record.recordId,
            memberId: memberId,
            shownStatus: shownStatus
        });
    });
    currentEntries.forEach(function (entry) {
        memberBody.insertAdjacentHTML('beforeend', rowHtml(entry.member, entry.status, entry.kind, entry.recordId, entry.memberId));
    });

    const memberById = new Map(participationRosterMembers.map(function (member) { return [String(member.id), member]; }));
    participationRemovedWalkins.filter(function (record) {
        return String(record.eventId || '') === eventId &&
            !activeMembers.some(function (member) { return String(member.id) === String(record.memberId || ''); });
    }).forEach(function (record) {
        const member = memberById.get(String(record.memberId || '')) || {
            id: record.memberId, memberNo: record.memberId, name: 'Member record unavailable', purok: 'Not recorded'
        };
        removed.push({ member: member, record: record });
    });
    const visibleRemoved = removed.filter(function (entry) {
        return !statusFilter && (!searchTerm || matchesSearch(entry.member));
    });
    visibleRemoved.forEach(function (entry) {
        const removedDate = entry.record.removedAt ? String(entry.record.removedAt).slice(0, 10) : 'Removed';
        const controls = isPublicView ? '' : '<button type="button" class="btn btn-sm btn-outline-primary" data-roster-action="restore" data-record-id="' +
            esc(entry.record.id || '') + '">Restore</button>';
        const actionCell = isPublicView ? '' : '<td class="text-end">' + controls + '</td>';
        removedBody.insertAdjacentHTML('beforeend',
            '<tr class="member-roster-row"><td><span class="member-roster-name">' +
            esc(entry.member.name || 'Member record unavailable') + '</span><span class="member-roster-no">' +
            esc(entry.member.memberNo || entry.member.id || '') + '</span></td><td><span class="member-roster-purok">' +
            esc(entry.member.purok || 'Not recorded') + '</span></td><td>' + esc(removedDate) + '</td>' + actionCell + '</tr>');
    });

    const selectedTitle = memberRosterActivity.options[memberRosterActivity.selectedIndex].text;
    const attendedCount = currentEntries.filter(function (entry) { return entry.shownStatus === 'Attended'; }).length;
    const pendingCount = currentEntries.length - attendedCount;
    rosterActivitySummary.textContent = selectedTitle + ' · ' + attendedCount + ' attended · ' +
        pendingCount + ' not yet attended';
    document.getElementById('removedSectionCount').textContent = String(visibleRemoved.length);
    document.getElementById('removedRosterSection').hidden = visibleRemoved.length === 0;
    rosterMainTableWrap.hidden = currentEntries.length === 0;
    emptyMessage.textContent = statusFilter || searchTerm
        ? 'No members match these filters.'
        : 'No active KK members found.';
    emptyMessage.hidden = currentEntries.length > 0 || visibleRemoved.length > 0;
    syncRosterSaveButton();
}

function renderMemberEngagement() {
    const table = document.getElementById('memberEngagementRows');
    if (!table) return;

    const from = dateFromFilter.value;
    const to = dateToFilter.value;
    const selectedActivity = activityFilter.value;
    const now = new Date();
    const completedEvents = Object.keys(participationEventAnalytics).filter(function (eventId) {
        const activity = participationEventAnalytics[eventId] || {};
        if (!activity.date || activity.cancelled || activity.published === false || !isValidIsoDate(activity.date)) return false;
        if (selectedActivity && String(eventId) !== selectedActivity) return false;
        if ((from && activity.date < from) || (to && activity.date > to)) return false;
        const endTime = activity.endTime && /^\d{2}:\d{2}$/.test(activity.endTime) ? activity.endTime : '23:59';
        const eventEnd = new Date(activity.date + 'T' + endTime + ':00');
        return !Number.isNaN(eventEnd.getTime()) && eventEnd < now;
    });
    const completedEventIds = new Set(completedEvents);
    const activeMembers = participationRosterMembers.filter(function (member) {
        return member.status === 'Active';
    });
    const attendanceByMember = new Map(activeMembers.map(function (member) {
        return [String(member.id || ''), { attendedEvents: new Set(), lastAttended: '' }];
    }));

    distinctAttendances(getRows()).forEach(function (record) {
        const memberAttendance = attendanceByMember.get(String(record.memberId || ''));
        const eventId = String(record.eventId || '');
        if (!memberAttendance || !completedEventIds.has(eventId)) return;
        memberAttendance.attendedEvents.add(eventId);
        const eventDate = String(record.eventDate || '');
        if (isValidIsoDate(eventDate) && eventDate > memberAttendance.lastAttended) {
            memberAttendance.lastAttended = eventDate;
        }
    });

    const activityCount = completedEvents.length;
    const members = activeMembers.map(function (member) {
        const attendance = attendanceByMember.get(String(member.id || ''));
        const attendedCount = attendance.attendedEvents.size;
        const rate = activityCount ? percent(attendedCount, activityCount) : 0;
        return {
            member: member,
            attendedCount: attendedCount,
            rate: rate,
            lastAttended: attendance.lastAttended,
            status: attendedCount === 0 ? 'Not yet attended' : (attendedCount * 2 >= activityCount ? 'Regular' : 'Occasional')
        };
    }).sort(function (a, b) {
        return b.attendedCount - a.attendedCount ||
            String(a.member.name || '').localeCompare(String(b.member.name || ''));
    });

    document.getElementById('memberEngagementScope').textContent =
        activityCount + ' completed activit' + (activityCount === 1 ? 'y' : 'ies') + ' · based on selected filters';
    document.getElementById('engagementCountAll').textContent = String(members.length);
    document.getElementById('engagementCountRegular').textContent = String(members.filter(function (item) { return item.status === 'Regular'; }).length);
    document.getElementById('engagementCountOccasional').textContent = String(members.filter(function (item) { return item.status === 'Occasional'; }).length);
    document.getElementById('engagementCountNone').textContent = String(members.filter(function (item) { return item.status === 'Not yet attended'; }).length);

    const search = document.getElementById('memberEngagementSearch').value.trim().toLocaleLowerCase();
    const filtered = members.filter(function (item) {
        const member = item.member;
        const matchesFilter = memberEngagementFilter === 'all' || item.status === memberEngagementFilter;
        const searchableText = [member.name, member.memberNo, member.id, member.purok].join(' ').toLocaleLowerCase();
        return matchesFilter && (!search || searchableText.indexOf(search) !== -1);
    });
    const visible = memberEngagementExpanded ? filtered : filtered.slice(0, 8);
    table.innerHTML = visible.map(function (item) {
        const member = item.member;
        const statusClass = item.status === 'Regular' ? 'regular' : (item.status === 'Occasional' ? 'occasional' : 'none');
        const lastAttended = item.lastAttended
            ? new Date(item.lastAttended + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            : '—';
        const rate = activityCount ? item.rate + '%' : '—';
        return '<tr><td><span class="member-engagement-name">' + esc(member.name || 'Member record unavailable') +
            '</span><span class="member-engagement-id">' + esc(member.memberNo || member.id || '') +
            '</span></td><td>' + esc(member.purok || 'Not recorded') + '</td><td>' +
            item.attendedCount + ' of ' + activityCount + '</td><td><div class="member-engagement-rate"><span class="member-engagement-progress"><span style="width:' +
            item.rate + '%"></span></span><strong>' + rate + '</strong></div></td><td>' + esc(lastAttended) +
            '</td><td><span class="member-engagement-status ' + statusClass + '">' + esc(item.status) + '</span></td></tr>';
    }).join('');

    document.getElementById('memberEngagementEmpty').hidden = filtered.length > 0;
    document.getElementById('memberEngagementResultCount').textContent = filtered.length
        ? 'Showing ' + visible.length + ' of ' + filtered.length + ' members'
        : 'No members match these filters.';
    const toggle = document.getElementById('memberEngagementToggle');
    toggle.hidden = filtered.length <= 8;
    toggle.textContent = memberEngagementExpanded ? 'Show fewer' : 'Show all ' + filtered.length;
    document.querySelectorAll('#memberEngagementTabs [data-engagement-filter]').forEach(function (button) {
        const active = button.dataset.engagementFilter === memberEngagementFilter;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

/* ---------- MAIN ---------- */
function renderAll() {
    renderParticipationHeroMetrics();
    renderHeatmap();
    renderMemberEngagement();
    renderRecords();
    syncRecordFilterError();
}

const memberEngagementSearch = document.getElementById('memberEngagementSearch');
const memberEngagementTabs = document.getElementById('memberEngagementTabs');
const memberEngagementToggle = document.getElementById('memberEngagementToggle');
if (memberEngagementSearch) memberEngagementSearch.addEventListener('input', function () {
    memberEngagementExpanded = false;
    renderMemberEngagement();
});
if (memberEngagementTabs) memberEngagementTabs.addEventListener('click', function (event) {
    const button = event.target.closest('[data-engagement-filter]');
    if (!button) return;
    memberEngagementFilter = button.dataset.engagementFilter;
    memberEngagementExpanded = false;
    renderMemberEngagement();
});
if (memberEngagementToggle) memberEngagementToggle.addEventListener('click', function () {
    memberEngagementExpanded = !memberEngagementExpanded;
    renderMemberEngagement();
});

['participationActivityFilter', 'participationDateFrom', 'participationDateTo'].forEach(function (id) {
    const el = document.getElementById(id);
    const update = function () { heatSelection = null; syncRecordFilterValues(); renderAll(); };
    el.addEventListener('input', update);
    el.addEventListener('change', update);
});
memberRosterActivity.addEventListener('change', function () {
    activityFilter.value = memberRosterActivity.value;
    heatSelection = null;
    renderAll();
});
memberRosterSearch.addEventListener('input', renderRecords);
rosterAttendanceFilter.addEventListener('change', renderRecords);
[[recordDateFromFilter, dateFromFilter], [recordDateToFilter, dateToFilter]].forEach(function (pair) {
    const recordFilter = pair[0], dashboardFilter = pair[1];
    const update = function () {
        dashboardFilter.value = recordFilter.value;
        heatSelection = null;
        renderAll();
    };
    recordFilter.addEventListener('input', update);
    recordFilter.addEventListener('change', update);
});

function rosterPost(action, payload, control, onSuccess, onFailure) {
    if (control) control.disabled = true;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api.php?action=' + encodeURIComponent(action), true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-Token', window.SK_CSRF_TOKEN || '');
    xhr.onload = function () {
        let res = {};
        try { res = JSON.parse(xhr.responseText); } catch (ex) {}
        if (xhr.status !== 200 || !res.ok) {
            if (control) control.disabled = false;
            if (onFailure) onFailure();
            if (window.AppAlerts) AppAlerts.error(res.error || 'Could not update event attendance.');
            return;
        }
        onSuccess(res);
    };
    xhr.onerror = function () {
        if (control) control.disabled = false;
        if (onFailure) onFailure();
        if (window.AppAlerts) AppAlerts.error('No connection. Could not update event attendance.');
    };
    xhr.send(JSON.stringify(payload));
}
function analyticsRowForWalkin(record) {
    const member = participationRosterMembers.find(function (item) { return String(item.id) === String(record.memberId); }) || {};
    const event = participationEventAnalytics[String(record.eventId)] || {};
    return {
        source: 'walkin',
        recordId: String(record.id || ''),
        memberKey: String(record.memberId || ''),
        memberId: String(record.memberId || ''),
        memberNo: String(member.memberNo || record.memberId || ''),
        memberName: member.name || 'Member record unavailable',
        eventId: String(record.eventId || ''),
        eventTitle: record.eventTitle || event.title || '',
        eventDate: event.date || '',
        centers: Array.isArray(event.centers) ? event.centers : [],
        activityCategory: event.category || 'Uncategorized',
        attendanceStatus: 'Attended',
        ageGroup: 'Unknown',
        purok: member.purok || 'Not recorded'
    };
}
document.getElementById('participationRecordsModal').addEventListener('click', function (event) {
    const control = event.target.closest('[data-roster-action]');
    if (!control || isPublicView) return;
    const action = control.dataset.rosterAction;
    const eventId = memberRosterActivity.value;
    if (!eventId || action !== 'restore') return;
    const recordId = control.dataset.recordId;
    if (!recordId) return;
    rosterPost('event_walkin_restore', { eventId: eventId, recordId: recordId }, control, function (res) {
        const index = participationRemovedWalkins.findIndex(function (item) {
            return String(item.id || '') === recordId;
        });
        if (index !== -1) participationRemovedWalkins.splice(index, 1);
        participationData.push(analyticsRowForWalkin(res.record));
        renderAll();
        if (window.AppAlerts) AppAlerts.success('Walk-in attendance restored.');
    });
});
document.getElementById('participationRecordsModal').addEventListener('change', function (event) {
    const control = event.target.closest('[data-roster-status="true"]');
    if (!control || isPublicView) return;
    const key = control.dataset.changeKey;
    const originalStatus = control.dataset.originalStatus;
    if (!key || !originalStatus) return;
    if (control.value === originalStatus) {
        pendingRosterChanges.delete(key);
    } else {
        pendingRosterChanges.set(key, {
            eventId: memberRosterActivity.value,
            kind: control.dataset.changeKind,
            recordId: control.dataset.recordId,
            memberId: control.dataset.memberId,
            originalStatus: originalStatus,
            status: control.value
        });
    }
    control.classList.toggle('is-staged', control.value !== originalStatus);
    syncRosterSaveButton();
});
function saveRosterAttendanceChanges() {
    const eventId = memberRosterActivity.value;
    if (!eventId || isPublicView) return;
    const changes = Array.from(pendingRosterChanges.entries()).filter(function (entry) {
        return entry[1].eventId === eventId;
    });
    if (!changes.length) return;

    let savedCount = 0;
    const saveNext = function (index) {
        if (index >= changes.length) {
            renderAll();
            if (window.AppAlerts) AppAlerts.success(savedCount + ' attendance change' + (savedCount === 1 ? '' : 's') + ' saved.');
            return;
        }
        const key = changes[index][0];
        const change = changes[index][1];
        let action = '';
        let payload = { eventId: eventId };
        if (change.kind === 'signup') {
            action = 'event_attendance';
            payload.registrationId = change.recordId;
            payload.status = change.status;
        } else if (change.kind === 'walkin' && change.status === 'Not yet attended') {
            action = 'event_walkin_remove';
            payload.recordId = change.recordId;
        } else if (change.kind === 'new' && change.status === 'Attended') {
            action = 'event_walkin';
            payload.memberId = change.memberId;
        } else {
            pendingRosterChanges.delete(key);
            saveNext(index + 1);
            return;
        }

        rosterPost(action, payload, rosterSaveButton, function (res) {
            if (change.kind === 'signup') {
                const record = participationData.find(function (item) {
                    return item.source === 'signup' && String(item.recordId || '') === String(change.recordId) &&
                        String(item.eventId || '') === eventId;
                });
                if (record) record.attendanceStatus = res.registration.attendanceStatus;
            } else if (change.kind === 'walkin') {
                const recordIndex = participationData.findIndex(function (item) {
                    return item.source === 'walkin' && String(item.recordId || '') === String(change.recordId);
                });
                if (recordIndex !== -1) participationData.splice(recordIndex, 1);
                participationRemovedWalkins.push(res.record);
            } else {
                participationData.push(analyticsRowForWalkin(res.record));
            }
            pendingRosterChanges.delete(key);
            savedCount += 1;
            saveNext(index + 1);
        }, function () {
            renderAll();
            const hint = document.getElementById('rosterSaveHint');
            if (hint && savedCount > 0) {
                hint.textContent = savedCount + ' change' + (savedCount === 1 ? '' : 's') +
                    ' saved. The remaining changes are still staged and can be retried.';
            }
        });
    };
    saveNext(0);
}
if (rosterSaveButton) rosterSaveButton.addEventListener('click', saveRosterAttendanceChanges);
function clearParticipationFilters() {
    activityFilter.value = '';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    memberRosterSearch.value = '';
    rosterAttendanceFilter.value = '';
    if (memberEngagementSearch) memberEngagementSearch.value = '';
    memberEngagementFilter = 'all';
    memberEngagementExpanded = false;
    heatSelection = null;
    syncRecordFilterValues();
    renderAll();
}
document.getElementById('clearParticipationFilters').addEventListener('click', clearParticipationFilters);
document.getElementById('recordsClearFilters').addEventListener('click', clearParticipationFilters);
initHeatControls();
syncRecordFilterValues();
new MutationObserver(renderAll).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
renderAll();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>