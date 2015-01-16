<?php
class MeGusta {
    
    private $response;
    
    public function __construct() {
        $idPost = $_GET['id'];
        $idUsuario = $_GET['id_usuario'];
                
        switch ($_GET['action']) {
            case 'add':
                
                $EasyWP = new EasyWP();
                $this->response = $EasyWP->addMegusta($idPost, $idUsuario);
                
                break;
            case 'delete':
                
                $EasyWP = new EasyWP();
                $this->response =  $EasyWP->deleteMegusta($idPost, $idUsuario);
                
                break;
            default:
                break;
        }
    }    
    
    public function display(){
        echo $this->response;
    }    
}
?>