<?php
class DepartementRepo{
     
    // database connection and table name
    private $conn;
    private $table_name = "departement";
     
    // object properties
    public $id;
    public $departementLib;
    public $reference;
    public $insertDate;
    public $insertAccountId;
    public $updateDate;
    public $updateAccountId;
     
    // constructor with $db as database connection
    public function __construct(){
        require_once '../include/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function readAll( $limit, $offset, $searchText) {

        $stmt = $this->conn->prepare("SELECT * FROM departement order by id ASC LIMIT ? OFFSET ?");

        if($this->getTableCount( 'departement' ) < 50){
            $limit = $this->getTableCount( 'departement' );
            $offset = 0;
        }
        $stmt->bind_param("ii", $limit, $offset);

        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["departementLib"] = $task["departementLib"];
            $tmp["reference"] = $task["reference"];
            $tmp["insertDate"] = $task["insertDate"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }

    /**
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function read() {

        $stmt = $this->conn->prepare("SELECT * FROM departement order by id");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["departementLib"] = $task["departementLib"];
            $tmp["reference"] = $task["reference"];
            $tmp["insertDate"] = $task["insertDate"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function readById($id) {
        $stmt = $this->conn->prepare("SELECT id, departementLib, reference, insertDate from departement WHERE id = ?");
        $stmt->bind_param( "i", $id );
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $departementLib, $reference, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["departementLib"] = $departementLib;
            $res["reference"] = $reference;
            $res["insertDate"] = $insertDate;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    // create product
    function create(){
         
        // query to insert record
        $query = "INSERT INTO ". $this->table_name ."(task) VALUES(?)";
         
        // prepare query
        $stmt = $this->conn->prepare($query);
     
        // posted values
        $this->name=htmlspecialchars(strip_tags($this->name));
        $this->price=htmlspecialchars(strip_tags($this->price));
        $this->description=htmlspecialchars(strip_tags($this->description));
        $this->created=htmlspecialchars(strip_tags($this->created));
     
        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":created", $this->created);
         
        // execute query
        if($stmt->execute()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getTableCount($tableName) {
        $stmt = $this->conn->prepare("SELECT Count(*) FROM ".$tableName);
        $stmt->execute();
        $stmt->bind_result( $total);
        // TODO
        // $task = $stmt->get_result()->fetch_assoc();
        $stmt->fetch();
        $stmt->close();
        return $total;
    }
}