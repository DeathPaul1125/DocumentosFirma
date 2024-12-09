function CopiarEnlace( code ) 
{
    // Nos traemos la url del navegador
    var url = window.location.href;
    console.log(url)
    // Le quitamos lo que hay tras el ultimo barra
    url = url.substring(0, url.lastIndexOf("/"));
    console.log(url)
    // Le añadimos el codigo del documento
    url = url + "/" + code;
    console.log(url)
    // Lo copiamos al portapapeles
    navigator.clipboard.writeText(url);
    // Mostramos un mensaje de que se ha copiado
    // alert("Se ha copiado el enlace al portapapeles");



}