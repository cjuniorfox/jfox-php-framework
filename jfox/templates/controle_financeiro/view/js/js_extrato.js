function contar_e_abrir_extrato(tempo){
    clearInterval($.timer);
    $.timer = setTimeout(function(){
        abrir_extratoNoForm();
    },tempo);
}
    
$(function(){
    $.load_url = "";
    abrir_extratoNoForm();
    setInterval(abrir_extratoNoForm(),5000);
})
$(fields).bind({
    change:function(){
        contar_e_abrir_extrato(1000);
    },
    blur:function(){
        contar_e_abrir_extrato(1000);
    },
    focus:function(){
        contar_e_abrir_extrato(5000);
            
    }
});