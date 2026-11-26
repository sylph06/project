<?php
require_once __DIR__ . '/config.php';
require_official();
?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Event Attendance</title>

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

        body { margin: 0; }

        body.attendance-page {
            background:var(--mem-bg)!important;
            color:var(--mem-ink)!important;
            font-size:15px;
            line-height:1.45;
            font-family:ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .top-strip { height: 5px; background: var(--mem-brand)!important; }

        .header {
            height: 72px;
            background: var(--mem-surface)!important;
            border-bottom: 1px solid var(--mem-line)!important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }
        .header-title { font-weight: 800; font-size: 1.25rem; color: var(--mem-ink)!important; }
        .header small { color: var(--mem-muted)!important; font-size: .88rem; }

        .content-area {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px clamp(12px, 2vw, 28px);
        }

        /* ===== Cards ===== */
        .card-box {
            background: var(--mem-surface)!important;
            border: 1px solid var(--mem-line)!important;
            border-radius: var(--mem-radius)!important;
            box-shadow: 0 10px 30px rgba(35, 91, 170, .08);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .card-head {
            padding: 16px 20px 12px;
            border-bottom: 1px solid var(--mem-line)!important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .card-head h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 750;
            color: var(--mem-ink)!important;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-body-pad { padding: 18px 20px; flex: 1; }

        /* ===== Scanner Viewport ===== */
        .scanner-container {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            max-height: 320px;
            background: #0b1424;
            border-radius: var(--mem-radius)!important;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--mem-line)!important;
        }
        .scanner-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media(max-width: 575px) {
            .header {
                padding: 0 16px;
                height: 64px;
            }
            .header-title { font-size: 1.1rem; }
            .hero-banner { padding: 18px 20px; }
            .scanner-container {
                aspect-ratio: 16 / 11;
                max-height: 260px;
            }
            .card-body-pad { padding: 14px 16px; }
        }
        .scanner-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .scanner-guide {
            width: 60%;
            aspect-ratio: 1;
            border: 2px solid rgba(255, 255, 255, .7);
            border-radius: 16px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, .45);
            position: relative;
        }
        .scanner-guide::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
            animation: scanline 2.2s ease-in-out infinite alternate;
        }
        @keyframes scanline {
            0% { top: 4%; }
            100% { top: 96%; }
        }

        .scanner-placeholder {
            text-align: center;
            color: #94a3b8;
            padding: 24px;
        }
        .scanner-placeholder i { font-size: 3rem; display: block; margin-bottom: 8px; opacity: .7; }

        /* Banner */
        .hero-banner {
            background: linear-gradient(135deg, #1f63d4 0%, #15459b 100%);
            border-radius: 18px;
            padding: 22px 26px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: 0 10px 24px rgba(21, 69, 155, .2);
            position: relative;
            overflow: hidden;
        }
        .hero-banner::after {
            content: "\F505";
            font-family: "bootstrap-icons";
            position: absolute;
            right: 20px;
            bottom: -20px;
            font-size: 8rem;
            color: rgba(255, 255, 255, .08);
            pointer-events: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
        }
        .status-online { background: #dcfce7; color: #15803d; }
        .status-offline { background: #fee2e2; color: #b91c1c; }

        .recent-attendance-table {
            font-size: .88rem;
        }
        .recent-attendance-table th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--mem-muted)!important;
            border-bottom: 1px solid var(--mem-line)!important;
        }
        .recent-attendance-table td {
            vertical-align: middle;
            border-bottom: 1px solid var(--mem-line)!important;
        }

        /* Dark mode overrides for attendance page */
        html[data-bs-theme="dark"] .hero-banner {
            box-shadow: 0 10px 24px rgba(30,64,175,.35);
        }
        html[data-bs-theme="dark"] .scanner-placeholder {
            color: #9ca3af;
        }
        html[data-bs-theme="dark"] .status-online { background: #064e3b; color: #dcfce7; }
        html[data-bs-theme="dark"] .status-offline { background: #7f1d1d; color: #fee2e2; }
    </style>
</head>
<body class="attendance-page">

<div class="top-strip"></div>

<!-- Standard Navigation Sidebar -->
<script src="js/sidebar.js?v=18"></script>

<div class="app-wrapper page-content d-flex flex-column min-vh-100">

    <!-- Top Header -->
    <header class="header">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="header-title"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Event Attendance Scanner</div>
                <small>Record KK member attendance via digital QR ID or manual lookup</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div id="connectionStatus" class="status-badge status-online">
                <i class="bi bi-wifi"></i> <span>Online</span>
            </div>
            <a href="participation.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Participation
            </a>
        </div>
    </header>

    <?php include __DIR__ . '/init.php'; ?>

    <main class="content-area flex-grow-1">

        <!-- Hero banner -->
        <div class="hero-banner">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-white text-primary mb-2 fw-bold">Live SK Event Check-In</span>
                    <h1 class="h3 fw-bold mb-2 text-white">Fast QR Attendance with Offline Fallback</h1>
                    <p class="mb-0 text-white-50" style="max-width: 650px;">
                        Scan KK Member digital ID cards using your device camera for instant attendance recording. If the event venue has no internet or a member does not have their QR ID, use the manual lookup or keep scanning — attendance records queue locally and sync automatically when internet returns.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div id="offlineSyncAlert" class="d-inline-block bg-white text-dark p-3 rounded-4 shadow-sm text-start" style="display:none !important;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                            <strong class="small text-warning-emphasis">Offline Queue Active</strong>
                        </div>
                        <div class="small text-muted mb-2"><span id="offlineCount">0</span> records pending sync</div>
                        <button id="btnManualSync" class="btn btn-sm btn-primary w-100 py-1">
                            <i class="bi bi-arrow-repeat me-1"></i> Sync Now
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Selection Filter Card -->
        <div class="card-box mb-4">
            <div class="card-body-pad py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <label for="eventSelect" class="form-label fw-bold small text-muted mb-1">Select Active Event / Activity *</label>
                        <select id="eventSelect" class="form-select form-select-lg fs-6 fw-semibold">
                            <option value="">-- Choose an event --</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex align-items-center justify-content-md-end gap-3 pt-md-3">
                            <div class="text-md-end">
                                <div class="text-muted small">Total Recorded Today</div>
                                <div class="h4 fw-bold text-primary mb-0" id="sessionCount">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dual Attendance Cards: QR Scanner + Manual Entry -->
        <div class="row g-4 mb-4">

            <!-- Left: QR Code Scanner -->
            <div class="col-lg-6">
                <div class="card-box">
                    <div class="card-head">
                        <h2><i class="bi bi-camera-fill text-primary"></i> Camera QR Scanner</h2>
                        <span class="badge bg-primary-subtle text-primary fw-bold" id="scannerStateBadge">Idle</span>
                    </div>
                    <div class="card-body-pad text-center">
                        <div class="scanner-container mb-3" id="scannerViewport">
                            <div class="scanner-placeholder" id="scannerPlaceholder">
                                <i class="bi bi-qr-code"></i>
                                <div class="fw-semibold">Camera ready</div>
                                <div class="small text-secondary px-3">Make sure lighting is even. Avoid heavy glare on plastic ID covers.</div>
                            </div>
                            <video id="video" playsinline style="display:none;"></video>
                            <div class="scanner-overlay" id="scannerOverlay" style="display:none;">
                                <div class="scanner-guide"></div>
                                <div class="position-absolute bottom-0 mb-3 text-white small bg-dark bg-opacity-50 px-2 py-1 rounded">
                                    <i class="bi bi-info-circle me-1"></i> Tip: Tilt ID to avoid glare
                                </div>
                            </div>
                        </div>

                        <div id="scanStatus" class="alert alert-secondary py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Select an event, then start camera to scan
                        </div>

                        <!-- Visual Sync Info -->
                        <div class="mb-3">
                            <div id="syncIndicator" class="small text-muted">
                                <i class="bi bi-cloud-check-fill text-success"></i> Real-time sync active
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <button id="toggleScanner" class="btn btn-primary px-4">
                                <i class="bi bi-camera-video-fill me-1"></i> Start Scanner
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Manual Member Lookup (No Internet / No QR fallback) -->
            <div class="col-lg-6">
                <div class="card-box">
                    <div class="card-head">
                        <h2><i class="bi bi-person-check-fill text-success"></i> Manual Member Attendance</h2>
                        <span class="badge bg-secondary-subtle text-secondary" id="offlineBadge" style="display:none;">Offline Mode</span>
                    </div>
                    <div class="card-body-pad">
                        <p class="small text-muted mb-3">
                            If the member forgot their QR ID, or their camera cannot read the code, select their name below to mark them present manually.
                        </p>

                        <div class="mb-3">
                            <label for="memberSearch" class="form-label fw-bold small text-muted">Filter Member List</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="memberSearch" class="form-control" placeholder="Type member name or KK number...">
                            </div>
                            <select id="memberSelect" class="form-select" size="6" style="min-height: 160px;"></select>
                            <div class="form-text small" id="memberSelectInfo">0 active members available</div>
                        </div>

                        <div class="d-grid mt-4">
                            <button id="recordManual" class="btn btn-success py-2 fw-semibold" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Record Member Attendance
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Recent Attendance Log in This Session -->
        <div class="card-box">
            <div class="card-head">
                <h2><i class="bi bi-clock-history text-secondary"></i> Recently Recorded Attendance</h2>
                <span class="small text-muted" id="tableRecordCount">Showing attendance for current event</span>
            </div>
            <div class="card-body-pad p-0">
                <div class="table-responsive">
                    <table class="table recent-attendance-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Member Name</th>
                                <th>Method</th>
                                <th>Time</th>
                                <th>Recorded By</th>
                                <th class="pe-4 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentAttendanceTbody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox d-block fs-3 mb-1"></i>
                                    Select an event to view or record attendance.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Modal Alert Feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-3 rounded-4 shadow">
            <div class="modal-body">
                <div id="modalIcon" class="mb-2"></div>
                <h5 class="fw-bold mb-1" id="modalTitle"></h5>
                <p class="small text-muted mb-3" id="modalMessage"></p>
                <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/kk-id.js"></script>

<script>
(function() {
    'use strict';

    // Backend data passed via init.php
    const membersData = <?php echo json_encode(json_decode($__data['kkMembers'] ?? '[]', true)); ?> || [];
    const eventsData = <?php echo json_encode(json_decode($__data['skAnnouncements'] ?? '[]', true)); ?> || [];
    let attendanceData = <?php echo json_encode(json_decode($__data['skEventAttendance'] ?? '[]', true)); ?> || [];

    // Filter active members and published events
    const activeMembers = membersData.filter(m => !m.status || m.status === 'Active');
    const validEvents = eventsData.filter(e => !e.cancelled);

    // Elements
    const eventSel = document.getElementById('eventSelect');
    const memberSel = document.getElementById('memberSelect');
    const memberSearch = document.getElementById('memberSearch');
    const recordManualBtn = document.getElementById('recordManual');
    const sessionCountEl = document.getElementById('sessionCount');
    const tableRecordCount = document.getElementById('tableRecordCount');
    const recentAttendanceTbody = document.getElementById('recentAttendanceTbody');
    const connStatus = document.getElementById('connectionStatus');
    const offlineSyncAlert = document.getElementById('offlineSyncAlert');
    const offlineCount = document.getElementById('offlineCount');
    const btnManualSync = document.getElementById('btnManualSync');
    const syncIndicator = document.getElementById('syncIndicator');
    const offlineBadge = document.getElementById('offlineBadge');

    // Populate Events
    validEvents.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = (e.title || 'Untitled Event') + (e.date ? ' (' + e.date + ')' : '');
        eventSel.appendChild(opt);
    });

    // Populate Members
    function renderMembersList(filterText = '') {
        memberSel.innerHTML = '';
        const ft = filterText.toLowerCase().trim();
        const filtered = activeMembers.filter(m => {
            if (!ft) return true;
            const name = (m.name || (m.firstName + ' ' + m.lastName) || '').toLowerCase();
            const no = (m.memberNo || '').toLowerCase();
            return name.includes(ft) || no.includes(ft);
        });

        filtered.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            const name = m.name || [m.firstName, m.middleName, m.lastName, m.suffix].filter(Boolean).join(' ') || m.id;
            opt.textContent = name + (m.memberNo ? ' • KK No. ' + m.memberNo : '');
            memberSel.appendChild(opt);
        });

        document.getElementById('memberSelectInfo').textContent = `${filtered.length} member(s) found`;
    }
    renderMembersList();

    memberSearch.addEventListener('input', e => renderMembersList(e.target.value));
    memberSel.addEventListener('change', () => {
        recordManualBtn.disabled = !memberSel.value || !eventSel.value;
    });
    eventSel.addEventListener('change', () => {
        recordManualBtn.disabled = !memberSel.value || !eventSel.value;
        refreshAttendanceTable();
    });

    /* =========================================================
       OFFLINE QUEUE & SYNC
       ========================================================= */
    const QUEUE_KEY = 'eventAttendanceQueue';
    function loadQueue() {
        try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); }
        catch(e) { return []; }
    }
    function saveQueue(q) {
        localStorage.setItem(QUEUE_KEY, JSON.stringify(q));
        updateOfflineUI();
    }
    function enqueue(entry) {
        const q = loadQueue();
        q.push(entry);
        saveQueue(q);
    }
    function updateOfflineUI() {
        const q = loadQueue();
        if (q.length > 0) {
            offlineSyncAlert.style.setProperty('display', 'inline-block', 'important');
            offlineCount.textContent = q.length;
            syncIndicator.innerHTML = '<i class="bi bi-cloud-arrow-up-fill text-warning"></i> Queued offline';
            offlineBadge.style.display = 'inline-block';
        } else {
            offlineSyncAlert.style.setProperty('display', 'none', 'important');
            syncIndicator.innerHTML = '<i class="bi bi-cloud-check-fill text-success"></i> Real-time sync active';
            offlineBadge.style.display = 'none';
        }
    }

    async function syncOfflineQueue() {
        if (!navigator.onLine) {
            showNotice('Offline', 'Cannot sync while offline. Please check your network connection.', 'warning');
            return;
        }
        const q = loadQueue();
        if (!q.length) return;

        btnManualSync.disabled = true;
        btnManualSync.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing...';

        const remaining = [];
        let successCount = 0;

        for (const entry of q) {
            try {
                const res = await callAttendanceApi(entry);
                if (res && res.ok) {
                    successCount++;
                    if (res.record) {
                        attendanceData.unshift(res.record);
                    }
                } else {
                    remaining.push(entry);
                }
            } catch (err) {
                remaining.push(entry);
            }
        }

        saveQueue(remaining);
        btnManualSync.disabled = false;
        btnManualSync.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Sync Now';

        if (successCount > 0) {
            refreshAttendanceTable();
            showNotice('Sync Complete', `Successfully uploaded ${successCount} attendance record(s).`, 'success');
        }
    }

    btnManualSync.addEventListener('click', syncOfflineQueue);
    window.addEventListener('online', () => {
        connStatus.className = 'status-badge status-online';
        connStatus.innerHTML = '<i class="bi bi-wifi"></i> <span>Online</span>';
        syncOfflineQueue();
    });
    window.addEventListener('offline', () => {
        connStatus.className = 'status-badge status-offline';
        connStatus.innerHTML = '<i class="bi bi-wifi-off"></i> <span>Offline</span>';
    });

    /* =========================================================
       API ATTENDANCE CALL
       ========================================================= */
    function callAttendanceApi(entry) {
        const payload = {
            eventId: entry.eventId,
            source: entry.source
        };
        if (entry.value) payload.value = entry.value;
        if (entry.memberId) payload.memberId = entry.memberId;

        return fetch('api.php?action=event_attendance_qr', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.SK_CSRF_TOKEN || ''
            },
            body: JSON.stringify(payload)
        }).then(r => r.json());
    }

    function recordAttendance(entry) {
        const isOnline = navigator.onLine;

        if (!isOnline) {
            enqueue(entry);
            // Optimistically show in UI
            const memberObj = activeMembers.find(m => m.id === entry.memberId);
            const optimisticRecord = {
                eventId: entry.eventId,
                memberId: entry.memberId,
                memberName: memberObj ? (memberObj.name || (memberObj.firstName + ' ' + memberObj.lastName)) : (entry.value ? 'Scanned KK Member' : 'Member'),
                source: entry.source,
                attendanceMarkedAt: new Date().toISOString(),
                attendanceMarkedBy: 'You (Queued Offline)',
                attendanceStatus: 'Attended'
            };
            attendanceData.unshift(optimisticRecord);
            refreshAttendanceTable();
            showNotice('Saved Offline', 'No internet connection. Record saved to local queue and will sync automatically when reconnected.', 'warning');
            return;
        }

        callAttendanceApi(entry).then(res => {
            if (res && res.ok) {
                if (res.record) attendanceData.unshift(res.record);
                refreshAttendanceTable();
                showNotice('Success', 'Attendance recorded successfully!', 'success');
            } else {
                showNotice('Error', res.error || 'Failed to record attendance.', 'danger');
            }
        }).catch(err => {
            enqueue(entry);
            showNotice('Offline Fallback', 'Network error encountered. Attendance saved locally and will sync later.', 'warning');
        });
    }

    /* =========================================================
       MANUAL ATTENDANCE HANDLER
       ========================================================= */
    recordManualBtn.addEventListener('click', () => {
        const eventId = eventSel.value;
        const memberId = memberSel.value;
        if (!eventId || !memberId) return;

        recordAttendance({
            eventId: eventId,
            memberId: memberId,
            source: 'manual'
        });
    });

    /* =========================================================
       CAMERA QR SCANNER
       ========================================================= */
    const video = document.getElementById('video');
    const toggleScannerBtn = document.getElementById('toggleScanner');
    const scanStatus = document.getElementById('scanStatus');
    const scannerViewport = document.getElementById('scannerViewport');
    const scannerPlaceholder = document.getElementById('scannerPlaceholder');
    const scannerOverlay = document.getElementById('scannerOverlay');
    const scannerBadge = document.getElementById('scannerStateBadge');
    let isScanning = false;

    function setScannerRunning(running) {
        isScanning = running;
        if (running) {
            scannerPlaceholder.style.display = 'none';
            video.style.display = 'block';
            scannerOverlay.style.display = 'flex';
            toggleScannerBtn.className = 'btn btn-danger px-4';
            toggleScannerBtn.innerHTML = '<i class="bi bi-stop-circle-fill me-1"></i> Stop Scanner';
            scannerBadge.className = 'badge bg-success-subtle text-success fw-bold';
            scannerBadge.textContent = 'Active Scanning';
            scanStatus.className = 'alert alert-info py-2 small mb-3';
            scanStatus.innerHTML = '<i class="bi bi-camera me-1"></i> Point the camera at the member QR ID';
        } else {
            scannerPlaceholder.style.display = 'block';
            video.style.display = 'none';
            scannerOverlay.style.display = 'none';
            toggleScannerBtn.className = 'btn btn-primary px-4';
            toggleScannerBtn.innerHTML = '<i class="bi bi-camera-video-fill me-1"></i> Start Scanner';
            scannerBadge.className = 'badge bg-secondary-subtle text-secondary fw-bold';
            scannerBadge.textContent = 'Idle';
            scanStatus.className = 'alert alert-secondary py-2 small mb-3';
            scanStatus.innerHTML = '<i class="bi bi-info-circle me-1"></i> Camera scanner stopped.';
        }
    }

    function onTokenScanned(token) {
        const eventId = eventSel.value;
        if (!eventId) {
            showNotice('Select Event', 'Please choose an active event first before scanning members.', 'warning');
            return;
        }

        recordAttendance({
            eventId: eventId,
            value: 'KKID:' + token,
            source: 'qr_scan'
        });

        // Briefly stop scanner so same code isn't read repeatedly
        KKId.stopScanner(video);
        setScannerRunning(false);
    }

    toggleScannerBtn.addEventListener('click', () => {
        if (isScanning) {
            KKId.stopScanner(video);
            setScannerRunning(false);
            return;
        }

        if (!eventSel.value) {
            showNotice('Select Event', 'Please select an event before starting the scanner.', 'warning');
            eventSel.focus();
            return;
        }

        KKId.startScanner(video, onTokenScanned, scanStatus)
            .then(() => setScannerRunning(true))
            .catch(err => {
                setScannerRunning(false);
                showNotice('Scanner Error', err.message || 'Could not start camera scanner. Use manual attendance below.', 'danger');
            });
    });

    /* =========================================================
       ATTENDANCE TABLE REFRESH
       ========================================================= */
    function refreshAttendanceTable() {
        const currentEventId = eventSel.value;
        if (!currentEventId) {
            sessionCountEl.textContent = '0';
            tableRecordCount.textContent = 'Select an event to view records';
            recentAttendanceTbody.innerHTML = `
                <tr><td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox d-block fs-3 mb-1"></i>
                    Select an event to view or record attendance.
                </td></tr>`;
            return;
        }

        const filtered = attendanceData.filter(r => (r.eventId || '') === currentEventId);
        sessionCountEl.textContent = filtered.length;
        tableRecordCount.textContent = `${filtered.length} attendee(s) recorded`;

        if (!filtered.length) {
            recentAttendanceTbody.innerHTML = `
                <tr><td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-clipboard2-check d-block fs-3 mb-1 text-primary"></i>
                    No attendance recorded yet for this event. Start scanning or mark attendance manually!
                </td></tr>`;
            return;
        }

        recentAttendanceTbody.innerHTML = filtered.map(r => {
            const timeStr = r.attendanceMarkedAt ? new Date(r.attendanceMarkedAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '—';
            const sourceBadge = r.source === 'qr_scan'
                ? '<span class="badge bg-primary-subtle text-primary"><i class="bi bi-qr-code me-1"></i>QR Scan</span>'
                : '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-person-fill me-1"></i>Manual</span>';

            return `
                <tr>
                    <td class="ps-4 fw-semibold text-dark">${r.memberName || 'KK Member'}</td>
                    <td>${sourceBadge}</td>
                    <td>${timeStr}</td>
                    <td class="small text-muted">${r.attendanceMarkedBy || 'Official'}</td>
                    <td class="pe-4 text-end">
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check2 me-1"></i>Attended</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /* Modal Notice Helper */
    function showNotice(title, message, type) {
        const iconEl = document.getElementById('modalIcon');
        const titleEl = document.getElementById('modalTitle');
        const msgEl = document.getElementById('modalMessage');

        let icon = '<i class="bi bi-info-circle-fill text-primary fs-1"></i>';
        if (type === 'success') icon = '<i class="bi bi-check-circle-fill text-success fs-1"></i>';
        if (type === 'warning') icon = '<i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>';
        if (type === 'danger') icon = '<i class="bi bi-x-circle-fill text-danger fs-1"></i>';

        iconEl.innerHTML = icon;
        titleEl.textContent = title;
        msgEl.textContent = message;

        const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
        modal.show();
    }

    // Initialize UI state
    updateOfflineUI();
    if (!navigator.onLine) {
        connStatus.className = 'status-badge status-offline';
        connStatus.innerHTML = '<i class="bi bi-wifi-off"></i> <span>Offline</span>';
    }
})();
</script>
</body>
</html>
