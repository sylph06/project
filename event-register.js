/* =========================================================
   event-register.js  - event sign-up (login required)
   Loaded by announcements.php and dashboard.php after the shared announcement script.
   - Adds a "Sign up for this event" button to the event details window.
   - Authenticated users can sign up; public viewers are directed to login first.
   - Officers also get a "Sign-ups" button to see (and remove) the list.
   - Sign-ups are saved in the data key  skEventRegistrations  and are NOT sent to public viewers.
   ========================================================= */
(function () {
    'use strict';

    var KEY = 'skEventRegistrations';

    function init() {
        var Core = window.AnnCore;
        var footer = document.getElementById('detailFooter');
        if (!Core || !footer || !window.bootstrap) return;
        var OFFICIAL = Core.mode === 'official';
        var AUTHENTICATED = OFFICIAL || window.ANN_AUTHENTICATED === true;
        var esc = Core.esc;
        var addedHere = [];          // sign-ups made on this page (so officers see them without reloading)
        var attendanceUpdates = {};
        var walkInsHere = [];
        var currentEvent = null;

        /* ---------- the window used for the form / the list ---------- */
        document.body.insertAdjacentHTML('beforeend',
            '<div class="modal fade" id="evRegModal" tabindex="-1" aria-labelledby="evRegTitle" aria-hidden="true">' +
              '<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">' +
                '<div class="modal-header"><h5 class="modal-title fw-bold" id="evRegTitle"></h5>' +
                  '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
                '<div class="modal-body" id="evRegBody"></div>' +
                '<div class="modal-footer" id="evRegFooter"></div>' +
              '</div></div></div>');
        var modalEl = document.getElementById('evRegModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var reopenId = null;

        /* when this window closes, go back to the event details */
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (reopenId) { var id = reopenId; reopenId = null; Core.openDetails(id); }
        });

        function show(title, bodyHtml, footerHtml) {
            document.getElementById('evRegTitle').textContent = title;
            document.getElementById('evRegBody').innerHTML = bodyHtml;
            document.getElementById('evRegFooter').innerHTML = footerHtml || '';
            if (!modalEl.classList.contains('show')) modal.show();
        }

        /* open our window after the details window finished closing */
        function switchTo(id, fn) {
            var det = document.getElementById('detailsModal');
            reopenId = id;
            if (det.classList.contains('show')) {
                det.addEventListener('hidden.bs.modal', function once() {
                    det.removeEventListener('hidden.bs.modal', once);
                    fn();
                });
                Core.closeDetails();
            } else fn();
        }

        function canSignUp(a) {
            var st = Core.getStatus(a);
            return st === 'Upcoming' || st === 'Ongoing';
        }

        /* ---------- buttons in the details window ---------- */
        function addButtons() {
            var id = Core.viewingId;
            if (!id || document.getElementById('evRegBtn') || document.getElementById('evListBtn')) return;
            var a = Core.find(id);
            if (!a) return;
            var html = '';
            if (OFFICIAL) html += '<button type="button" class="btn btn-outline-success" id="evListBtn"><i class="bi bi-people me-1"></i> Sign-ups (' + listFor(id).length + ')</button>';
            if (canSignUp(a)) html += '<button type="button" class="btn btn-primary" id="evRegBtn"><i class="bi bi-' + (AUTHENTICATED ? 'pencil-square' : 'box-arrow-in-right') + ' me-1"></i> ' + (AUTHENTICATED ? 'Sign up for this event' : 'Log in to sign up') + '</button>';
            if (html) footer.insertAdjacentHTML('beforeend', html);
            var b = document.getElementById('evRegBtn');
            if (b) b.addEventListener('click', function () {
                if (!AUTHENTICATED) {
                    window.location.href = 'login.php?next=announcements.php&eventId=' + encodeURIComponent(id);
                    return;
                }
                switchTo(id, function () { showForm(a); });
            });
            var l = document.getElementById('evListBtn');
            if (l) l.addEventListener('click', function () { switchTo(id, function () { showList(a); }); });
        }
        new MutationObserver(addButtons).observe(footer, { childList: true });
        addButtons();

        /* ---------- the sign-up form ---------- */
        function field(id, label, type, extra, req) {
            return '<div class="mb-3"><label class="form-label fw-semibold" for="' + id + '">' + label + (req ? ' <span class="text-danger">*</span>' : '') + '</label>' +
                '<input class="form-control" id="' + id + '" type="' + type + '" ' + (extra || '') + '></div>';
        }
        function showForm(a) {
            currentEvent = a;
            var body =
                '<p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Tell us who is coming to <strong>' + esc(a.title) + '</strong>.</p>' +
                '<div id="evErr" class="alert alert-danger d-none py-2" role="alert"></div>' +
                '<form id="evForm" novalidate>' +
                field('evName', 'Full name', 'text', 'maxlength="100" autocomplete="name"', true) +
                '<div class="row g-3">' +
                  '<div class="col-6">' + field('evAge', 'Age', 'number', 'min="1" max="120" inputmode="numeric"') + '</div>' +
                  '<div class="col-6">' + field('evPurok', 'Purok', 'text', 'maxlength="60"') + '</div>' +
                '</div>' +
                field('evMobile', 'Mobile number', 'tel', 'maxlength="30" inputmode="tel" autocomplete="tel" placeholder="09XX XXX XXXX"', true) +
                field('evEmail', 'Email (optional)', 'email', 'maxlength="120" autocomplete="email"') +
                '<div class="mb-1"><label class="form-label fw-semibold" for="evNotes">Notes (optional)</label>' +
                  '<textarea class="form-control" id="evNotes" rows="2" maxlength="300"></textarea></div>' +
                '</form>';
            var foot = '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>' +
                       '<button type="button" class="btn btn-primary" id="evSubmit"><i class="bi bi-check2-circle me-1"></i> Submit sign-up</button>';
            show('Sign up: ' + a.title, body, foot);
            document.getElementById('evSubmit').addEventListener('click', submit);
            document.getElementById('evForm').addEventListener('submit', function (e) { e.preventDefault(); submit(); });
        }

        function err(msg) {
            var el = document.getElementById('evErr');
            if (!el) return;
            el.textContent = msg; el.classList.remove('d-none');
        }

        function submit() {
            var v = function (id) { return document.getElementById(id).value.trim(); };
            var data = { name: v('evName'), age: parseInt(v('evAge'), 10) || 0, purok: v('evPurok'),
                         mobile: v('evMobile'), email: v('evEmail'), notes: v('evNotes') };
            if (!data.name) return err('Please enter your full name.');
            if (!data.mobile) return err('Please enter your mobile number.');
            var btn = document.getElementById('evSubmit');
            btn.disabled = true;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api.php?action=event_register', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function () {
                var res = {};
                try { res = JSON.parse(xhr.responseText); } catch (e) {}
                if (xhr.status === 200 && res.ok) {
                    if (res.record) addedHere.push(res.record);
                    document.getElementById('evRegTitle').textContent = 'You are signed up!';
                    document.getElementById('evRegBody').innerHTML =
                        '<div class="text-center py-3"><i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>' +
                        '<h5 class="fw-bold mt-2">Thank you, ' + esc(data.name) + '!</h5>' +
                        '<p class="mb-1">You are signed up for <strong>' + esc(currentEvent.title) + '</strong>.</p>' +
                        '<p class="text-muted small mb-0">Reference: <strong>' + esc(res.ref) + '</strong>. Please be at ' + esc(currentEvent.venue || 'the venue') + ' on time.</p></div>';
                    document.getElementById('evRegFooter').innerHTML = '<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>';
                } else {
                    if (xhr.status === 401) {
                        window.location.href = 'login.php?next=announcements.php&eventId=' + encodeURIComponent(currentEvent.id);
                        return;
                    }
                    btn.disabled = false;
                    err(res.error || 'Could not send your sign-up. Please try again.');
                }
            };
            xhr.onerror = function () { btn.disabled = false; err('No connection. Please try again.'); };
            xhr.send(JSON.stringify({ eventId: currentEvent.id, data: data }));
        }

        /* ---------- officers: list of sign-ups ---------- */
        function allRegs() {
            var saved = [];
            try { saved = JSON.parse(DB.getItem(KEY) || '[]'); } catch (e) {}
            if (!Array.isArray(saved)) saved = [];
            var ids = {};
            saved.forEach(function (r) { ids[r.id] = 1; });
            addedHere.forEach(function (r) { if (!ids[r.id]) saved.push(r); });
            saved.forEach(function (r) {
                if (r && attendanceUpdates[r.id]) {
                    r.attendanceStatus = attendanceUpdates[r.id].status;
                    r.attendanceMarkedAt = attendanceUpdates[r.id].markedAt;
                    r.attendanceMarkedBy = attendanceUpdates[r.id].markedBy;
                }
            });
            return saved;
        }
        function listFor(id) { return allRegs().filter(function (r) { return r.eventId === id; }); }

        function memberRecords() {
            try {
                var members = JSON.parse(DB.getItem('kkMembers') || '[]');
                return Array.isArray(members) ? members.filter(function (member) {
                    return member && member.id && member.status === 'Active';
                }) : [];
            } catch (e) { return []; }
        }

        function memberName(member) {
            return [member.firstName, member.middleName, member.lastName, member.suffix]
                .filter(Boolean).join(' ');
        }

        function walkinsFor(id) {
            var saved = [];
            try {
                saved = JSON.parse(DB.getItem('skEventAttendance') || '[]');
                if (!Array.isArray(saved)) saved = [];
            } catch (e) {}
            var ids = {};
            saved.forEach(function (record) { if (record && record.id) ids[record.id] = 1; });
            walkInsHere.forEach(function (record) { if (record && record.id && !ids[record.id]) saved.push(record); });
            return saved.filter(function (record) { return record && record.eventId === id; });
        }

        function showList(a) {
            var rows = listFor(a.id);
            var walkins = walkinsFor(a.id);
            var members = memberRecords();
            var signedMemberIds = {};
            rows.forEach(function (record) { if (record.memberId) signedMemberIds[String(record.memberId)] = true; });
            walkins.forEach(function (record) { if (record.memberId) signedMemberIds[String(record.memberId)] = true; });
            var availableMembers = members.filter(function (member) { return !signedMemberIds[String(member.id)]; });
            var walkinForm = '<div class="border rounded p-3 mb-3"><label class="form-label fw-semibold" for="evWalkinMember">Add walk-in KK member</label>' +
                '<div class="d-flex flex-column flex-sm-row gap-2"><select class="form-select" id="evWalkinMember">' +
                '<option value="">Choose an active KK member</option>' +
                availableMembers.map(function (member) {
                    return '<option value="' + esc(member.id) + '">' + esc(memberName(member)) + ' · ' + esc(member.memberNo || member.id) + '</option>';
                }).join('') + '</select><button type="button" class="btn btn-outline-primary text-nowrap" id="evWalkinAdd"' +
                (availableMembers.length ? '' : ' disabled') + '>Record as attended</button></div>' +
                (!availableMembers.length ? '<div class="form-text">No additional active KK members are available for this event.</div>' : '') +
                '</div>';
            var body = rows.length || walkins.length
                ? walkinForm + '<p class="small text-muted">Sign-up is not attendance. Mark sign-ups after the event, or add a walk-in KK member as attended.</p><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Name / Member ID</th><th>Age</th><th>Purok</th><th>Mobile</th><th>Email</th><th>Attendance</th><th></th></tr></thead><tbody>' +
                  rows.map(function (r) {
                      var status = r.attendanceStatus || 'Not recorded';
                      return '<tr><td>' + esc(r.name) + (r.memberId ? '<div class="small text-muted">KK ID: ' + esc(r.memberId) + '</div>' : '<div class="small text-muted">Not linked to a KK account</div>') +
                             (r.notes ? '<div class="small text-muted">' + esc(r.notes) + '</div>' : '') + '</td><td>' + (r.age || '') + '</td><td>' + esc(r.purok) +
                             '</td><td>' + esc(r.mobile) + '</td><td>' + esc(r.email) + '</td><td><select class="form-select form-select-sm" data-attendance-status="' + esc(r.id) + '">' +
                             ['Not recorded', 'Attended', 'Did not attend'].map(function (value) {
                                 return '<option value="' + value + '"' + (status === value ? ' selected' : '') + '>' + value + '</option>';
                             }).join('') + '</select>' +
                             (r.attendanceMarkedBy ? '<div class="small text-muted mt-1">Marked by ' + esc(r.attendanceMarkedBy) + '</div>' : '') +
                             '</td><td class="text-nowrap"><button class="btn btn-sm btn-outline-primary me-1" data-attendance-save="' + esc(r.id) + '">Save</button>' +
                             '<button class="btn btn-sm btn-outline-danger" data-rm="' + esc(r.id) + '" title="Remove sign-up"><i class="bi bi-trash"></i></button></td></tr>';
                  }).join('') +
                  walkins.map(function (record) {
                      var member = members.filter(function (item) { return String(item.id) === String(record.memberId); })[0];
                      return '<tr><td>' + esc(member ? memberName(member) : 'KK member') +
                             '<div class="small text-muted">KK ID: ' + esc(member ? (member.memberNo || member.id) : record.memberId) + ' · Walk-in</div></td>' +
                             '<td></td><td></td><td></td><td></td><td><span class="badge text-bg-success">Attended</span>' +
                             (record.attendanceMarkedBy ? '<div class="small text-muted mt-1">Marked by ' + esc(record.attendanceMarkedBy) + '</div>' : '') +
                             '</td><td></td></tr>';
                  }).join('') + '</tbody></table></div>'
                : walkinForm + '<p class="text-muted mb-0">No one has signed up or been recorded as a walk-in attendee yet.</p>';
            show('Event roster: ' + a.title + ' (' + (rows.length + walkins.length) + ')', body,
                 '<button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Close</button>');
            var walkinButton = document.getElementById('evWalkinAdd');
            if (walkinButton) walkinButton.addEventListener('click', function () {
                var memberSelect = document.getElementById('evWalkinMember');
                if (!memberSelect || !memberSelect.value) {
                    AppAlerts.warning('Choose an active KK member first.');
                    return;
                }
                walkinButton.disabled = true;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'api.php?action=event_walkin', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.onload = function () {
                    var res = {};
                    try { res = JSON.parse(xhr.responseText); } catch (ex) {}
                    if (xhr.status !== 200 || !res.ok) {
                        walkinButton.disabled = false;
                        AppAlerts.error(res.error || 'Could not record the walk-in attendee.');
                        return;
                    }
                    walkInsHere.push(res.record);
                    showList(a);
                };
                xhr.onerror = function () {
                    walkinButton.disabled = false;
                    AppAlerts.error('No connection. Could not record the walk-in attendee.');
                };
                xhr.send(JSON.stringify({ eventId: a.id, memberId: memberSelect.value }));
            });
            document.getElementById('evRegBody').onclick = function (e) {
                var attendanceButton = e.target.closest('[data-attendance-save]');
                if (attendanceButton) {
                    var registrationId = attendanceButton.getAttribute('data-attendance-save');
                    var select = document.querySelector('[data-attendance-status="' + registrationId + '"]');
                    if (!select) return;
                    attendanceButton.disabled = true;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'api.php?action=event_attendance', true);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.onload = function () {
                        var res = {};
                        try { res = JSON.parse(xhr.responseText); } catch (ex) {}
                        if (xhr.status !== 200 || !res.ok) {
                            attendanceButton.disabled = false;
                            AppAlerts.error(res.error || 'Could not save attendance.');
                            return;
                        }
                        attendanceUpdates[registrationId] = {
                            status: res.registration.attendanceStatus,
                            markedAt: res.registration.attendanceMarkedAt || '',
                            markedBy: res.registration.attendanceMarkedBy || ''
                        };
                        showList(a);
                    };
                    xhr.onerror = function () {
                        attendanceButton.disabled = false;
                        AppAlerts.error('No connection. Could not save attendance.');
                    };
                    xhr.send(JSON.stringify({
                        eventId: a.id,
                        registrationId: registrationId,
                        status: select.value
                    }));
                    return;
                }
                var b = e.target.closest('[data-rm]');
                if (!b) return;
                var rid = b.getAttribute('data-rm');
                var left = allRegs().filter(function (r) { return r.id !== rid; });
                try {
                    DB.setItem(KEY, JSON.stringify(left));
                    addedHere = addedHere.filter(function (r) { return r.id !== rid; });
                    showList(a);
                } catch (ex) { AppAlerts.error(ex.message || 'Could not remove the sign-up.'); }
            };
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();