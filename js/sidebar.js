/* =========================================================
   SHARED SIDEBAR  (copied from dashboard.php)
   Usage: put this where the sidebar should be, right after
   <div class="top-strip"></div>:
        <script src="js/sidebar.js"></script>
   Add/rename menu items in the MENU list below -> every page updates.
   The active link is detected from the current file name.
   ========================================================= */
(function () {

    // saved display preference (Settings page): text size
    try {
        var sz = localStorage.getItem('kkTextSize');
        if (sz === 'small') document.documentElement.style.fontSize = '15.5px';
        else if (sz === 'large') document.documentElement.style.fontSize = '19.5px';
    } catch (e) {}

    var MENU = [
        { title: null, items: [
            ['dashboard.php',     'bi-grid-1x2-fill',             'Dashboard']
        ]},
        { title: 'SK Management', items: [
            ['sk-management.php', 'bi-calendar-check',             'SK Meetings'],
            ['officials.php',     'bi-person-badge',              'SK Officials']
        ]},
        { title: 'KK Youth', items: [
            ['request.php',       'bi-inbox-fill',                'Review Requests', 'review_requests'],
            ['members.php',       'bi-people',                    'Member List'],
            ['archives.php',      'bi-archive',                   'Archives'],
            ['announcements.php', 'bi-megaphone',                 'Announcements', 'announcements'],
            ['participation.php', 'bi-graph-up-arrow',            'Participation Tracking'],
            ['event-attendance.php', 'bi-qr-code',               'Event Attendance'],
            ['reports.php',       'bi-file-earmark-bar-graph',    'Reports']
        ]},
        { title: 'System', items: [
            ['settings.php',      'bi-gear-fill',                 'Settings']
        ]}
    ];

    var publicMode = document.documentElement.classList.contains('is-public') || !!window.SK_PUBLIC;
    var memberHome = publicMode && !!window.SK_MEMBER_PORTAL;
    var current = (location.pathname.split('/').pop() || 'dashboard.php').toLowerCase();
    var ALIAS = { 'profiling.php': 'registration.php' };
    current = ALIAS[current] || current;

    if (publicMode) {
        var landing = [];
        landing.push(memberHome
            ? ['member-portal.php', 'bi-grid-1x2-fill', 'Dashboard']
            : ['index.php#home', 'bi-house-door', 'Home']);
        if (memberHome) landing.push(['settings.php', 'bi-gear-fill', 'Settings']);
        landing.push(['index.php#officials',     'bi-people',       'SK officials']);
        landing.push(['index.php#expenses',      'bi-receipt',      'Recorded expenses']);
        landing.push(['index.php#budget',        'bi-shield-check', 'Project transparency']);
        landing.push(['index.php#announcements', 'bi-megaphone',    'Announcements & activities']);
        landing.push(['archives.php',            'bi-archive',      'Completed event archives']);
        landing.push(['index.php#join',          'bi-person-plus',  'How to join']);
        MENU = [{ title: null, items: landing }];
    }

    if (window.SK_MEMBER_PORTAL && !publicMode) {
        var portalLink = ['member-portal.php', 'bi-person-vcard', 'My KK Portal'];
        var youthMenu = MENU.filter(function (group) { return group.title === 'KK Youth'; })[0];
        if (youthMenu) youthMenu.items.unshift(portalLink);
    }

    if (!publicMode && window.SK_CAN_VIEW_AUDIT_LOG) {
        var systemMenu = MENU.filter(function (group) { return group.title === 'System'; })[0];
        if (systemMenu) systemMenu.items.unshift(['audit-log.php', 'bi-journal-text', 'Audit Log']);
    }

    function isActive(href) {
        var at = href.indexOf('#');
        var page = at > -1 ? href.slice(0, at) : href;
        var hash = at > -1 ? href.slice(at) : '';
        if (page !== current) return false;
        if (!hash) return true;
        if (hash === '#home') return !location.hash || location.hash === '#home';
        return location.hash === hash;
    }

    var nav = MENU.map(function (group, i) {
        var title = group.title
            ? '<div class="sidebar-section-title mt-4">' + group.title + '</div>'
            : '';
        var links = group.items.map(function (it) {
            var href = it[0];
            var active = isActive(href);
            var alerts = window.SK_SIDEBAR_NOTICES || {};
            var alertKeys = it[3] === 'announcements'
                ? ['announcements', 'interested']
                : (it[3] ? [it[3]] : []);
            var hasNew = alertKeys.some(function (key) { return alerts[key] === true; });
            var badge = hasNew
                ? '<span class="sidebar-new-dot" aria-hidden="true"></span><span class="sidebar-visually-hidden">New</span>'
                : '';
            return '<li class="nav-item">' +
                   '<a class="nav-link' + (active ? ' active' : '') + '" href="' + href + '"' +
                   (active ? ' aria-current="page"' : '') + '>' +
                   '<i class="bi ' + it[1] + ' me-2"></i>' + it[2] + badge +
                   '</a></li>';
        }).join('');
        return title + '<ul class="nav nav-pills nav-sidebar flex-column gap-1" data-lte-toggle="treeview" role="menu">' + links + '</ul>';
    }).join('');

    var html =
    '<aside class="sidebar app-sidebar bg-body-secondary shadow offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarTitle">' +
        '<div class="offcanvas-header border-bottom d-lg-none">' +
            '<h5 class="offcanvas-title text-dark fw-bold" id="sidebarTitle">SK Barangay Sikat</h5>' +
            '<button type="button" class="btn-close " data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>' +
        '</div>' +
        '<div class="offcanvas-body sidebar-wrapper d-flex flex-column p-3">' +
            '<a href="' + (memberHome ? 'member-portal.php' : 'dashboard.php') + '" class="sidebar-brand brand-link d-flex align-items-center gap-2 text-decoration-none mb-4 px-1 pt-1">' +
                '<span class="brand-badge">SK</span>' +
                '<span class="lh-1">' +
                    '<span class="d-block fw-bold text-dark" style="font-size:15px;">SK BARANGAY SIKAT</span>' +
                    '<span class="d-block text-secondary mt-1" style="font-size:11px;">KK YOUTH PROFILING</span>' +
                '</span>' +
            '</a>' +
            nav +
            '<div class="mt-auto pt-3 small text-secondary border-top">' +
                '<button type="button" class="theme-toggle mb-3" data-theme-toggle aria-pressed="false" onclick="kkToggleTheme()"><i class="bi bi-moon-stars me-2"></i>Dark mode</button>' +
                '<div class="fw-semibold text-dark">Barangay Sikat</div>' +
                '<div>Alfonso, Cavite</div>' +
                '<span class="badge bg-primary mt-2">Active FY 2026–2027</span>' +
            '</div>' +
        '</div>' +
    '</aside>';

    // Insert right here (synchronously) so there is no flash or layout shift.
    document.currentScript.insertAdjacentHTML('beforebegin', html);

    window.addEventListener('hashchange', function () {
        document.querySelectorAll('#sidebar .nav-sidebar .nav-link').forEach(function (link) {
            var on = isActive(link.getAttribute('href'));
            link.classList.toggle('active', on);
            if (on) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    });

    // Keep the sidebar's own scroll position when switching pages.
    var body = document.querySelector('#sidebar .offcanvas-body');
    try {
        var saved = sessionStorage.getItem('sidebarScroll');
        if (saved !== null) body.scrollTop = parseInt(saved, 10) || 0;
        body.addEventListener('scroll', function () {
            sessionStorage.setItem('sidebarScroll', body.scrollTop);
        });
    } catch (e) {}

})();