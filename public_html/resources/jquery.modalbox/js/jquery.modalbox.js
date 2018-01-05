(function($){
    $.modalbox = {
        defaults:{
            style:'default'
        }
    };
    $.fn.extend({
        modalbox:function(id,url){
            var config = $.extend({}, $.modalbox.defaults, config);
            var modalbox_id = "#"+id+".jquery-modalbox ";
            $.get(url,function(data){
                create_modalbox(data);
                position_modalbox();
                $(modalbox_id+'.close, '+modalbox_id+'.mask').click(function(){
                    $(modalbox_id).remove();
                })
            });
                        
            /*Metodos privados*/
            
            /*Carrega codigos html e os imprime na tela*/
            var create_modalbox = function(content){
                var close = '<div class="close"></div>'
                var html = '<div id="'+id+'" class="jquery-modalbox"><div class="dialog">'+close+'<div class="dialog-body">'+content+'</div></div><div class="mask"></div></div>';
                $('body').append(html);
            }
            
            var position_modalbox = function(){
                var maskHeight = $(document).height();
                var maskWidth = $(document).width();
                $(modalbox_id+'.mask').css({
                    'width':maskWidth,
                    'height':maskHeight
                });
                
                var winH = $(window).height();
                var winW = $(window).width();
                
                $(modalbox_id+'.dialog').fadeIn();
                $(modalbox_id+'.mask').fadeTo("slow",0.5);
                
                $(modalbox_id+'.dialog').css('top',  winH/2-$(modalbox_id+'.dialog').height()/2);
                $(modalbox_id+'.dialog').css('left', winW/2-$(modalbox_id+'.dialog').width()/2);
            //efeito de transição
            }
        }
    });
})(jQuery);