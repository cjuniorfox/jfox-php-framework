;
(function ($, window, document, undefined) {
    $(document).ready(function () {
        if ($.controller === "") {
            $.controller = "index/tela_principal";
        }
        /*Aplicacao apenas visual*/
        $("#main_menu").fadeOut(200, function () {
            $("#login_window").fadeIn(200, function () {
                $("#login").focus();
            });
        });


        /*Cancela capacidade de postar formulario pelo metodo tradicional*/
        $("#login_window-form").submit(function () {
            return false;
        });

        $("#entrar").click(function () {
            envia_form();
        });

        function verificar_login() {
            $.getJSON("{SITE_PATH}index.php/login/json_verificar_login?_=" + Math.floor(Math.random() * 1000), function (json) {
                if (json.login === "ok") {
                    /*Aqui que se carrega os dados apos logar no sistema*/
                    $("#login_window").fadeOut(200, function () {
                        window.location.replace("{SITE_PATH}#tela_principal");
                        $("#main_menu").load("{SITE_PATH}_template/appTema/main_menu");
                    });

                } else {
                    $("#login_window-message").html("Login ou senha incorretos");
                    $("#login").val("");
                    $("#senha").val("");
                    $("#login").focus();
                }
            });
        }
        function envia_form() {
            // pegando os campos do formulário
            var login = $("#login").attr("value");
            var senha = $("#senha").attr("value");

            //Define os dados para serem postados
            $.ajax({
                type: "POST",
                url: "{SITE_PATH}index.php/login/post_login",
                dataType: "html",
                data: "login=" + login + "&senha=" + senha,
                // enviado com sucesso
                success: function () {
                    verificar_login();
                },
                // quando houver erro
                error: function () {
                    alert("Ocorreu um erro durante a requisição");
                }
            });
        }
    });
})(jQuery, window, document);