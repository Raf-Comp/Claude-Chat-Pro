jQuery(document).ready(function($) {
    // Obsługa pokazywania/ukrywania haseł
    $('.toggle-password').on('click', function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Inicjalizacja highlight.js
    if (typeof hljs !== 'undefined') {
        hljs.highlightAll();
    }
});