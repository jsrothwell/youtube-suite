jQuery(document).ready(function($) {
    // Email signup
    $('.yts-email-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var email = $form.find('input[name="email"]').val();

        $.post(ytsData.ajaxUrl, {
            action: 'yts_email_signup',
            nonce: ytsData.nonce,
            email: email
        }, function(response) {
            if (response.success) {
                $form.find('.yts-message').html('<p style="color: green;">' + response.data.message + '</p>');
                $form[0].reset();
            } else {
                $form.find('.yts-message').html('<p style="color: red;">' + response.data.message + '</p>');
            }
        });
    });
});
