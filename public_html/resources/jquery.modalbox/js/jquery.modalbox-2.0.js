/*
 *  Project: Jquery Modalbox
 *  Description: Carrega caixa modal em jQuery com conteúdo externo ao site
 *  Author: Carlos Júnior
 *  License: Livre
 */


;
(function ($, window, document, undefined) {

    $.extend({
        modalbox: function (id,url,close_event,ready_event) {
            var config = $.extend({}, $.modalbox.defaults, config);
            var modalbox_id = "#"+id+".jquery-modalbox ";
            $.get(url,function(data){
                create_modalbox(data);
                position_modalbox();
                if(typeof(ready_event) === 'function')
                    ready_event();
                $(modalbox_id+'.close, '+modalbox_id+'.mask').click(function(){
                    $(modalbox_id).remove();
                    if(typeof(close_event) === 'function')
                        close_event();
                }); 
            });
                        
            /*Metodos privados*/
            
            /*Carrega codigos html e os imprime na tela*/
            var create_modalbox = function(content){
                var close = '<div class="close"></div>';
                var html = '<div id="'+id+'" class="jquery-modalbox"><div class="dialog">'+close+'<div class="dialog-body">'+content+'</div></div><div class="mask">&nbsp;</div></div>';
                $('body').append(html);
            };
            
            var position_modalbox = function(){

                
                var winH = $(window).height();
                var winW = $(window).width();
                
                $(modalbox_id+'.dialog').fadeIn();
                $(modalbox_id+'.mask').fadeTo("slow",0.5);
                $(modalbox_id+'.dialog').css({
                    'max-height'    : (winH * 0.9) + "px",
                    'max-width'     : (winW * 0.9) + "px"
                });
                $(modalbox_id+'.dialog').
                css({
                    'top': winH/2-$(modalbox_id+'.dialog').height()/2,
                    'left': winW/2-$(modalbox_id+'.dialog').width()/2
                });
                $(modalbox_id+' .dialog-body').css({
                    'height' : ($(modalbox_id+'.dialog').height() - 25) + "px"
                });
            //efeito de transição
            };
        }
    });

})(jQuery, window, document);