/*Cria e gerencia janelas de mensagem para o sistema*/

;
(function($, window, document, undefined) {
    if (typeof $.messageBox !== 'function') {
        $.extend({
            messageBox: function(id, message, time) {
                var close = function(id) {
                    $('#' + id).fadeOut(function() {
                        $(this).remove();
                    });
                };
                //armazena a largura e a altura da janela
                var winH = $(window).height();
                var winW = $(window).width();


                var box = $("<div></div>").
                        addClass("JF-messageBox").
                        attr('id', id).
                        add(this.selector);
                if (!$("#" + id).length)
                    box.appendTo('body');
                $("<div></div>").
                        html(message).
                        appendTo(box);


                $('.JF-messageBox').css('top', winH / 2 - $('.JF-messageBox').height() / 2);
                $('.JF-messageBox').css('left', winW / 2 - $('.JF-messageBox').width() / 2);
                $('.JF-messageBox').fadeIn();
                if (time) {
                    setTimeout(function() {
                        close(id);
                    }, time);
                }

                this.messageBox.close = close;
            }
        });
        $.messagebox = $.messageBox;
    }
})(jQuery, window, document);
