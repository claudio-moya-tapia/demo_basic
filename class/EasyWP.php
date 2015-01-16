<?php

class EasyWP {

    private $dbHost;
    private $dbUser;
    private $dbPass;
    private $dbBase;
    private $conexion;

    public function __construct() {
        $this->dbHost = Config::create()->getDbHost();
        $this->dbUser = Config::create()->getDbUser();
        $this->dbPass = Config::create()->getDbPass();
        $this->dbBase = Config::create()->getDbBase();

        $conexion = mysql_connect($this->dbHost, $this->dbUser, $this->dbPass);
        $db_selected = mysql_select_db($this->dbBase, $conexion);
        mysql_set_charset('utf8', $conexion);

        $this->conexion = $conexion;
    }

    /**
     * Get post by id
     * @param int $id
     * @return stdClass
     */
    public function getPostById($id) {

        $sql = '
            SELECT
                *
            FROM
                wp_posts
            WHERE
                ID = "' . $id . '" AND
                post_status = "publish"
        ';

        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {

            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $ary[] = mysql_fetch_object($resulset);
            }
        }

        return $ary;
    }

    /**
     * Get all post from a categroy by the slug
     * @param string $slug
     * @return array 
     */
    public function getPostsByCategory($slug, $date = '%%', $limit = 0, $random = false) {

        $sql = '
            SELECT
                d.*
            FROM
                wp_terms a,
                wp_term_taxonomy b,
                wp_term_relationships c,
                wp_posts d
            WHERE
                a.name = "' . $slug . '" AND
                b.term_id = a.term_id AND
                c.term_taxonomy_id = b.term_taxonomy_id AND
                d.ID = c.object_id AND
                d.post_parent = "0" AND
                d.post_status = "publish" AND
				d.post_date LIKE "' . $date . '"
            ORDER BY
                post_date DESC
        ';

        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {

            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $ary[] = mysql_fetch_object($resulset);
            }
        }

        return $ary;
    }
    
    public function getPostsByCategoryId($id, $date = '%%', $limit = 0, $random = true) {

        $sql = '
            SELECT
                d.*
            FROM
                wp_terms a,
                wp_term_taxonomy b,
                wp_term_relationships c,
                wp_posts d
            WHERE
                a.term_id = "'.$id.'" AND
                b.term_id = a.term_id AND
                c.term_taxonomy_id = b.term_taxonomy_id AND
                d.ID = c.object_id AND
                d.post_parent = "0" AND
                d.post_status = "publish"
        ';
                
        if($random){
            $sql .= ' ORDER BY RAND()';
        }
        
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        
        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {

            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $ary[] = mysql_fetch_object($resulset);
            }
        }

        return $ary;
    }

    /**
     * Get all images from a post by post_parent id
     * @param string $slug
     * @return array 
     */
    public function getPostImages($post_parent, $limit = 0) {

        $sql = '
            SELECT 
                    post_name as imageName, guid imageUrl, post_content as imagenDesc
            FROM
                    wp_posts
            WHERE
                    post_parent = "' . $post_parent . '" AND
                    post_type = "attachment" AND
                    post_mime_type LIKE "image%"
            ORDER BY
                    ID DESC            
        ';

        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {

            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $Image = mysql_fetch_object($resulset);
                $tmp = explode('.jpg', $Image->imageUrl);
                $Image->imageTb = $tmp[0] . '-150x150.jpg';
                $ary[] = $Image;
            }
        }

        return $ary;
    }
    
    /**
     * Get all images from a post by post_parent id
     * @param string $slug
     * @return array 
     */
    public function getPostVideos($post_parent, $limit = 0) {

        $sql = '
            SELECT 
                    post_name as imageName, guid imageUrl, post_content as imagenDesc
            FROM
                    wp_posts
            WHERE
                    post_parent = "' . $post_parent . '" AND
                    post_type = "attachment" AND
                    post_mime_type LIKE "video%"
            ORDER BY
                    ID DESC            
        ';

        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {

            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $Video = mysql_fetch_object($resulset);               
                $ary[] = $Video;
            }
        }

        return $ary;
    }
    
    public function getCategoriesFromPostId($id){
        
        $sql = '
            SELECT 
                c.term_id as categoryId, c.name as categoryTitle,
                b.description as categoryDesc
            FROM
                wp_term_relationships a,
                wp_term_taxonomy b,
                wp_terms c
            WHERE
                a.object_id = "'.$id.'" AND
                b.term_taxonomy_id = a.term_taxonomy_id AND
                c.term_id = b.term_id AND
                c.term_id != 2
        ';

        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {

            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $ary[] = mysql_fetch_object($resulset);
            }
        }

        return $ary;                
    }
    
    public function addMegusta($idPost,$idUsuario){
        $response = 'ok';
        
        $sql = 'INSERT INTO post_megusta (wp_posts_id, id_usuario) VALUES ('.$idPost.', '.$idUsuario.');';
        
        $resulset = mysql_query($sql, $this->conexion);
        if(!$resulset) {
            $response = 'No se pudo ejecutar la consulta a la base de datos.';
        }
        
        echo $response;
    }
    
    public function deleteMegusta($idPost,$idUsuario){
        $response = 'ok';
        
        $sql = 'DELETE FROM post_megusta WHERE wp_posts_id = '.$idPost.' AND id_usuario = '.$idUsuario.';';
        
        $resulset = mysql_query($sql, $this->conexion);
        if(!$resulset) {
            $response = 'No se pudo ejecutar la consulta a la base de datos.';
        }
        
        echo $response;
    }
    
    public function hasMegusta($idPost, $idUsuario){
        
        $response = true;
        
        $sql = '
            SELECT 
                id_post_megusta
            FROM
                post_megusta
            WHERE
                wp_posts_id = "'.$idPost.'" AND
                id_usuario = "'.$idUsuario.'"
        ';

        $resulset = mysql_query($sql, $this->conexion);
        
        if (mysql_num_rows($resulset) > 0) {
            $response = true;
        }else{
            $response = false;
        }

        return $response;  
    }
    
    public function getComentarios($idPost){
                
        $sql = '
            SELECT a.*, b.nombre 
            FROM 
            post_comentario a,
            usuario b
            WHERE
            wp_posts_id = "'.$idPost.'" AND
            b.id_usuario = a.id_usuario
        ';

        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {
            
            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $ary[] = mysql_fetch_object($resulset);
            }
        }

        return $ary;  
    }
    
    public function addComentario($idPost,$idUsuario,$texto){
        
        $response = 'ok';
        
        $sql = 'INSERT INTO post_comentario (wp_posts_id, id_usuario, texto) VALUES ('.$idPost.', '.$idUsuario.', "'.$texto.'");';
        
        $resulset = mysql_query($sql, $this->conexion);
        if(!$resulset) {
            $response = 'No se pudo ejecutar la consulta a la base de datos.';
        }else{
            $response = mysql_insert_id();
        }
        
        echo $response;
    }
    
    public function getUltimosPosts(){
        
        $sql = '
        SELECT c.*
        FROM 
            wp_terms a,
            wp_term_relationships b,
            wp_posts c
        WHERE
            (
            a.term_id = 3 OR
            a.term_id = 4 OR
            a.term_id = 5 OR
            a.term_id = 6 OR
            a.term_id = 7 OR
            a.term_id = 8
            ) AND
            b.term_taxonomy_id = a.term_id AND
            c.ID = b.object_id AND
            c.post_status = "publish"
        ORDER BY 
            c.post_date DESC';
        
        $resulset = mysql_query($sql, $this->conexion);
        $ary = array();
        if (mysql_num_rows($resulset) > 0) {
            
            for ($i = 0; $i < mysql_num_rows($resulset); $i++) {
                $ary[] = mysql_fetch_object($resulset);
            }
        }

        return $ary;
    }
}
?>