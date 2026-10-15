/* =========================================================
   event-register.js  - public EVENT SIGN-UP (no account needed)
   Loaded by announcements.php and dashboard.php after the shared announcement script.
   - Adds a "Sign up for this event" button to the event details window.
   - Anyone (public viewer or officer) can sign up; api.php?action=event_register saves it.
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
        var esc = Core.esc;
        var addedHere = [];          // sign-ups made on this page (so officers see them without reloading)
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
            if (canSignUp(a)) html += '<button type="button" class="btn btn-primary" id="evRegBtn"><i class="bi bi-pencil-square me-1"></i> Sign up for this event</button>';
            if (html) footer.insertAdjacentHTML('beforeend', html);
            var b = document.getElementById('evRegBtn');
            if (b) b.addEventListener('click', function () { switchTo(id, function () { showForm(a); }); });
            var l = document.getElementById('evListBtn');
            if (l) l.addEventListener('click', function () { switchTo(id, function () { showList(a); }); });
        }
        new MutationObserver(addButtons).observe(footer, { childList: true });

        /* ---------- the sign-up form ---------- */
        function field(id, label, type, extra, req) {
            return '<div class="mb-3"><label class="form-label fw-semibold" for="' + id + '">' + label + (req ? ' <span class="text-danger">*</span>' : '') + '</label>' +
                '<input class="form-control" id="' + id + '" type="' + type + '" ' + (extra || '') + '></div>';
        }
        function showForm(a) {
            currentEvent = a;
            var body =
                '<p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>No account needed. Just tell us who is coming to <strong>' + esc(a.title) + '</strong>.</p>' +
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
            return saved;
        }
        function listFor(id) { return allRegs().filter(function (r) { return r.eventId === id; }); }

        function showList(a) {
            var rows = listFor(a.id);
            var body = rows.length
                ? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Name</th><th>Age</th><th>Purok</th><th>Mobile</th><th>Email</th><th></th></tr></thead><tbody>' +
                  rows.map(function (r) {
                      return '<tr><td>' + esc(r.name) + (r.notes ? '<div class="small text-muted">' + esc(r.notes) + '</div>' : '') + '</td><td>' + (r.age || '') + '</td><td>' + esc(r.purok) +
                             '</td><td>' + esc(r.mobile) + '</td><td>' + esc(r.email) + '</td>' +
                             '<td><button class="btn btn-sm btn-outline-danger" data-rm="' + esc(r.id) + '" title="Remove"><i class="bi bi-trash"></i></button></td></tr>';
                  }).join('') + '</tbody></table></div>'
                : '<p class="text-muted mb-0">No one has signed up yet.</p>';
            show('Sign-ups: ' + a.title + ' (' + rows.length + ')', body,
                 '<button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Close</button>');
            document.getElementById('evRegBody').onclick = function (e) {
                var b = e.target.closest('[data-rm]');
                if (!b) return;
                var rid = b.getAttribute('data-rm');
                var left = allRegs().filter(function (r) { return r.id !== rid; });
                try {
                    DB.setItem(KEY, JSON.stringify(left));
                    addedHere = addedHere.filter(function (r) { return r.id !== rid; });
                    showList(a);
                } catch (ex) { alert(ex.message || 'Could not remove the sign-up.'); }
            };
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();