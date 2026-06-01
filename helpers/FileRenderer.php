<?php

// Lo dejamos con fin didáctico. No se utiliza.
class FileRenderer
{
    public function __construct()
    {
    }

    public function render($viewName,$resultado = array()){
        include_once("view/header.mustache");
        include_once("view/". $viewName. "View.php");
        include_once("view/footer.mustache");
    }
}