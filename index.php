<?php
require_once './class/Config.class.php';
require_once './class/EasyWP.php';
require_once './class/Template.class.php';
require_once './class/Home.class.php';
require_once './class/Post.class.php';
require_once './class/MeGusta.class.php';
require_once './class/Comentario.class.php';

/**
* Page selector
*/
switch ($_GET['page']) {    
    case 'articulo':
        $Post = new Post();
        $Post->display();
        break;
    case 'megusta':
        $MeGusta = new MeGusta();   
        $MeGusta->display();
        break;
    case 'comentario':
        $Comentario = new Comentario();   
        $Comentario->display();
        break;
    default:
        $Home = new Home();
        $Home->display();
        break;
}
?>