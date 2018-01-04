;
(function ($, window, document, undefined){
    $(document).ready(function(){
        /*Remove loading box a cada 15 seg*/
        setInterval(function(){
            $.messageBox.close("loading_appTema");
        },15000);

        /*Navegacao e historico*/
        $.changed = false;
        $.address.change(function(event){
            var carregando = "<img src='{SITE_PUBLIC_PATH}resources/templates/appTema/images/loading11.gif' alt='Carregando...' />";
            if(event.value && event.value!= "/"){
                $.messageBox("loading_appTema",carregando);
                $("#main_body").load("{SITE_PATH}"+event.value,function(){
                    $.messageBox.close("loading_appTema");
                    $.changed = true;
                    /*Retorna scroll para parte superior da janela e encerra processos de paginacao pageflow*/
                    $('html, body').animate({
                        scrollTop: $("body").offset().top
                    }, 1000);
                });
            }
        })
        /*Verifica se o usuario esta logado*/
        $.getJSON("{SITE_PATH}index.php/login/json_verificar_login?_=" + Math.floor(Math.random() * 1000),  function(json){
            if(json.login=="ok"){
                $("#main_menu").load("{SITE_PATH}_template/appTema/main_menu");
            }else{
                $("#main_body").load("{SITE_PATH}index.php/login");
            }
        });
    });
})(jQuery, window, document);