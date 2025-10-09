jQuery(document).ready(function($) {
    // Tab switching
    $('.yts-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.yts-tab').removeClass('active');
        $(this).addClass('active');
        $('.yts-tab-content').removeClass('active');
        $('.yts-tab-content[data-tab="' + tab + '"]').addClass('active');
    });
});
