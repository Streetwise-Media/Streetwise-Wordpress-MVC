(function($, exports, undefined) {
    
    var showErrors = function(errors) {
        $.each(errors, function(k, v) {
            console.log(v);
            var errorsEl = $('<span style="color:red;">'+v.errors.join("<br />")+'</span>');
            errorsEl.insertAfter('#'+v.control)
            $('#'+v.control).click(function() { errorsEl.remove(); });
        });
    }
    
    $('document').ready(function() {
        $('#save_post').click(function() {
            var post = {};
            $('.new_post_control').each(function(){
                post[$(this).attr('name').replace('new_post[', '').replace(']', '')] = $(this).val();
            });
            $.post(window.location.href, {post: post}, function(r) {
                console.log(r);
                var res = $.parseJSON(r);
                if (res.errors) showErrors(res.errors);
            });
        });
    });
}(jQuery, window));