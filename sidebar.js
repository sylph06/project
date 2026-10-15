/* =========================================================
   SHARED SIDEBAR  (copied from dashboard.php)
   Usage: put this where the sidebar should be, right after
   <div class="top-strip"></div>:
        <script src="sidebar.js"></script>
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
            ['registration.php',   'bi-person-plus-fill',         'KK Youth Registration'],  
            ['members.php',       'bi-people',                    'Member List'],
            ['announcements.php', 'bi-megaphone',                 'Announcements'],
            ['participation.php', 'bi-graph-up-arrow',            'Participation Tracking'],
            ['reports.php',       'bi-file-earmark-bar-graph',    'Reports']
        ]},
        { title: 'System', items: [
            ['settings.php',      'bi-gear-fill',                 'Settings']
        ]}
    ];

    var publicMode = document.documentElement.classList.contains('is-public') || !!window.SK_PUBLIC;
    if (publicMode) {
        MENU = [
            { title: null, items: [
                ['officials.php',     'bi-person-badge', 'SK Officials'],
                ['announcements.php', 'bi-megaphone',    'Announcements']
            ]}
        ];
    }

    var current = (location.pathname.split('/').pop() || 'dashboard.php').toLowerCase();

    // Pages that belong to a menu item but have a different file name.
    // (profiling.php is the same KK Member Profiling page as registration.php)
    var ALIAS = { 'profiling.php': 'registration.php' };
    current = ALIAS[current] || current;

    var nav = MENU.map(function (group, i) {
        var title = group.title
            ? '<div class="sidebar-section-title mt-4">' + group.title + '</div>'
            : '';
        var links = group.items.map(function (it) {
            var active = it[0] === current;
            return '<li class="nav-item">' +
                   '<a class="nav-link' + (active ? ' active' : '') + '" href="' + it[0] + '"' +
                   (active ? ' aria-current="page"' : '') + '>' +
                   '<i class="bi ' + it[1] + ' me-2"></i>' + it[2] +
                   '</a></li>';
        }).join('');
        return title + '<ul class="nav flex-column gap-1">' + links + '</ul>';
    }).join('');

    var html =
    '<aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarTitle">' +
        '<div class="offcanvas-header border-bottom">' +
            '<h5 class="offcanvas-title text-dark fw-bold" id="sidebarTitle">SK KK Profiling</h5>' +
            '<button type="button" class="btn-close " data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>' +
        '</div>' +
        '<div class="offcanvas-body d-flex flex-column p-3">' +
            '<a href="dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none mb-4 px-1 pt-1">' +
                '<span class="brand-badge"><img src="images/sk-logo.jpg" alt="SK Barangay Sikat logo"></span>' +
                '<span class="lh-1">' +
                    '<span class="d-block fw-bold text-dark" style="font-size:15px;">SANGGUNIANG</span>' +
                    '<span class="d-block fw-bold text-dark mt-1" style="font-size:15px;">KABATAAN</span>' +
                    '<span class="d-block text-secondary mt-1" style="font-size:11px;letter-spacing:1px;">KK YOUTH PROFILING</span>' +
                '</span>' +
            '</a>' +
            nav +
            '<div class="mt-auto pt-3 small text-secondary border-top">' +
                '<div class="fw-semibold text-dark">Barangay Sikat</div>' +
                '<div>Alfonso, Cavite</div>' +
                '<span class="badge bg-primary mt-2">Active FY 2026–2027</span>' +
            '</div>' +
        '</div>' +
    '</aside>';

    // Insert right here (synchronously) so there is no flash or layout shift.
    document.currentScript.insertAdjacentHTML('beforebegin', html);

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