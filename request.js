(function () {
    'use strict';

    var $ = function (id) { return document.getElementById(id); };
    var OFFICER = window.SK_OFFICER || 'SK Officer';
    var BARANGAY = 'Sikat', CITY = 'Alfonso', PROVINCE = 'Cavite';
    var MIN_AGE = 15, MAX_AGE = 30;
    var FIELD_LIST = [
        ['firstName', 'First Name'], ['middleName', 'Middle Name'], ['lastName', 'Last Name'], ['suffix', 'Suffix'],
        ['birthDate', 'Date of Birth'], ['gender', 'Gender'], ['genderOther', 'Gender (self-described)'],
        ['civilStatus', 'Civil Status'], ['religion', 'Religion'], ['religionOther', 'Religion (specified)'],
        ['houseNo', 'House Number'], ['street', 'Street'], ['purok', 'Purok / Sitio'],
        ['mobile', 'Mobile Number'], ['email', 'Email Address'], ['facebook', 'Facebook Account'],
        ['classification', 'Youth Classification'], ['classificationOther', 'Classification (specified)'],
        ['educationLevel', 'Educational Attainment'], ['educationOther', 'Educational Attainment (specified)'],
        ['educationStatus', 'Education Status'], ['educationStatusOther', 'Education Status (specified)'],
        ['school', 'School / Institution'], ['schoolOther', 'School / Institution (specified)'],
        ['employment', 'Employment Status'], ['employmentOther', 'Employment Status (specified)'],
        ['salaryIncome', 'Monthly Salary / Income (₱)'], ['occupation', 'Occupation / Job'],
        ['occupationOther', 'Occupation (specified)'], ['training', 'Training / Skills Program'],
        ['trainingOther', 'Training (specified)'], ['pwd', 'PWD'], ['disabilityType', 'Disability Type'],
        ['disabilityOther', 'Disability (specified)'], ['skVoter', 'Registered SK Voter'],
        ['voterNo', 'Precinct Voter Number'], ['interests', 'Program Interests'],
        ['interestOther', 'Program Interest (specified)'], ['notes', 'Additional Notes'], ['photo', 'Profile Picture']
    ];
    var FIELD_LABELS = {};
    FIELD_LIST.forEach(function (field) { FIELD_LABELS[field[0]] = field[1]; });
    var MEMBER_INPUTS = FIELD_LIST.map(function (field) { return field[0]; })
        .filter(function (key) { return ['pwd', 'skVoter', 'interests', 'photo'].indexOf(key) < 0; });
    var PUBLIC_FIELDS = FIELD_LIST.filter(function (field) {
        return ['firstName', 'middleName', 'lastName', 'suffix', 'interests', 'photo'].indexOf(field[0]) < 0;
    });
    var members = [], editRequests = [], pendingRegistrations = [];
    var requestStatusFilter = 'pending', requestDatePreset = 'all', currentEditRequestId = null;
    var publicModal = bootstrap.Modal.getOrCreateInstance($('pubModal'));
    var editModal = bootstrap.Modal.getOrCreateInstance($('reviewModal'));

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }
    function fullName(record) {
        return [record.firstName, record.middleName, record.lastName, record.suffix].filter(Boolean).join(' ') || 'Unnamed applicant';
    }
    function nowISO() { return new Date().toISOString(); }
    function fmtDate(value) {
        if (!value) return '—';
        var date = new Date(String(value).length === 10 ? value + 'T00:00:00' : value);
        return isNaN(date) ? '—' : date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    function calcAge(value) {
        var birth = new Date(value + 'T00:00:00');
        if (isNaN(birth)) return NaN;
        var today = new Date();
        var age = today.getFullYear() - birth.getFullYear();
        if (today.getMonth() < birth.getMonth() ||
            (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age--;
        return age;
    }
    function readList(key) {
        var raw = DB.getItem(key);
        if (raw == null || raw === '') return [];
        var value = JSON.parse(raw);
        if (!Array.isArray(value)) throw new Error('The ' + key + ' data is not a list.');
        return value;
    }
    function loadData() {
        members = readList('kkMembers');
        editRequests = readList('kkEditRequests');
        pendingRegistrations = readList('kkPendingRegistrations');
    }
    function notify(type, message) {
        var alert = document.createElement('div');
        alert.className = 'alert alert-' + type + ' shadow small mb-2';
        alert.textContent = message;
        $('toastBox').appendChild(alert);
        window.setTimeout(function () { alert.remove(); }, 5000);
    }
    function setList(key, list) {
        DB.setItem(key, JSON.stringify(list));
    }
    function findMember(id) {
        return members.filter(function (member) { return String(member.id) === String(id); })[0] || null;
    }
    function requestState(item) {
        if (item.status === 'Edit Request' || item.status === 'Pending') return 'pending';
        return String(item.status || '').toLowerCase();
    }
    function requestDate(item, type) {
        var value = type === 'public' ? item.submittedAt : item.requestedAt;
        return value ? String(value).slice(0, 10) : '';
    }
    function matchesStatus(item) {
        var status = requestState(item);
        return requestStatusFilter === 'all' || (requestStatusFilter === 'done'
            ? status === 'approved' || status === 'rejected'
            : status === requestStatusFilter);
    }
    function matchesDate(item, type) {
        var from = $('requestDateFrom').value, to = $('requestDateTo').value;
        if (from && to && from > to) return false;
        var date = requestDate(item, type);
        return !!date && (!from || date >= from) && (!to || date <= to);
    }
    function dateText(value) {
        return value ? fmtDate(value) : '—';
    }
    function statusBadge(status) {
        var css = status === 'Approved' ? 'success' : status === 'Rejected' ? 'danger' : 'warning text-dark';
        return '<span class="badge bg-' + css + '">' + esc(status || 'Pending') + '</span>';
    }
    function button(action, id, label, css) {
        return '<button type="button" class="btn btn-sm ' + css + '" data-act="' + action + '" data-id="' + esc(id) + '">' + label + '</button>';
    }
    function updateDashboard() {
        var counts = { pending: 0, approved: 0, rejected: 0, done: 0, all: 0 };
        editRequests.forEach(function (item) {
            if (matchesDate(item, 'edit')) counts[requestState(item)] = (counts[requestState(item)] || 0) + 1;
        });
        pendingRegistrations.forEach(function (item) {
            if (matchesDate(item, 'public')) counts[requestState(item)] = (counts[requestState(item)] || 0) + 1;
        });
        counts.done = counts.approved + counts.rejected;
        counts.all = counts.pending + counts.done;
        $('pendingRequestTotal').textContent = counts.pending;
        $('approvedRequestTotal').textContent = counts.approved;
        $('rejectedRequestTotal').textContent = counts.rejected;
        $('doneRequestTotal').textContent = counts.done;
        $('allRequestTotal').textContent = counts.all;
        var from = $('requestDateFrom').value, to = $('requestDateTo').value;
        $('requestDateLabel').textContent = from && to && from > to
            ? 'Invalid date range: the start date must be before the end date.'
            : (from || to ? 'Showing requests' + (from ? ' from ' + fmtDate(from) : '') + (to ? ' to ' + fmtDate(to) : '') : 'Showing requests from all dates');
        document.querySelectorAll('[data-request-status]').forEach(function (card) {
            card.classList.toggle('active', card.dataset.requestStatus === requestStatusFilter);
        });
        document.querySelectorAll('[data-request-range]').forEach(function (buttonEl) {
            buttonEl.classList.toggle('active', buttonEl.dataset.requestRange === requestDatePreset);
        });
    }
    function updatePendingTotal(publicCount, editCount) {
        $('requestTotalCount').textContent = (publicCount + editCount) + ' pending';
        var badge = document.querySelector('.sidebar-request-count');
        if (badge) {
            badge.textContent = publicCount + editCount;
            badge.classList.toggle('has-pending', publicCount + editCount > 0);
            badge.setAttribute('aria-label', (publicCount + editCount) + ' pending requests');
        }
    }
    function render() {
        var datedPublic = pendingRegistrations.filter(function (item) { return matchesDate(item, 'public'); });
        var datedEdits = editRequests.filter(function (item) { return matchesDate(item, 'edit'); });
        var publicPendingCount = datedPublic.filter(function (item) { return requestState(item) === 'pending'; }).length;
        var editPendingCount = datedEdits.filter(function (item) { return requestState(item) === 'pending'; }).length;
        var publicRows = datedPublic.filter(matchesStatus).sort(function (a, b) {
            return (requestState(a) === 'pending' ? 0 : 1) - (requestState(b) === 'pending' ? 0 : 1)
                || String(b.submittedAt || '').localeCompare(String(a.submittedAt || ''));
        });
        var editRows = datedEdits.filter(matchesStatus).sort(function (a, b) {
            return String(b.requestedAt || '').localeCompare(String(a.requestedAt || ''));
        });
        $('pubCount').textContent = publicPendingCount;
        $('reqCount').textContent = editPendingCount;
        $('pubBody').innerHTML = publicRows.length ? publicRows.map(function (item) {
            return '<tr><td><div class="fw-bold">' + esc(fullName(item)) + '</div><small class="text-muted">' + esc(item.refNo || '') + '</small></td>' +
                '<td>' + esc(item.purok || '—') + '</td><td>' + dateText(item.submittedAt) + '</td><td>' + statusBadge(item.status) + '</td>' +
                '<td class="text-end">' + button('pubview', item.id, 'Review', 'btn-outline-dark') + '</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-center text-muted py-4">No public registrations match these filters.</td></tr>';
        $('requestsBody').innerHTML = editRows.length ? editRows.map(function (item) {
            var member = findMember(item.memberId);
            return '<tr><td><div class="fw-bold">' + esc(member ? fullName(member) : item.memberName || 'Member record unavailable') + '</div><small class="text-muted">' + esc(item.memberNo || '') + '</small></td>' +
                '<td>' + dateText(item.requestedAt) + '</td><td>' + Object.keys(item.changes || {}).length + '</td><td>' + statusBadge(item.status) + '</td>' +
                '<td class="text-end">' + button('review', item.id, 'View Details', 'btn-outline-dark') + '</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-center text-muted py-4">No profile edit requests match these filters.</td></tr>';
        updatePendingTotal(publicPendingCount, editPendingCount);
        updateDashboard();
    }
    function valueHtml(key, value) {
        if (key === 'photo') return value ? '<img src="' + esc(value) + '" alt="" class="rounded border object-fit-cover" style="width:44px;height:44px;">' : '—';
        if (key === 'birthDate') return esc(fmtDate(value));
        if (Array.isArray(value)) return value.length ? esc(value.join(', ')) : '—';
        return value == null || value === '' ? '—' : esc(value);
    }
    function showEditRequest(id) {
        var request = editRequests.filter(function (item) { return String(item.id) === String(id); })[0];
        if (!request) return;
        currentEditRequestId = request.id;
        var member = findMember(request.memberId);
        var changes = request.changes && typeof request.changes === 'object' ? request.changes : {};
        var rows = Object.keys(changes).map(function (key) {
            return '<tr><td class="fw-bold">' + esc(FIELD_LABELS[key] || key) + '</td><td>' + valueHtml(key, request.previous && request.previous[key]) + '</td><td>' + valueHtml(key, changes[key]) + '</td></tr>';
        }).join('');
        var pending = request.status === 'Edit Request';
        $('reviewBody').innerHTML =
            '<div class="d-flex justify-content-between align-items-start mb-3"><div><h6 class="fw-bold mb-0">' + esc(member ? fullName(member) : request.memberName || 'Member record unavailable') + '</h6>' +
            '<small class="text-muted">' + esc(request.memberNo || '') + ' • requested ' + dateText(request.requestedAt) + ' by ' + esc(request.requestedBy || '—') + '</small></div>' + statusBadge(request.status) + '</div>' +
            (pending && (!member || member.status === 'Archived') ? '<div class="alert alert-warning small">This member is missing or archived. The edit cannot be approved.</div>' : '') +
            '<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Field</th><th>Current (official)</th><th>Requested</th></tr></thead><tbody>' +
            (rows || '<tr><td colspan="3" class="text-muted">No changed fields were recorded.</td></tr>') + '</tbody></table></div>' +
            (pending ? '<label class="form-label" for="reviewRemarks">Remarks <span class="text-muted fw-normal">(optional)</span></label><textarea id="reviewRemarks" class="form-control" rows="2" style="height:auto;"></textarea>' :
                '<div class="small text-muted">Reviewed ' + dateText(request.reviewedAt) + ' by ' + esc(request.reviewedBy || '—') + (request.remarks ? ' — ' + esc(request.remarks) : '') + '</div>');
        $('approveBtn').classList.toggle('d-none', !pending);
        $('rejectBtn').classList.toggle('d-none', !pending);
        editModal.show();
    }
    function archiveMembersOverAgeLimit() {
        var count = 0;
        members.forEach(function (member) {
            if (member.status === 'Archived' || !member.birthDate || !(calcAge(member.birthDate) > MAX_AGE)) return;
            var reason = 'Automatically archived – exceeded youth age eligibility.';
            var archivedAt = nowISO();
            member.status = 'Archived';
            member.archiveReason = reason;
            member.archivedAt = archivedAt;
            member.archivedBy = 'System';
            member.archiveType = 'auto';
            if (!Array.isArray(member.history)) member.history = [];
            member.history.push({ at: archivedAt, action: 'Auto-archived', by: 'System', note: reason });
            editRequests.forEach(function (request) {
                if (String(request.memberId) === String(member.id) && request.status === 'Edit Request') {
                    request.status = 'Rejected';
                    request.reviewedAt = nowISO();
                    request.reviewedBy = 'System';
                    request.remarks = 'Profile was archived.';
                }
            });
            count++;
        });
        return count;
    }
    function reviewEditRequest(approve) {
        var request = editRequests.filter(function (item) { return String(item.id) === String(currentEditRequestId); })[0];
        if (!request || request.status !== 'Edit Request') {
            notify('warning', 'This profile edit request was already reviewed.');
            return;
        }
        var member = findMember(request.memberId);
        var remarks = $('reviewRemarks') ? $('reviewRemarks').value.trim() : '';
        if (approve && (!member || member.status === 'Archived')) {
            notify('danger', 'This profile is missing or archived, so the changes cannot be applied.');
            return;
        }
        var now = nowISO();
        var oldMembers = JSON.stringify(members);
        var oldRequests = JSON.stringify(editRequests);
        if (approve) {
            Object.keys(request.changes || {}).forEach(function (key) { member[key] = request.changes[key]; });
            if (!Array.isArray(member.history)) member.history = [];
            member.updatedAt = now;
            member.history.push({ at: now, action: 'Edit approved', by: OFFICER,
                note: Object.keys(request.changes || {}).map(function (key) { return FIELD_LABELS[key] || key; }).join(', ') });
            request.status = 'Approved';
        } else {
            request.status = 'Rejected';
            if (member) {
                if (!Array.isArray(member.history)) member.history = [];
                member.history.push({ at: now, action: 'Edit rejected', by: OFFICER, note: remarks || 'Changes were not approved.' });
            }
        }
        request.reviewedAt = now;
        request.reviewedBy = OFFICER;
        request.remarks = remarks;
        var archivedCount = approve ? archiveMembersOverAgeLimit() : 0;
        try {
            if (approve) {
                if (window.USE_MEMBERS_TABLE) DB.saveMembers(members);
                else setList('kkMembers', members);
            }
            setList('kkEditRequests', editRequests);
        } catch (error) {
            members = JSON.parse(oldMembers);
            editRequests = JSON.parse(oldRequests);
            notify('danger', error.message || 'Could not save the request review.');
            return;
        }
        editModal.hide();
        notify('success', approve ? 'Changes approved and the profile was updated.' : 'Changes rejected. The profile was left unchanged.');
        if (archivedCount) {
            notify('info', archivedCount + ' profile' + (archivedCount === 1 ? ' was' : 's were') + ' automatically archived for exceeding the youth age limit.');
        }
        render();
    }
    function showPublicRequest(id) {
        var request = pendingRegistrations.filter(function (item) { return String(item.id) === String(id); })[0];
        if (!request) return;
        var rows = PUBLIC_FIELDS.filter(function (field) {
            var value = request[field[0]];
            return value != null && value !== '' && (!Array.isArray(value) || value.length);
        }).map(function (field) {
            var value = field[0] === 'birthDate'
                ? fmtDate(request.birthDate) + ' (age ' + calcAge(request.birthDate) + ')'
                : request[field[0]];
            return '<div class="col-md-6"><div class="text-muted small">' + esc(field[1]) + '</div><div class="small fw-bold">' + valueHtml(field[0], value) + '</div></div>';
        }).join('');
        $('pubBodyDetail').innerHTML =
            '<h5 class="fw-bold mb-0">' + esc(fullName(request)) + '</h5><small class="text-muted">' + esc(request.refNo || '') + ' · submitted ' + dateText(request.submittedAt) + '</small> ' + statusBadge(request.status) +
            '<div class="row g-3 mt-1">' + rows + '</div>' +
            (request.status !== 'Pending' ? '<div class="alert alert-secondary small mt-3 mb-0">' + esc(request.status) + ' on ' + dateText(request.reviewedAt) + (request.remarks ? ' — ' + esc(request.remarks) : '') + '</div>' : '');
        $('pubRemarks').value = '';
        $('pubRemarksBox').classList.toggle('d-none', request.status !== 'Pending');
        $('pubFooter').innerHTML = (request.status === 'Pending'
            ? button('pubreject', request.id, '<i class="bi bi-x-circle"></i> Reject', 'btn-outline-dark') + button('pubapprove', request.id, '<i class="bi bi-check-circle"></i> Approve & Register', 'btn-dark')
            : '') + '<button type="button" class="btn-outline" data-bs-dismiss="modal">Close</button>';
        publicModal.show();
    }
    function duplicateMember(applicant) {
        var first = String(applicant.firstName || '').toLowerCase();
        var last = String(applicant.lastName || '').toLowerCase();
        return members.some(function (member) {
            return String(member.firstName || '').toLowerCase() === first
                && String(member.lastName || '').toLowerCase() === last
                && member.birthDate === applicant.birthDate;
        });
    }
    function dobMessage(value) {
        var birth = new Date(value + 'T00:00:00');
        if (isNaN(birth)) return 'Please enter a valid date of birth.';
        if (birth > new Date()) return 'Date of birth cannot be in the future.';
        var age = calcAge(value);
        if (age < MIN_AGE || age > MAX_AGE) return 'Only youth aged ' + MIN_AGE + ' to ' + MAX_AGE + ' may register.';
        return '';
    }
    function nextMemberNo() {
        var year = new Date().getFullYear(), max = 0;
        members.forEach(function (member) {
            var match = new RegExp('^KK-' + year + '-(\\d+)$').exec(member.memberNo || '');
            if (match) max = Math.max(max, parseInt(match[1], 10));
        });
        return 'KK-' + year + '-' + String(max + 1).padStart(3, '0');
    }
    function approvePublicRequest(id) {
        loadData();
        var request = pendingRegistrations.filter(function (item) { return String(item.id) === String(id); })[0];
        if (!request || request.status !== 'Pending') {
            notify('warning', 'This registration was already reviewed.');
            render();
            return;
        }
        var dobError = dobMessage(request.birthDate);
        if (dobError) { notify('danger', dobError); return; }
        if (duplicateMember(request)) {
            notify('danger', 'This youth is already registered (same name and date of birth). Reject this request instead.');
            return;
        }
        var record = {};
        MEMBER_INPUTS.concat(['pwd', 'skVoter']).forEach(function (key) { record[key] = request[key] || ''; });
        record.interests = Array.isArray(request.interests) ? request.interests.slice() : [];
        record.photo = '';
        record.id = 'm' + Date.now();
        record.memberNo = nextMemberNo();
        record.barangay = BARANGAY;
        record.city = CITY;
        record.province = PROVINCE;
        record.status = 'Active';
        record.createdAt = nowISO();
        record.history = [{ at: record.createdAt, action: 'Registered', by: OFFICER, note: 'Approved from public registration ' + (request.refNo || '') + '.' }];
        members.push(record);
        request.status = 'Approved';
        request.reviewedAt = nowISO();
        request.reviewedBy = OFFICER;
        request.memberId = record.id;
        request.memberNo = record.memberNo;
        try {
            if (window.USE_MEMBERS_TABLE) DB.saveMembers(members);
            else setList('kkMembers', members);
            setList('kkPendingRegistrations', pendingRegistrations);
        } catch (error) {
            members.pop();
            request.status = 'Pending';
            delete request.reviewedAt;
            delete request.reviewedBy;
            delete request.memberId;
            delete request.memberNo;
            notify('danger', error.message || 'Could not save the registration review.');
            return;
        }
        publicModal.hide();
        notify('success', fullName(record) + ' was approved and registered (' + record.memberNo + ').');
        render();
    }
    function rejectPublicRequest(id) {
        var remarks = $('pubRemarks').value.trim();
        if (!remarks) {
            notify('warning', 'Please write a short reason (remarks) before rejecting.');
            $('pubRemarks').focus();
            return;
        }
        var request = pendingRegistrations.filter(function (item) { return String(item.id) === String(id); })[0];
        if (!request || request.status !== 'Pending') {
            notify('warning', 'This registration was already reviewed.');
            render();
            return;
        }
        var previous = JSON.stringify(request);
        request.status = 'Rejected';
        request.reviewedAt = nowISO();
        request.reviewedBy = OFFICER;
        request.remarks = remarks;
        try {
            setList('kkPendingRegistrations', pendingRegistrations);
        } catch (error) {
            Object.keys(request).forEach(function (key) { delete request[key]; });
            Object.assign(request, JSON.parse(previous));
            notify('danger', error.message || 'Could not save the registration review.');
            return;
        }
        publicModal.hide();
        notify('success', 'Registration rejected.');
        render();
    }
    function dateInputValue(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }
    function setDateRange(preset) {
        var today = new Date(), from = '', to = '';
        if (preset === 'today') from = to = dateInputValue(today);
        else if (preset === 'week') {
            var monday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));
            var sunday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate());
            sunday.setDate(sunday.getDate() + 6);
            from = dateInputValue(monday); to = dateInputValue(sunday);
        } else if (preset === 'first-half') {
            from = dateInputValue(new Date(today.getFullYear(), today.getMonth(), 1));
            to = dateInputValue(new Date(today.getFullYear(), today.getMonth(), 15));
        } else if (preset === 'second-half') {
            from = dateInputValue(new Date(today.getFullYear(), today.getMonth(), 16));
            to = dateInputValue(new Date(today.getFullYear(), today.getMonth() + 1, 0));
        } else if (preset === 'month') {
            from = dateInputValue(new Date(today.getFullYear(), today.getMonth(), 1));
            to = dateInputValue(new Date(today.getFullYear(), today.getMonth() + 1, 0));
        }
        $('requestDateFrom').value = from;
        $('requestDateTo').value = to;
        requestDatePreset = preset;
        render();
    }

    document.addEventListener('click', function (event) {
        var statusCard = event.target.closest('[data-request-status]');
        if (statusCard) {
            requestStatusFilter = statusCard.dataset.requestStatus;
            render();
            return;
        }
        var rangeButton = event.target.closest('[data-request-range]');
        if (rangeButton) {
            setDateRange(rangeButton.dataset.requestRange);
            return;
        }
        var actionButton = event.target.closest('[data-act]');
        if (!actionButton) return;
        var id = actionButton.dataset.id;
        if (actionButton.dataset.act === 'pubview') showPublicRequest(id);
        else if (actionButton.dataset.act === 'pubapprove') approvePublicRequest(id);
        else if (actionButton.dataset.act === 'pubreject') rejectPublicRequest(id);
        else if (actionButton.dataset.act === 'review') showEditRequest(id);
    });
    $('approveBtn').addEventListener('click', function () { reviewEditRequest(true); });
    $('rejectBtn').addEventListener('click', function () { reviewEditRequest(false); });
    ['requestDateFrom', 'requestDateTo'].forEach(function (id) {
        $(id).addEventListener('change', function () {
            requestDatePreset = '';
            render();
        });
    });
    window.addEventListener('storage', function (event) {
        if (['kkMembers', 'kkEditRequests', 'kkPendingRegistrations'].indexOf(event.key) >= 0) {
            try { loadData(); render(); } catch (error) { notify('danger', error.message); }
        }
    });
    try {
        loadData();
        render();
    } catch (error) {
        notify('danger', error.message || 'Could not load request data.');
    }
})();
