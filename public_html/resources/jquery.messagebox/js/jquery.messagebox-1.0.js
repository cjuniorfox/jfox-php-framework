/*Cria e gerencia janelas de mensagem para o sistema*/

;
(function ($, window, document, undefined) {
    
    $.fn.extend({
        messageBox:function(message,time){
            //armazena a largura e a altura da janela
            var winH = $(window).height();
            var winW = $(window).width();
            

            var box = $("<div></div>").
                addClass("JF-messageBox").
                add(this.selector).
                appendTo('body');
            
            $("<div></div>").
                html(message).
                appendTo(box);
            

            $('.JF-messageBox').css('top',  winH/2-$('.JF-messageBox').height()/2);
            $('.JF-messageBox').css('left', winW/2-$('.JF-messageBox').width()/2);
            $('.JF-messageBox').fadeIn();
            if(time){
               setTimeout(function(){
                   $().messageBox.close();
               },time);
            }

            this.messageBox.close = function(){
                $('.JF-messageBox').fadeOut(function(){
                    $('.JF-messageBox').remove();
                })
                
            }
        }
    });
})(jQuery, window, document);
