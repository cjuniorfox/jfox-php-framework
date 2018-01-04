if (typeof loadjscssfile !== 'function') {
    function loadjscssfile(filename, filetype) {
        if (filetype === "js") { //if filename is a external JavaScript file
            document.write("<script src='" + filename + "' type='text/javascript'></script>");
        }
        else if (filetype === "css") { //if filename is an external CSS file
            document.write("<link href='" + filename + "' rel='stylesheet' type='text/css' />");
        }

    }
}

if (typeof limit_characters !== 'function') {
    function limit_characters(string, length, extesion) {
        var newString = "";
        var i = 0;
        while (i < length && i < string.length) {
            newString = newString + string.charAt(i);
            i = i + 1;
        }
        if (i < string.length) {
            newString = newString + extesion;
        }
        return newString;
    }
}

if (typeof print_r !== 'function') {
    var print_r = function(obj, t) {

        // define tab spacing
        var tab = t || '';
        // check if it's array
        var isArr = Object.prototype.toString.call(obj) === '[object Array]' ? true : false;
        // use {} for object, [] for array
        var str = isArr ? ('Array\n' + tab + '[\n') : ('Object\n' + tab + '{\n');
        // walk through it's properties
        for (var prop in obj) {
            if (obj.hasOwnProperty(prop)) {
                var val1 = obj[prop];
                var val2 = '';
                var type = Object.prototype.toString.call(val1);
                switch (type) {

                    // recursive if object/array
                    case '[object Array]':
                    case '[object Object]':
                        val2 = print_r(val1, (tab + '\t'));
                        break;

                    case '[object String]':
                        val2 = '\'' + val1 + '\'';
                        break;

                    default:
                        val2 = val1;
                }
                str += tab + '\t' + prop + ' => ' + val2 + ',\n';
            }
        }
        // remove extra comma for last property
        str = str.substring(0, str.length - 2) + '\n' + tab;
        return isArr ? (str + ']') : (str + '}');
    };
}

if (typeof dump !== 'function') {
    var var_dump = print_r; // equivalent function
}

if (typeof getQueryParams !== 'function') {
    var getQueryParams = function(qs) {
        qs = qs.split("+").join(" ");

        var params = {}, tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;

        while (tokens = re.exec(qs)) {
            params[decodeURIComponent(tokens[1])]
                    = decodeURIComponent(tokens[2]);
        }

        return params;
    };
}
/**
 * Pega a url passada, extrai o get e retorna os valores do mesmo.
 * @param {string} url url a ser passada, por padrão é window.location.href
 * @return {array} array do get passado
 */
if (typeof getUrlVars !== 'function') {
    var getUrlVars = function(url) {
        if(!url || typeof(url)!== 'string')
            url = window.location.href;
        var vars = [], hash;
        var hashes = url.slice(url.indexOf('?') + 1).split('&');

        for (var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }

        return vars;
    };
}