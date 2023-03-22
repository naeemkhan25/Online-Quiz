(function($){
    $(document).on('click','.hdq_csv_download',function(e){
        e.preventDefault();
        var $this = $(this);
        var post_id = $this.siblings().val();
        var data = {
            action: 'hdq_download_csv',
            post_id:post_id,
            security:hdq_csv_download.nonce,
        };
        $.ajax({
            type: 'post',
            url: hdq_csv_download.ajax_url,
            data: data,
            beforeSend: function (response) {
            },
            complete: function (response) {
            },
            success: function (response) {
                window.location = response;
            },

        });
    });

})(jQuery);