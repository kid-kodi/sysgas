<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class LaboratoireRepo {

    // database connection and table name
    private $conn;
    private $table_name = "laboratoire";
     
    // object properties
    public $id;
    public $laboratoireLib;
    public $description;
    public $price;
    public $created;
     
    // constructor with $db as database connection
    function __construct() {
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

        $stmt = $this->conn->prepare("SELECT * FROM laboratoire order by id ASC LIMIT ? OFFSET ?");

        if($this->getTableCount( 'laboratoire' ) < 50){
            $limit = $this->getTableCount( 'laboratoire' );
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
            $tmp["laboratoireLib"]   = $task["laboratoireLib"];
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

        $stmt = $this->conn->prepare("SELECT * FROM laboratoire order by id");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["laboratoireLib"]   = $task["laboratoireLib"];
            $tmp["uniteId"]   = $task["uniteId"];
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
        $stmt = $this->conn->prepare("SELECT id, laboratoireLib, uniteId, insertDate from laboratoire WHERE id = ?");
        $stmt->bind_param( "i", $id );
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $laboratoireLib, $uniteId, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["laboratoireLib"] = $laboratoireLib;
            $res["uniteId"] = $uniteId;
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
    public function create( $laboratoireLib, $uniteId, $user_id ) {
        $stmt = $this->conn->prepare("INSERT INTO laboratoire(laboratoireLib, uniteId, insertAccountId, updateAccountId) VALUES(?,?,?,?)");
        $stmt->bind_param("siii", $laboratoireLib, $uniteId, $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_laboratoire_id = $this->conn->insert_id;
            return $new_laboratoire_id;
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function update( $id, $laboratoireLib, $uniteId, $user_id ) {
        $stmt = $this->conn->prepare("UPDATE laboratoire set laboratoireLib = ?, uniteId = ?, updateAccountId = ? WHERE id = ?");
        $stmt->bind_param("siii", $laboratoireLib, $uniteId, $user_id, $id );
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
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

?>
