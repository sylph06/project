(function (w) {
    'use strict';

    function api(action, body) {
        return fetch('api.php?action=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': w.SK_CSRF_TOKEN || ''
            },
            body: JSON.stringify(body || {})
        }).then(function (response) {
            return response.json().then(function (result) {
                if (!response.ok || !result.ok) throw new Error(result.error || 'The request failed.');
                return result;
            });
        });
    }

    function tokenFromValue(value) {
        value = String(value || '').trim();
        if (value.indexOf('KKID:') === 0) value = value.slice(5);
        return /^[a-f0-9]{64}$/i.test(value) ? value.toLowerCase() : '';
    }

    function qrDataUrl(token) {
        if (typeof w.qrcode !== 'function') throw new Error('QR generation is unavailable. Reload the page and try again.');
        var qr = w.qrcode(0, 'M');
        qr.addData('KKID:' + token);
        qr.make();
        return qr.createDataURL(5, 3);
    }

    function makeCard(member, token) {
        var card = document.createElement('article');
        card.className = 'kk-id-card';
        card.setAttribute('aria-label', 'KK Digital ID for ' + (member.name || 'member'));

        var header = document.createElement('div');
        header.className = 'kk-id-header';
        var logo = document.createElement('img');
        logo.src = 'images/sk-logo.jpg';
        logo.alt = '';
        var heading = document.createElement('div');
        var organization = document.createElement('strong');
        organization.textContent = 'SANGGUNIANG KABATAAN';
        var barangay = document.createElement('span');
        barangay.textContent = 'Barangay Sikat • Alfonso, Cavite';
        heading.appendChild(organization);
        heading.appendChild(barangay);
        header.appendChild(logo);
        header.appendChild(heading);

        var main = document.createElement('div');
        main.className = 'kk-id-main';
        var photo = document.createElement(member.photo ? 'img' : 'div');
        photo.className = 'kk-id-photo' + (member.photo ? '' : ' kk-id-initials');
        if (member.photo) {
            photo.src = member.photo;
            photo.alt = 'Member profile';
        } else {
            var parts = String(member.name || '?').trim().split(/\s+/);
            photo.textContent = (parts[0] || '?').charAt(0) + (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '');
        }

        var details = document.createElement('div');
        details.className = 'kk-id-details';
        var name = document.createElement('strong');
        name.textContent = member.name || 'KK Member';
        var number = document.createElement('span');
        number.textContent = 'KK No. ' + (member.memberNo || '—');
        var purok = document.createElement('span');
        purok.textContent = member.purok ? member.purok : 'Barangay Sikat';
        details.appendChild(name);
        details.appendChild(number);
        details.appendChild(purok);

        var qr = document.createElement('img');
        qr.className = 'kk-id-qr';
        qr.src = qrDataUrl(token);
        qr.alt = 'Secure KK ID QR code';
        main.appendChild(photo);
        main.appendChild(details);
        main.appendChild(qr);

        var footer = document.createElement('div');
        footer.className = 'kk-id-footer';
        var label = document.createElement('strong');
        label.textContent = 'KK YOUTH MEMBER';
        var status = document.createElement('span');
        status.textContent = member.status || 'Active';
        footer.appendChild(label);
        footer.appendChild(status);

        card.appendChild(header);
        card.appendChild(main);
        card.appendChild(footer);
        return card;
    }

    function renderCard(container, member, token) {
        if (!container) throw new Error('The digital ID display area is missing.');
        var card = makeCard(member, token);
        container.replaceChildren(card);
        return card;
    }

    function getToken(memberId) {
        return api('kk_id_token', { memberId: String(memberId) }).then(function (result) {
            return result.token;
        });
    }

    function recordPrint(memberId, method) {
        return api('kk_id_print', { memberId: String(memberId), method: method });
    }

    function printCard(member, method) {
        if (method !== 'automatic') return Promise.reject(new Error('Choose automatic printing to print an ID.'));
        return getToken(member.id).then(function (token) {
            return recordPrint(member.id, method).then(function () { return token; });
        }).then(function (token) {
            var printArea = document.getElementById('printArea');
            if (!printArea) throw new Error('The ID print area is missing.');
            printArea.replaceChildren(makeCard(member, token));
            setTimeout(function () { w.print(); }, 120);
        });
    }

    function startScanner(video, onToken, status) {
        if (!video || !w.navigator.mediaDevices || !w.navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('Camera scanning is unavailable. Enter the QR value manually.'));
        }
        if (typeof w.BarcodeDetector !== 'function') {
            return Promise.reject(new Error('This browser does not support camera QR scanning. Enter the QR value manually.'));
        }
        var detector;
        try {
            detector = new w.BarcodeDetector({ formats: ['qr_code'] });
        } catch (error) {
            return Promise.reject(new Error('This browser could not start QR scanning. Enter the QR value manually.'));
        }
        stopScanner(video);
        return w.navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
            .then(function (stream) {
                video.srcObject = stream;
                video.playsInline = true;
                return video.play().then(function () {
                    if (status) status.textContent = 'Point the camera at the member QR code.';
                    var scanning = false;
                    video._kkScanner = setInterval(function () {
                        if (scanning || video.readyState < 2) return;
                        scanning = true;
                        detector.detect(video).then(function (codes) {
                            if (!codes.length) return;
                            var token = tokenFromValue(codes[0].rawValue);
                            if (token) {
                                stopScanner(video);
                                onToken(token);
                            } else if (status) {
                                status.textContent = 'That QR is not a KK ID. Keep scanning or enter the value manually.';
                            }
                        }).catch(function () {
                            if (status) status.textContent = 'Could not read the QR code. Adjust the camera and try again.';
                        }).finally(function () {
                            scanning = false;
                        });
                    }, 300);
                });
            }).catch(function (error) {
                stopScanner(video);
                throw error;
            });
    }

    function stopScanner(video) {
        if (!video) return;
        if (video._kkScanner) {
            clearInterval(video._kkScanner);
            video._kkScanner = null;
        }
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(function (track) { track.stop(); });
            video.srcObject = null;
        }
    }

    function verifyScan(value) {
        var token = tokenFromValue(value);
        if (!token) return Promise.reject(new Error('Enter a valid KK ID QR value.'));
        return api('kk_id_scan', { value: 'KKID:' + token }).then(function (result) {
            return result.member;
        });
    }

    w.KKId = {
        api: api,
        getToken: getToken,
        makeCard: makeCard,
        printCard: printCard,
        recordPrint: recordPrint,
        renderCard: renderCard,
        startScanner: startScanner,
        stopScanner: stopScanner,
        tokenFromValue: tokenFromValue,
        verifyScan: verifyScan
    };
})(window);
