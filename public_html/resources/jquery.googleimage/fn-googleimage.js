;
(function($, window, document, undefined) {

        $.fn.googleImage = function() {
            return $(this).each(function() {
                var self = this;
                var url = "http://ajax.googleapis.com/ajax/services/search/images?v=1.0&rsz=2&start=1&callback=?&q=" + $(this).attr("alt");
                $.getJSON(url, function(data) {
                    if (data.responseData.results.length > 0)
                        $(self).attr("src", data.responseData.results[0].unescapedUrl);
                    else
                        $(self).attr("src", "");
                });
            });
        };
})(jQuery, window, document);