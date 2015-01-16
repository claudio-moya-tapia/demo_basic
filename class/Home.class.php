<?php

class Home {

    private $page;

    public function __construct() {
        $this->page = new Template();
        $this->page->readTemplateFromFile('./html/index.html');

        $this->create();
    }

    private function create() {
        $this->page->parse('httpBase', Config::create()->getHttpBase());

        $EasyWP = new EasyWP();
        //Top
        $Top = $EasyWP->getPostById(66);
        $this->page->parse('topText', $Top[0]->post_content);
        
        //Chat
        $Chat = $EasyWP->getPostById(71);
        list($chatTitulo,$chatHash) = explode('<!--more-->', $Chat[0]->post_content);
        $this->page->parse('chatTitulo', trim($chatTitulo));
        $this->page->parse('chatHash', trim($chatHash));
        
        //Posts
        $idUsuario = 1;

        $aryPosts = $EasyWP->getUltimosPosts();

        shuffle($aryPosts);
        
        foreach ($aryPosts as $Post) {

            $Category = $EasyWP->getCategoriesFromPostId($Post->ID);

            $categoryColor = Config::create()->getCategoryColor($Category[0]->categoryId);

            $Image = $EasyWP->getPostImages($Post->ID, 1);

            //Me gusta
            if ($EasyWP->hasMegusta($Post->ID, $idUsuario)) {
                $megusta = 'style="background-position: 0 -18px;"';
                $megustaJS = "Post.deleteMegusta('" . $Post->ID . "','" . $idUsuario . "','megustaId_" . $Post->ID . "')";
            } else {
                $megusta = '';
                $megustaJS = "Post.addMegusta('" . $Post->ID . "','" . $idUsuario . "','megustaId_" . $Post->ID . "')";
            }

            $this->page->parse('postUrl', Config::create()->getHttpBase() . '?page=articulo&id=' . $Post->ID);
            $this->page->parse('postImg', $Image[0]->imageUrl);
            $this->page->parse('postCategoryColor', $categoryColor);
            $this->page->parse('megustaJS', $megustaJS);
            $this->page->parse('megusta', $megusta);
            $this->page->parse('megustaId', $Post->ID);
            $this->page->addBlock('ultimosPosts');
        }
    }

    public function display() {
        $this->page->display();
    }

}

?>