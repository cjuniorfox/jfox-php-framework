/*
 *  Project: albumMosaic
 *  Description: Gerencia albuns do tipo mosaico
 *  Author: Carlos Junior
 *  License:
 *  Proprietario
 */
(function($, window, document, undefined) {

    var pluginName = "albumMosaic",
            defaults = {
        lightweight: false,
        detailsBackground: '#ffea00',
        detailsColor: '#333',
        speed: 300,
        desc_speed: 100,
        margin: 0,
        background : "",
        thumbs: [{
                image: null,
                title: null,
                description: null,
                background: null,
                color: null,
                url: null,
                text: null,
                textColor: null
            }]
    };
    var delay = (function() {
        var timer = 0;
        return function(callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();
    function Plugin(element, options) {
        this.element = element;
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    Plugin.prototype = {
        init: function() {
            var self = this;
            delay(function() {
                self._initialize();
            }, this.options.speed);
        },
        _initialize: function() {
            this._create_html();
            //Se a versão do IE for anterior a 9, não faz preload (não funcionará).
            if ($.browser.version <= 8)
                this._process(this);
            else {
                this._preload_images(function(self) {
                    self._process(self);
                });
            }


        },
        _process: function(self, clean) {
            if (clean) {

                self._clean_block_images();
                this._create_html();
            }
            self.lc = self._count_lc();
            self._resize_block_images();
            self._description_turn_left();
            self._create_window_event();
            $(window).ready(function() {
                if (self.options.lightweight)
                    self._lightweight_animation(function() {
                        self._create_mouse_event(); 
                    });
                else
                    self._animate_images(function() {
                        self._create_mouse_event();
                    });
            });
        },
        _loading_information: function(value, total) {
            if (!$("#jq-albumMosaic-loading").length) {
                var html = "<div id='jq-albumMosaic-loading' style='display:none'>";
                html = html + "<div class='-jq-albumMosaic-progress_bar'>";
                html = html + "<hr class='-jq-albumMosaic-progress_bar-content' />";
                html = html + "</div>";
                $(html).hide().appendTo('body');
            }
            if (!$("#jq-albumMosaic-loading").is(":visible"))
                $("#jq-albumMosaic-loading").fadeIn(1000);
            this._center_element_screen($("#jq-albumMosaic-loading"));
            var bar = ((value+1) * 100) / total;
            if(bar > 100)
                bar = 100;
            $("#jq-albumMosaic-loading .-jq-albumMosaic-progress_bar-content").css({'width': bar + "%"});
        },
        _remove_loading_information: function() {
            $("#jq-albumMosaic-loading").stop().hide(function() {
                $(this).remove();
            });
        },
        _pre_load: function(id, total_itens, event) {
            var self = this;

            if (id < this.options.thumbs.length) {
                $('<img />').
                        attr('src', this.options.thumbs[id].image).load(function() {
                    id++;
                    self._loading_information(id, total_itens);
                    self._pre_load(id, total_itens, event);
                });
            } else {
                this._remove_loading_information();
                event(this);
            }
        },
        _preload_images: function(event) {
            var total_itens = this.options.thumbs.length;
            this._loading_information(1, total_itens);

            this._pre_load(0, total_itens, event);
        },
        _create_html: function() {
            var element = $('<ul></ul>').
                    addClass('-jq-albumMosaic-album_body');
            var thumbs = this.options.thumbs;
            for (id in thumbs) {
                var background = thumbs[id].background || this.options.detailsBackground;
                var color = thumbs[id].color || this.options.detailsColor;

                var html = ' <div class="-jq-albumMosaic-crop_image">';
                html = html + '     <a href="' + thumbs[id].url + '" class="-jq-albumMosaic-link">';
                html = html + '         <img class="-jq-albumMosaic-thumb" src="' + thumbs[id].image + '" alt="' + thumbs[id].title + '"/>';
                html = html + '         <div class="-jq-albumMosaic-text" style="color:' + thumbs[id].textColor + '"><div class="-jq-albumMosaic-text-inner">' + thumbs[id].text + '</div></div>';
                html = html + '     </a>';
                html = html + ' </div>';
                html = html + '<div class="-jq-albumMosaic-description_right" style="background: ' + background + ';">';
                html = html + '     <div class="-jq-albumMosaic-desc_box" style="color : ' + color + '">';
                html = html + '         <h1 class="-jq-albumMosaic-desc_title">' + thumbs[id].title + '</h1>';
                html = html + '         <p class="-jq-albumMosaic-desc_content">' + thumbs[id].description + '</p>';
                html = html + '     </div>';
                html = html + '</div>';
                //Adiciona o elemento já montado ao código HTML
                $("<li></li>").html(html).appendTo(element);
            }
            element.appendTo(this.element);
        },
        _create_mouse_event: function() {
            var self = this;
            $(".-jq-albumMosaic-crop_image").hover(
                    function() {
                        self.show_description($(this).parent());
                    }, function() {
                $(this).over = false;
                self.hide_description($(this).parent());
            }
            );
        },
        _create_window_event: function() {
            var self = this;
            $(window).resize(function() {
                delay(function() {
                    self._process(self, true);
                }, 300);
            });
        },
        //Esvazia todas as propriedades passadas nos blocos.
        _clean_block_images: function() {
            $("#jq-albumMosaic-loading").remove();
            $(this.element).find(".-jq-albumMosaic-album_body").remove();
        },

        /**
         * Redimensiona os blocos aonde as imagens ficarão instaladas
         */
        _resize_block_images: function() {
            //Carrega as variáveis
            var self = this;
            var bl_width = this._block_width();
            var bl_height = this._block_height();
            //redimensiona agora as imagens
            $(".-jq-albumMosaic-album_body").//Muda tamanho da fonte, define altura e largura de cada bloco e redimensiona as imagens
                    css('font-size', (bl_width / 22) + 'pt').
                    find("li").width(bl_width).height(bl_height).
                    find('.-jq-albumMosaic-text').width(bl_width).height(bl_height).parent().parent().
                    find('.-jq-albumMosaic-thumb').each(
                    function(i, el) {
                        if ($(this).width())
                            self._resize_image_loaded(el);
                        else
                            self._resize_image(el);
                    });
        },
        /**
         * Retorna a margin relativa para os elementos
         * @returns {@pro;margin@this.options}
         */
        _elements_margin: function() {
            var margin = ((($(this.element).width() * $(this.element).height()) / (1024*768)) * this.options.margin);
            if(margin < 0.5){
                margin = 0;
            }else if(margin > 0.5 && margin < 1){
                margin = 1;
            }
            return margin;
        },
        /**
         * Na hora de montar o mosaico, este posiciona o elemento verticalmente
         * @param element jquery_dom - Elemento li do mosaico
         * @returns Posição do elemento
         */
        _element_top: function(element) {
            var bh = this._block_height();
            var margin = this._elements_margin();
            var position = (Math.floor(element.index() / this.lc) * bh) + (Math.floor(element.index() / this.lc) * margin);
            return position + (margin/2);
        },
        /**
         * Na hora de montar o mosaico, este posiciona o elemento verticalmente
         * @param element jquery_dom - Elemento li do mosaico
         * @returns posição do elemento
         */
        _element_left: function(element) {
            var bw = this._block_width();
            var margin = this._elements_margin();
            var position = ((element.index() % this.lc) * bw) + ((element.index() % this.lc) * margin);
            return position + (margin/2);
        },
        _animate_images: function(event) {
            var self = this;
            var element = $(self.element).find('.-jq-albumMosaic-album_body');
            var bw = self._block_width();
            var i = 0;
            var li_element = '';
            var size = element.children().length;
            var bigger_delay = 0; //Este recebe a soma de todos os delays
            //Adiciona a quantidade de itens a um array com id de cada um
            var ids = new Array();
            var rand = 0;
            for (i = 0; i <= size; i++) {
                ids[ids.length] = i;
            }
            //Agora vai processando e removendo os itens até acabar o array
            while (ids.length) {
                rand = Math.floor((Math.random() * ids.length));
                //rand = 0;
                li_element = element.children().eq(ids[rand]);
                var top = self._element_top(li_element);
                var left = self._element_left(li_element);
                self._center_element_screen(li_element);
                var delay_time = self.options.speed * Math.floor((Math.random() * size)) / 15;
                li_element.delay(delay_time).show().animate({top: top + 'px', left: left + 'px'}, self.options.speed);
                ids.splice(rand, 1);
                if (delay_time > bigger_delay)
                    bigger_delay = delay_time;
            }
            setTimeout(function() {
                element.css("background",self.options.background);
                event();
            }, bigger_delay + self.options.speed);
        },
        //Faz o memso que animate_images, porem de uma forma mais leve
        _lightweight_animation: function(event) {
            var self = this;
            var element = $(this.element).find('.-jq-albumMosaic-album_body');
            var bw = this._block_width();
            var bh = this._block_height();
            element.children().each(function() {
                var top = self._element_top($(this));
                var left = self._element_left($(this));
                $(this).css({
                    position: "absolute",
                    top: top + 'px',
                    left: left + 'px',
                    display: 'list-item'
                });
                element.css("background",self.options.background);
            });
            event();
        },
        //Calcula a quantidade de linhas e colunas necessarias para o album
        _count_lc: function() {
            var lc = $(".-jq-albumMosaic-album_body li").size();
            return Math.sqrt(lc);
        },
        //Calcula o tamanho em largura do bloco de imagens
        _block_width: function() {
            var total_width = $(this.element).find('.-jq-albumMosaic-album_body').width();
            return (total_width / this.lc) - (this._elements_margin());
        },
        //Calcula o tamanho em altura do bloco de imagens
        _block_height: function() {
            var total_height = $(".-jq-albumMosaic-album_body").height();
            return (total_height / this.lc) - (this._elements_margin());
        },
        //Transforma as descrições que estão na outra metade da tela de direita para esquerda
        _description_turn_left: function() {
            var self = this;
            $(".-jq-albumMosaic-album_body li").each(function(i) {
                if (((i + 1) % self.lc) > (self.lc / 2) || !((i + 1) % self.lc)) {
                    $(this).find('.-jq-albumMosaic-description_right').addClass('-jq-albumMosaic-description_left');
                }
            });
        },
        //Carrega e redimensiona imagem
        _resize_image: function(el) {
            var self = this;
            var ni = new Image();
            ni.onload = function() {
                self._resize_image_loaded($(el));
            };
            ni.src = $(el).attr('src');
        },
        //Redimensiona imagem miniatura já pre-carregada
        _resize_image_loaded: function(el) {
            var cur_w = el.width;
            var cur_h = el.height;
            var bl_w = this._block_width();
            var bl_h = this._block_height();
            //Calcula pela proporção de aspecto
            var ratio = 0;
            var img_ar = cur_w / cur_h;
            var bl_ar = bl_w / bl_h;
            ratio = img_ar <= bl_ar ? (bl_w / cur_w) : (bl_h / cur_h);
            //redimensiona os elementos
            $(el).width(cur_w * ratio).
                    height(cur_h * ratio);
            this._center_image(el);
        },
        //Centraliza imagem no bloco
        _center_image: function(el) {
            $(el).css({
                "position": "absolute",
                "top": (($(".-jq-albumMosaic-album_body li").height() / 2) - ($(el).height() / 2)) + "px",
                "left": (($(".-jq-albumMosaic-album_body li").width() / 2) - ($(el).width() / 2)) + "px"
            });
        },
        show_description: function(el) {
            var v = parseInt(el.parent().width() / 110);
            var h = parseInt(el.parent().height() / 110);
            var s = parseInt((v > h ? h : v)) + "px"; //tamanho da margem
            //Se não existir description, não mostra descrição
            var banner_width = "200%";
            if (!el.find(".-jq-albumMosaic-desc_title, .-jq-albumMosaic-desc_content").html())
                banner_width = "100%";
            this._stop_animation(el);

            var speed = this.options.desc_speed;
            el.css('z-index', this._count_lc() + 1).find('.-jq-albumMosaic-description_right').
                    animate({width: banner_width, display: 'block'}, speed, function() {
                $(this).children().fadeIn();
            }).
                    parent().find('.-jq-albumMosaic-crop_image').
                    css("z-index", 2).
                    animate({top: s, left: s, bottom: s, right: s}, speed).
                    parent().next().css("z-index", "-2");
            el.find(".-jq-albumMosaic-thumb, .-jq-albumMosaic-text").animate({'margin-top': "-" + s, 'margin-left': "-" + s}, speed);
        },
        hide_description: function(el) {
            var speed = 200;
            this._stop_animation(el);
            el.find('.-jq-albumMosaic-description_right').
                    animate({width: '0%', display: 'none'}, speed, function() {
                $(this).children().hide();
                el.css('z-index', '');
            }).
                    parent().find('.-jq-albumMosaic-crop_image').
                    css("z-index", "1").
                    animate({top: 0, left: 0, bottom: 0, right: 0}, speed).
                    parent().next().css("z-index", "");
            el.find(".-jq-albumMosaic-thumb, .-jq-albumMosaic-text").animate({'margin-top': "0", 'margin-left': "0"}, speed);
        },
        _stop_animation: function(el) {

            el.stop().
                    find('.-jq-albumMosaic-description_right').stop().
                    children().stop().
                    parent().parent().find('.-jq-albumMosaic-crop_image').stop().
                    parent().next().stop();
            el.find(".-jq-albumMosaic-thumb, .-jq-albumMosaic-text").stop();
        },
        //Centraliza elemento na tela
        _center_element_screen: function(el) {
            $(el).css({
                "position": "absolute",
                "top": (($(window).height() / 2) - ($(el).height() / 2)) + "px",
                "left": (($(window).width() / 2) - ($(el).width() / 2)) + "px"
            });
        }
    };
    $.fn[pluginName] = function(options) {
        return this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin(this, options));
            }
        });
    };
})(jQuery, window, document);