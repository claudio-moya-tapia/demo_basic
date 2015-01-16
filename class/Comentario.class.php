<?php

class Comentario {

    private $response;

    public function __construct() {
        $idPost = $_POST['id_post'];
        $idUsuario = $_POST['id_usuario'];
        $texto = $_POST['comentario'];

        switch ($_POST['action']) {
            case 'add':

                $EasyWP = new EasyWP();
                $this->response = $EasyWP->addComentario($idPost, $idUsuario, $texto);

                break;
            default:
                break;
        }
    }

    public function display() {
        $idPost = $_POST['id_post'];        
        $url = Config::create()->getHttpBase().'?page=articulo&id='.$idPost.'#nuevo_comentario';
        
        echo '<script language="javascript" type="text/javascript">window.location.assign("'.$url.'");</script>';        
    }
}
?>