<?php
class Config {
    
    private static $singleton;
    private $dbHost;
    private $dbBase;
    private $dbUser;
    private $dbPass;
    private $httpBase;
    
    public static function create() {

        if (is_null(self::$singleton)) {
            self::$singleton = new Config();
        }
        return self::$singleton;
    }
    
    public function __construct() {
        $this->dbHost = 'localhost';
        $this->dbUser = 'xxxxxx';
        $this->dbPass = 'xxxxxx';
        $this->dbBase = 'xxxxxx';
        $this->httpBase = 'http://dev.rayalab.cl/consalud_somosmas/';
    }    
    
    public function getDbHost(){
        return $this->dbHost;
    }
    
    public function getDbBase(){
        return $this->dbBase;
    }
    
    public function getDbUser(){
        return $this->dbUser;
    }
    
    public function getDbPass(){
        return $this->dbPass;
    }
    
    public function getHttpBase(){
        return $this->httpBase;
    }     
    
    public function getCategoryLogo($id){
        
        switch ($id) {
            case '3':
                $catLogo = $this->httpBase.'img/cate01.png';
                break;
            case '4':
                $catLogo = $this->httpBase.'img/cate02.png';
                break;
            case '5':
                $catLogo = $this->httpBase.'img/cate03.png';
                break;
            case '6':
                $catLogo = $this->httpBase.'img/cate04.png';
                break;
            case '7':
                $catLogo = $this->httpBase.'img/cate05.png';
                break;
            case '8':
                $catLogo = $this->httpBase.'img/cate06.png';
                break;
            default:
                break;
        }
        
        return $catLogo;
        
    }          
    
    public function getCategoryColor($id){
        
        switch ($id) {
            case '3':
                $catColor = 'cate01';
                break;
            case '4':
                $catColor = 'cate02';
                break;
            case '5':
                $catColor = 'cate03';
                break;
            case '6':
                $catColor = 'cate04';
                break;
            case '7':
                $catColor = 'cate05';
                break;
            case '8':
                $catColor = 'cate06';
                break;
            default:
                break;
        }
        
        return $catColor;
    }    
}
?>
