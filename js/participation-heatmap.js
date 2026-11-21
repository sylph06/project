(function (global) {
    'use strict';

    function isValidIsoDate(value) {
        var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return false;
        var date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
        return date.getUTCFullYear() === Number(match[1]) &&
            date.getUTCMonth() === Number(match[2]) - 1 &&
            date.getUTCDate() === Number(match[3]);
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
        });
    }

    function render(container, year, dates, calendarLabel) {
        year = Number(year);
        if (!container || !Number.isInteger(year) || !Array.isArray(dates)) {
            throw new TypeError('A heatmap container, year, and date list are required.');
        }

        var start = Date.UTC(year, 0, 1);
        var dayCount = (Date.UTC(year + 1, 0, 1) - start) / 86400000;
        var firstWeekday = new Date(start).getUTCDay();
        var counts = Object.create(null);
        dates.forEach(function (value) {
            var date = String(value || '');
            if (!isValidIsoDate(date) || date.slice(0, 4) !== String(year)) return;
            counts[date] = (counts[date] || 0) + 1;
        });

        var monthFormatter = new Intl.DateTimeFormat(undefined, { month: 'short', timeZone: 'UTC' });
        var monthLabels = [];
        var daysBeforeMonth = 0;
        for (var month = 0; month < 12; month++) {
            var monthDate = new Date(Date.UTC(year, month, 1));
            var weekColumn = Math.floor((firstWeekday + daysBeforeMonth) / 7) + 1;
            monthLabels.push('<span style="grid-column:' + weekColumn + ' / span 4">' +
                escapeHtml(monthFormatter.format(monthDate)) + '</span>');
            daysBeforeMonth += new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
        }

        var weekdayLabels = ['', 'Mon', '', 'Wed', '', 'Fri', ''].map(function (label, index) {
            return '<span style="grid-row:' + (index + 1) + '">' + label + '</span>';
        }).join('');
        var maxCount = Object.keys(counts).reduce(function (maximum, date) {
            return Math.max(maximum, counts[date]);
        }, 0);
        var dayCells = [];
        for (var dayIndex = 0; dayIndex < dayCount; dayIndex++) {
            var dateValue = new Date(start + dayIndex * 86400000);
            var dateText = dateValue.toISOString().slice(0, 10);
            var count = counts[dateText] || 0;
            var level = count && maxCount ? Math.ceil(count / maxCount * 4) : 0;
            var week = Math.floor((firstWeekday + dayIndex) / 7) + 1;
            var weekday = dateValue.getUTCDay() + 1;
            var label = dateText + ': ' + count + ' confirmed attendance record' + (count === 1 ? '' : 's');
            dayCells.push('<span class="heatmap-day" data-level="' + level +
                '" style="grid-column:' + week + ';grid-row:' + weekday +
                '" role="gridcell" aria-label="' + escapeHtml(label) +
                '" title="' + escapeHtml(label) + '"></span>');
        }

        container.innerHTML =
            '<div class="heatmap-calendar" role="grid" aria-label="' + escapeHtml(calendarLabel) + '">' +
                '<div class="heatmap-months" aria-hidden="true">' + monthLabels.join('') + '</div>' +
                '<div class="heatmap-weekdays" aria-hidden="true">' + weekdayLabels + '</div>' +
                '<div class="heatmap-days" role="rowgroup">' + dayCells.join('') + '</div>' +
            '</div>';
        return Object.keys(counts).reduce(function (sum, date) { return sum + counts[date]; }, 0);
    }

    global.KKParticipationHeatmap = { render: render };
})(window);
