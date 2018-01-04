;
(function ($, window, document, undefined){
    
    if(!$.listagem_acoes){
        $.extend({
            listagem_acoes : {
                delete_box : function(controller_name, key){
                    $.modalbox('delete_box','{SITE_PATH}'+ controller_name +'/delete_box?id=' + key);
                },
                remove_from_list : function(element){
                    $(element).slideUp();
                    $(element).next().slideUp();
                }
            }
        });
    }
    

    $(document).ready(function(){
        var null_padrao = 'Digite sua busca';
        $("#lista").accordion({
            active: false, 
            collapsible: true, 
            header: "h4", 
            autoHeight: false, 
            clearStyle: true,
            create: function(event){
                var focus_item = '{focus_item}';
                var item = "#item_link_" + focus_item;
                if(focus_item && $(item).length){
                    $(item).click();
                    $('#relatorio').parent().animate({
                        scrollTop: $(item).offset().top - 60
                    }, 500);
                }
            }
        });
        
        $(".lista_acoes a").mouseover(function(){
            var msg = $(this).find("img").attr('alt');
            $(this).parent().find(".lista_acoes-descricao").html(msg);
        });
        
        $(".lista_acoes").find("img").mouseout(function(){
            $(this).parent().parent().find(".lista_acoes-descricao").html("");
        });
        
        $(".listar_body").click(function(event){
            $('#listagem-body').html('<div class="update_box"><div class="loading_fullscreen">&nbsp;</div></div>');
            event.preventDefault();
            var href = this.href;
            $("#listagem-body").load(href);
        })
        
        $("#relatorio_pesq").submit(function(event){
            event.preventDefault();

            $("#searchbox").addClass('searching');
            
            if($("#searchbox").val() == null_padrao)
                $("#listagem-left").load($(this).attr('action'));
            else
                $("#listagem-left").load($(this).attr('action')+"?"+$(this).serialize());
        });
        
        $("#searchbox").click(function(){
            if($("#searchbox").val() == null_padrao){
                $("#searchbox").val("");
            }
        });
        $("#searchbox").blur(function(){
            if($("#searchbox").val() == ""){
                $("#searchbox").val(null_padrao);
            }
        });
        
    });

})(jQuery, window, document);