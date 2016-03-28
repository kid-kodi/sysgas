<?php
class UniteRepo{
     
    // database connection and table name
    private $conn;
    private $table_name = "unite";
     
    // object properties
    public $id;
    public $uniteLib;
    public $description;
    public $price;
    public $created;
     
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }
}