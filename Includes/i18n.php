<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
if (isset($_GET['lang'])){
    if ($_GET['lang'] == 'en-us'){
        $_SESSION['idioma']= 'en-us';
    } elseif ($_GET['lang'] == 'hi-in') {
        $_SESSION['idioma']= 'hi-in';
    } elseif ($_GET['lang'] == 'pt-br') {
        $_SESSION['idioma']= 'pt-br';
    } elseif ($_GET['lang'] == 'zh-cn') {
        $_SESSION['idioma'] = 'zh-cn';
    } elseif($_GET['lang'] == 'es-es'){
        $_SESSION['idioma'] = 'es-es';
    }
    else{
        $_SESSION ['idioma'] = 'pt-br'; 
    }
} 

if   (!isset($_SESSION['idioma'])){
        $_SESSION['idioma'] = 'pt-br';
}

$lang = require_once(__DIR__ . '/../Languages/' . $_SESSION['idioma'] . '.php');

?>