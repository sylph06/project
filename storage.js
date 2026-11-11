/* =========================================================
   DB  - replaces the browser localStorage with the MySQL database.
   Same three methods the pages already use:
       DB.getItem(key)   DB.setItem(key, text)   DB.removeItem(key)
   - reading uses the data PHP put in window.__DB (init.php)
   - writing is sent to api.php right away (the page waits, so
     nothing is lost when you move to another page)
   - public viewers (window.SK_PUBLIC) are view only: saves are ignored here
     and refused again by api.php
   - if the server refuses, an Error is thrown, so the existing
     try/catch "could not save" messages in the pages still work
   ========================================================= */
(function (w) {
    'use strict';

    var cache = w.__DB || {};
    var VIEW_ONLY = !!w.SK_PUBLIC;       // public viewers can read but never save
    var existingDB = w.DB && typeof w.DB === 'object' ? w.DB : {};

    function post(action, body) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php?action=' + action, false);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-Token', w.SK_CSRF_TOKEN || '');
        xhr.send(JSON.stringify(body));
        var res = {};
        try { res = JSON.parse(xhr.responseText); } catch (e) {}
        if (xhr.status !== 200 || !res.ok) throw new Error(res.error || 'Could not save to the database.');
        return res;
    }

    function stable(value) {
        if (Array.isArray(value)) return value.map(stable);
        if (value && typeof value === 'object') {
            var result = {};
            Object.keys(value).sort().forEach(function (key) { result[key] = stable(value[key]); });
            return result;
        }
        return value;
    }

    function sameValue(a, b) {
        return JSON.stringify(stable(a)) === JSON.stringify(stable(b));
    }

    w.DB = Object.assign({}, existingDB, {
        getItem: function (key) {
            return Object.prototype.hasOwnProperty.call(cache, key) ? cache[key] : null;
        },
        setItem: function (key, value) {
            if (VIEW_ONLY) return;
            value = String(value);
            if (key === 'skRoles') {
                throw new Error('Use the dedicated SK role actions to change roles.');
            }
            if (key === 'kkMembers') throw new Error('Use DB.saveMemberData to save KK member records.');
            var body = { key: key, value: value };
            body.expected = Object.prototype.hasOwnProperty.call(cache, key) ? cache[key] : null;
            var result = post('set', body);
            cache[key] = typeof result.value === 'string' ? result.value : value;
        },
        meetingMinutes: function (action, meetingId, minutes) {
            if (['meeting_minutes_save', 'meeting_minutes_approve', 'meeting_minutes_reopen'].indexOf(action) < 0) {
                throw new Error('Unknown meeting minutes action.');
            }
            var body = { meetingId: meetingId };
            if (action === 'meeting_minutes_save') body.minutes = String(minutes || '');
            var result = post(action, body);
            var meetings;
            try {
                meetings = JSON.parse(cache.skMeetings || '[]');
            } catch (e) {
                throw new Error('Could not read the current meeting data.');
            }
            if (!Array.isArray(meetings)) throw new Error('Meeting data must be a list.');
            cache.skMeetings = JSON.stringify(meetings.map(function (meeting) {
                return meeting && String(meeting.id) === String(meetingId) ? result.meeting : meeting;
            }));
            return result.meeting;
        },
        saveMeetingAttendance: function (meetingId, expected, records) {
            var result = post('meeting_attendance_save', {
                meetingId: meetingId,
                expected: expected,
                records: records
            });
            var attendance;
            try {
                attendance = JSON.parse(cache.skAttendance || '{}');
            } catch (e) {
                throw new Error('Could not read the current meeting attendance.');
            }
            if (!attendance || typeof attendance !== 'object' || Array.isArray(attendance)) {
                throw new Error('Meeting attendance data must be an object.');
            }
            attendance[meetingId] = result.attendance;
            cache.skAttendance = JSON.stringify(attendance);
            return result.attendance;
        },
        deleteMeetingAttendance: function (meetingId, expected) {
            var result = post('meeting_attendance_delete', {
                meetingId: meetingId,
                expected: expected
            });
            var attendance;
            try {
                attendance = JSON.parse(cache.skAttendance || '{}');
            } catch (e) {
                throw new Error('The attendance was deleted, but the local cache could not be refreshed.');
            }
            if (attendance && typeof attendance === 'object' && !Array.isArray(attendance)) {
                delete attendance[meetingId];
                cache.skAttendance = JSON.stringify(attendance);
            }
            return result;
        },
        saveMembers: function (members) {
            if (VIEW_ONLY) return;
            if (!Array.isArray(members)) throw new Error('KK member records must be an array.');
            var previous = [];
            try {
                previous = JSON.parse(cache.kkMembers || '[]');
                if (!Array.isArray(previous)) previous = [];
            } catch (e) {
                throw new Error('Could not read the current KK member data.');
            }
            var byId = {};
            previous.forEach(function (member) {
                if (member && member.id != null) byId[String(member.id)] = member;
            });
            members.forEach(function (member) {
                if (!member || member.id == null) throw new Error('Every KK member must have an id.');
                var id = String(member.id);
                if (!Object.prototype.hasOwnProperty.call(byId, id)) {
                    post('member_add', { member: member });
                } else if (!sameValue(byId[id], member)) {
                    post('member_update', { id: id, expected: byId[id], member: member });
                }
            });
            cache.kkMembers = JSON.stringify(members);
        },
        saveMemberData: function (members, requests) {
            if (VIEW_ONLY) throw new Error('Public viewers cannot change KK member data.');
            if (!Array.isArray(members) || !Array.isArray(requests)) {
                throw new Error('KK member and request data must be lists.');
            }
            if (w.USE_MEMBERS_TABLE) {
                w.DB.saveMembers(members);
                w.DB.setItem('kkEditRequests', JSON.stringify(requests));
                return;
            }
            var result = post('member_data_save', {
                members: members,
                requests: requests,
                expectedMembers: Object.prototype.hasOwnProperty.call(cache, 'kkMembers') ? cache.kkMembers : null,
                expectedRequests: Object.prototype.hasOwnProperty.call(cache, 'kkEditRequests') ? cache.kkEditRequests : null
            });
            cache.kkMembers = result.members;
            cache.kkEditRequests = result.requests;
        },
        registerMember: function (member, registration) {
            if (VIEW_ONLY) throw new Error('Public viewers cannot create member profiles.');
            if (!member || !member.id) throw new Error('A complete member profile is required.');
            var result = post('member_register', {
                member: member,
                registrationId: registration ? String(registration.id) : null
            });
            var members;
            try {
                members = JSON.parse(cache.kkMembers || '[]');
            } catch (e) {
                throw new Error('Could not read the current KK member data.');
            }
            if (!Array.isArray(members)) throw new Error('KK member records must be a list.');
            var found = false;
            members = members.map(function (item) {
                if (!item || String(item.id) !== String(member.id)) return item;
                found = true;
                return result.member;
            });
            if (!found) members.push(result.member);
            cache.kkMembers = JSON.stringify(members);
            if (registration) {
                var pending;
                try {
                    pending = JSON.parse(cache.kkPendingRegistrations || '[]');
                } catch (e) {
                    pending = [];
                }
                if (Array.isArray(pending)) {
                    cache.kkPendingRegistrations = JSON.stringify(pending.map(function (item) {
                        if (!item || String(item.id) !== String(registration.id)) return item;
                        return result.registration || Object.assign({}, item, {
                            status: 'Approved',
                            memberId: String(result.member.id),
                            memberNo: String(result.member.memberNo)
                        });
                    }));
                }
            }
            return result;
        },
        deleteMember: function (id) {
            if (VIEW_ONLY) return;
            var members;
            try {
                members = JSON.parse(cache.kkMembers || '[]');
            } catch (e) {
                throw new Error('Could not read the current KK member data.');
            }
            if (!Array.isArray(members)) throw new Error('KK member records must be an array.');
            var member = members.filter(function (item) {
                return item && String(item.id) === String(id);
            })[0];
            if (!member) throw new Error('The KK member record was not found.');
            post('member_delete', { id: String(id), expected: member });
            cache.kkMembers = JSON.stringify(members.filter(function (item) {
                return !item || String(item.id) !== String(id);
            }));
        },
        restoreMember: function (member, birthDate, reason) {
            if (VIEW_ONLY) throw new Error('Public viewers cannot restore member records.');
            if (!member || !member.id) throw new Error('The archived member record is missing.');
            var result = post('member_restore', {
                id: String(member.id),
                expected: member,
                birthDate: birthDate,
                reason: reason
            });
            var members;
            try {
                members = JSON.parse(cache.kkMembers || '[]');
            } catch (e) {
                throw new Error('Could not read the current KK member data.');
            }
            if (!Array.isArray(members)) throw new Error('KK member records must be a list.');
            cache.kkMembers = JSON.stringify(members.map(function (item) {
                return item && String(item.id) === String(member.id) ? result.member : item;
            }));
            return result.member;
        },
        assignRole: function (role) {
            var result = post('role_assign', { role: role });
            var roles = JSON.parse(cache.skRoles || '[]');
            if (!Array.isArray(roles)) roles = [];
            roles.push(result.role);
            cache.skRoles = JSON.stringify(roles);
            return result.role;
        },
        updateRole: function (role, expected) {
            var result = post('role_update', { id: role.id, role: role, expected: expected });
            var roles = JSON.parse(cache.skRoles || '[]');
            if (!Array.isArray(roles)) roles = [];
            cache.skRoles = JSON.stringify(roles.map(function (item) {
                return item && String(item.id) === String(role.id) ? result.role : item;
            }));
            return result.role;
        },
        endRole: function (role, expected, archive) {
            var result = post(archive ? 'role_archive' : 'role_end', {
                id: role.id,
                expected: expected || role
            });
            var roles = JSON.parse(cache.skRoles || '[]');
            if (!Array.isArray(roles)) roles = [];
            cache.skRoles = JSON.stringify(roles.map(function (item) {
                return item && String(item.id) === String(role.id) ? result.role : item;
            }));
            return result.role;
        },
        archiveExpiredRoles: function () {
            var result = post('role_archive_expired', {});
            if (Array.isArray(result.roles)) cache.skRoles = JSON.stringify(result.roles);
            return result;
        },
        saveCommittee: function (name, original, expected) {
            var result = post('committee_save', {
                name: name,
                original: original,
                expected: expected
            });
            cache.skCommittees = JSON.stringify(result.committees);
            if (Array.isArray(result.roles)) cache.skRoles = JSON.stringify(result.roles);
            return result;
        },
        listUnlinkedAccounts: function () {
            return post('accounts_unlinked', {});
        },
        linkMemberAccount: function (accountId, legacyId) {
            return post('account_link', { accountId: accountId, legacyId: legacyId });
        },
        createMemberAccount: function (legacyId, username, guardianConsent, consentVersion) {
            return post('account_create', {
                legacyId: legacyId,
                username: username,
                guardianConsent: guardianConsent,
                consentVersion: consentVersion
            });
        },
        resetMemberAccount: function (accountId) {
            return post('account_reset', { accountId: accountId });
        },
        deactivateMemberAccount: function (accountId) {
            return post('account_deactivate', { accountId: accountId });
        },
        removeItem: function (key) {
            if (VIEW_ONLY) return;
            if (key === 'skRoles') throw new Error('Use the dedicated SK role actions to change roles.');
            post('remove', {
                key: key,
                expected: Object.prototype.hasOwnProperty.call(cache, key) ? cache[key] : null
            });
            delete cache[key];
        }
    });
})(window);