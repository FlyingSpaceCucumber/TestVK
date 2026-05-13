jQuery(document).ready(function($) {
    'use strict';

    const $generateBtn = $('#js-generate-link');
    const $copyBtn = $('#js-copy-link');
    const $linkInput = $('#doc-link-display');

    $generateBtn.on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $spinner = $btn.next('.spinner');

        $spinner.addClass('is-active');
        $btn.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'generate_doc_token',
            post_id: $btn.data('post-id'),
            _ajax_nonce: $('#doc_access_nonce').val()
        })
            .done(function(response) {
                if (response.success) {
                    $linkInput.val(response.data.url);
                    $copyBtn.prop('disabled', false);
                }
            })
            .always(function() {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
            });
    });

    $copyBtn.on('click', function() {
        const url = $linkInput.val();
        if (!url) return;

        navigator.clipboard.writeText(url).then(() => {
            const originalText = $copyBtn.text();
            $copyBtn.text('Copied!').addClass('button-primary');

            setTimeout(() => {
                $copyBtn.text(originalText).removeClass('button-primary');
            }, 2000);
        });
    });
});