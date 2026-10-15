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

    function post(action, body) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api.php?action=' + action, false);       // false = wait for the answer
        xhr.setRequestHeader('Content-Type', 'application/json');
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

    w.DB = {
        getItem: function (key) {
            return Object.prototype.hasOwnProperty.call(cache, key) ? cache[key] : null;
        },
        setItem: function (key, value) {
            if (VIEW_ONLY) return;
            value = String(value);
            if (key === 'skRoles') {
                throw new Error('Use the dedicated SK role actions to change roles.');
            }
            if (key === 'kkMembers' && w.USE_MEMBERS_TABLE) {
                throw new Error('Use DB.saveMembers to save KK member records.');
            }
            post('set', { key: key, value: value });
            cache[key] = value;
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
        // public registration form: adds ONE pending request on the server
        register: function (data) { return post('register', { data: data }); },
        removeItem: function (key) {
            if (VIEW_ONLY) return;
            if (key === 'skRoles') throw new Error('Use the dedicated SK role actions to change roles.');
            post('remove', { key: key });
            delete cache[key];
        }
    };
})(window);