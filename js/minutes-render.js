(function (w) {
    'use strict';

    var allowedTags = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'a'];
    var config = {
        ALLOWED_TAGS: allowedTags,
        ALLOWED_ATTR: ['href'],
        ALLOW_DATA_ATTR: false,
        ALLOW_ARIA_ATTR: false,
        ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):)/i
    };

    w.SKMinutes = {
        sanitize: function (html) {
            var template = document.createElement('template');
            template.innerHTML = w.DOMPurify.sanitize(String(html || ''), config);
            template.content.querySelectorAll('a').forEach(function (link) {
                link.setAttribute('rel', 'noopener noreferrer');
            });
            return template.innerHTML;
        }
    };
})(window);
