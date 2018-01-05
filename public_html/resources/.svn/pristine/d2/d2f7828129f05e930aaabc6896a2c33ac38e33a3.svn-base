function image_loader(target,filePath){
    var arrSelf = document.URL.split("/");
    arrSelf[arrSelf.length - 1] = "";
    var self = arrSelf.join("/");
    var file = self + filePath;
    /*Verifica o tipo de arquivo*/
    var type = file.substring(file.length-4,file.length);
    type = type.toLowerCase()
    if ((type == "jpeg") || (type == ".jpg") || (type == ".gif") || (type == ".bmp") || (type == ".png")) {
        var out = '<img src="' + filePath + '" />';
    }else{
        var out = "";
    }
    $(target).html(out);
}