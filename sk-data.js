/* =========================================================
   SHARED SK DATA LAYER  (used by members.html and sk-management.html)
   - KK youth records stay ONLY in 'kkMembers' (owned by registration.html).
   - An SK position is a small "role" record in 'skRoles' that points to a KK
     member by id (kkId). No SK person is ever stored twice.
   - Load it with <script src="sk-data.js"></script> BEFORE the page script.
   ========================================================= */
(function (w) {
    'use strict';

    var KK_KEY = 'kkMembers', ROLE_KEY = 'skRoles', MEET_KEY = 'skMeetings', ATT_KEY = 'skAttendance', UNLINKED_KEY = 'skUnlinked';

    var POSITIONS = ['SK Chairperson', 'SK Vice Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad'];
    var SEATS = { 'SK Chairperson': 1, 'SK Vice Chairperson': 1, 'SK Secretary': 1, 'SK Treasurer': 1, 'SK Kagawad': 7 };
    var ROLES = {
        'SK Chairperson': 'Leads the Sangguniang Kabataan, presides over its meetings, coordinates youth programs and represents the youth of Barangay Sikat.',
        'SK Vice Chairperson': 'Assists the SK Chairperson, presides in the Chairperson\'s absence and helps oversee SK programs and committees.',
        'SK Secretary': 'Keeps the minutes and records of the SK, prepares resolutions and communications, and maintains the official files.',
        'SK Treasurer': 'Manages SK funds, keeps financial records and prepares budget and expense reports.',
        'SK Kagawad': 'Serves as an SK council member: takes part in sessions, committee work and in planning and carrying out youth programs.'
    };

    function read(k, fb) { try { var v = JSON.parse(DB.getItem(k)); return v == null ? fb : v; } catch (e) { return fb; } }
    function write(k, v) {
        if (k === ROLE_KEY) return false;
        try { DB.setItem(k, JSON.stringify(v)); return true; } catch (e) { return false; }
    }
    function pad(n) { return String(n).padStart(2, '0'); }
    function todayISO() { var d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function nowISO() { return new Date().toISOString(); }
    function uid(p) { return p + Date.now() + Math.random().toString(36).slice(2, 7); }
    function norm(s) { return String(s || '').toLowerCase().replace(/\s+/g, ' ').trim(); }
    function calcAge(iso) {
        var b = new Date(iso + 'T00:00:00');
        if (isNaN(b)) return NaN;
        var t = new Date(), a = t.getFullYear() - b.getFullYear(), m = t.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && t.getDate() < b.getDate())) a--;
        return a;
    }
    function kkName(m) { return [m.firstName, m.middleName, m.lastName, m.suffix].filter(Boolean).join(' '); }
    var S = {
        POSITIONS: POSITIONS, SEATS: SEATS, ROLES: ROLES,
        KEYS: { KK: KK_KEY, ROLE: ROLE_KEY, MEET: MEET_KEY, ATT: ATT_KEY, UNLINKED: UNLINKED_KEY },
        read: read, write: write, todayISO: todayISO, nowISO: nowISO, uid: uid, norm: norm, calcAge: calcAge, kkName: kkName,
        kk: [], roles: []
    };

    S.load = function () {
        var kk = read(KK_KEY, []);
        S.kk = Array.isArray(kk) ? kk.filter(function (m) { return m && m.id; }) : [];
        var roles = read(ROLE_KEY, []);
        roles = Array.isArray(roles) ? roles : [];
        S.roles = roles.filter(function (r) { return r && r.id && r.kkId; });
        S.roles.forEach(function (r) {
            if (['Active', 'Inactive', 'Archived'].indexOf(r.status) < 0) r.status = 'Active';
            if (!Array.isArray(r.history)) r.history = [];
        });
        return S;
    };
    S.saveRoles = function () { return false; };
    S.kkById = function (id) { return S.kk.filter(function (m) { return m.id === id; })[0]; };
    S.roleOf = function (kkId) {
        var today = todayISO(), ranks = {
            'SK Chairperson': 5, 'SK Chairman': 5, 'SK Secretary': 4,
            'SK Treasurer': 3, 'SK Kagawad': 2, 'SK Vice Chairperson': 1
        };
        var roles = S.roles.filter(function (r) { return r.kkId === kkId; });
        var current = roles.filter(function (r) {
            return r.status === 'Active' && (!r.termStart || r.termStart <= today) && (!r.termEnd || r.termEnd >= today);
        });
        current.sort(function (a, b) { return (ranks[b.position] || 0) - (ranks[a.position] || 0); });
        if (current.length) return current[0];
        roles.sort(function (a, b) { return String(b.createdAt || '').localeCompare(String(a.createdAt || '')); });
        return roles[0];
    };
    S.addHist = function (r, action, note, by) { r.history.push({ at: nowISO(), action: action, by: by || window.SK_OFFICER || 'SK Officer', note: note || '' }); };

    // an SK role whose term has ended is archived (never deleted)
    S.autoArchive = function () {
        var n = 0, t = todayISO();
        S.roles.forEach(function (r) {
            if (r.termEnd && r.termEnd < t && r.status !== 'Archived') {
                r.status = 'Archived';
                S.addHist(r, 'Auto-archived', 'Term ended on ' + r.termEnd + '.', 'System');
                n++;
            }
        });
        return n;
    };

    // KK members that can serve right now: active role + KK record not archived
    S.officers = function () {
        var order = function (p) { return Math.max(0, POSITIONS.indexOf(p)); };
        return S.roles.filter(function (r) {
            var m = S.kkById(r.kkId);
            var today = todayISO();
            return r.status === 'Active' && (!r.termStart || r.termStart <= today) && (!r.termEnd || r.termEnd >= today)
                && m && m.status !== 'Archived';
        }).map(function (r) { return { role: r, kk: S.kkById(r.kkId), id: r.kkId, name: kkName(S.kkById(r.kkId)), position: r.position }; })
          .sort(function (a, b) { return order(a.position) - order(b.position) || a.name.localeCompare(b.name); });
    };

    S.seatError = function (position, exceptRoleId) {
        var cap = SEATS[position];
        if (!cap) return '';
        var today = todayISO();
        var used = S.roles.filter(function (x) {
            return x.id !== exceptRoleId && x.status === 'Active' && x.position === position
                && (!x.termStart || x.termStart <= today) && (!x.termEnd || x.termEnd >= today);
        }).length;
        if (used < cap) return '';
        return cap === 1 ? 'There is already an active ' + position + '. Deactivate that member first or choose another position.'
                         : 'All ' + cap + ' ' + position + ' seats are already filled by active members.';
    };

    w.SKData = S;
})(window);