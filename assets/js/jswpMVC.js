(function($, exports, undefined){

    var jswpMVC = function() {
    
        this.getModel = function(prefix){
            if (typeof prefix === 'undefined'){
                console.log('jswpMVC.getModel called without prefix');
                return {};
            }
            var model = {};
            var propRegex = new RegExp(prefix+"\\[([^\\]]+)\\]");
            $('.'+prefix+'_control').each(function() {
                model[$(this).attr('name').match(propRegex)[1]] = $(this).val();
            });
            return model;
        };
        
        this.wrapControls = function(prefix, class_string){
            if (typeof prefix === 'undefined'){
                console.log('jswpMVC.wrapControls called without prefix');
                return {};
            }
            var classes = (typeof class_string === 'string')
                ? prefix+' '+string : prefix;
            $('.'+prefix+'_control').each(function() {
                $(this).wrap('<div class="'+classes+'_control_wrap" />');
            });
            return 1;
        };
        
        this.showErrors = function(prefix, errors, methods){
            
            var callbacks = methods || {};
            
            var controlsAreWrapped = $('.'+prefix+'_control_wrap').length > 0;
            
            var clearError = function($el){
                $el.css({'border-color': 'transparent'}).removeClass('cerror').tipTip('destroy');
            };
            
            var setError = function(error){
                if (!controlsAreWrapped) jswpMVC.wrapControls(prefix);
                $('#'+error.control).closest('.'+prefix+'_control_wrap')
                    .css({border: '1px solid red'}).click(function() {
                        $(this).css({'border-color': 'transparent'}).tipTip('destroy');
                    })
                    .addClass('cerror')
                    .tipTip({
                        content: error.errors.join('<br />')
                    });
            };
            
            $('.cerror').each(function() {
                var $el = $(this);
                if (typeof callbacks.clearError === 'function')
                    return callbacks.clearError($el);
                return clearError($el);
            });
            $.each(errors, function(k, v){
                if (typeof callbacks.setError === 'function')
                    return callbacks.setError(v);
                return setError(v);
            });
        };
    
    };
    
    
    exports.jswpMVC = new jswpMVC();
    
}(jQuery, window));