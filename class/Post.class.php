<?php

class Post {

    private $page;
    
    public function __construct() {
        $this->page = new Template();
        $this->page->readTemplateFromFile('./html/post.html');

        $this->create();
    }

    private function create() {
        $this->page->parse('httpBase', Config::create()->getHttpBase());
        
        $idPost = $_GET['id'];
        $idUsuario = 1; //FAKE USER DEMO MODE
        
        //Post
        $EasyWP = new EasyWP();
        $Post = $EasyWP->getPostById($idPost);
        $Image = $EasyWP->getPostImages($idPost, 1);
        $Video = $EasyWP->getPostVideos($idPost, 1);
        $Category = $EasyWP->getCategoriesFromPostId($idPost);
        $categoryImg = Config::create()->getCategoryLogo($Category[0]->categoryId);
        
        $this->page->parse('idPost', $idPost);
        $this->page->parse('idUsuario', $idUsuario);
        $this->page->parse('titulo', $Post[0]->post_title);
        $this->page->parse('texto', $Post[0]->post_content);        
        $this->page->parse('categoryTitle', $Category[0]->categoryTitle);
        $this->page->parse('categoryDesc', $Category[0]->categoryDesc);
        $this->page->parse('categoryImg', $categoryImg);
        
        if(count($Video) > 0){
            $this->page->parse('video', $Video[0]->imageUrl);
            $this->page->addBlock('bloqueVideo');
        }else{
            $this->page->parse('image', $Image[0]->imageUrl);
            $this->page->addBlock('bloqueImagen');
        }
        
        //Me gusta
        if($EasyWP->hasMegusta($idPost, $idUsuario)){
            $megusta = 'style="background-position: 0 -18px;"';
            $megustaJS = "Post.deleteMegusta('".$idPost."','".$idUsuario."', this)";
        }else{
            $megusta = '';
            $megustaJS = "Post.addMegusta('".$idPost."','".$idUsuario."', this)";
        }
        
        $this->page->parse('megusta', $megusta);
        $this->page->parse('megustaJS', $megustaJS);
        
        //Relacionados
        $aryPost = $EasyWP->getPostsByCategoryId($Category[0]->categoryId, '%%', 12, true);
                
        foreach ($aryPost as $post){
            
            $Image = $EasyWP->getPostImages($post->ID, 1);
            
            $this->page->parse('relUrl',  Config::create()->getHttpBase().'?page=articulo&id='.$post->ID);
            $this->page->parse('relImg',$Image[0]->imageTb);
            $this->page->addBlock('postRelacionados');
        }
        
        //Comentarios
        $this->page->parse('formComentarios', Config::create()->getHttpBase().'?page=comentario');
        
        $aryComentarios = $EasyWP->getComentarios($idPost);
        
        foreach($aryComentarios as $Comentario){                        
            $this->page->parse('comentarioTexto', $Comentario->texto);
            $this->page->addBlock('comentarios');
        }
    }

    public function display() {
        $this->page->display();
    }

}

?>
