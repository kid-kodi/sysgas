<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DayRepo {

    private $conn;

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
    public function getAllDay() {
        
        $stmt = $this->conn->prepare("SELECT * FROM jour");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["jourId"] = $task["JourId"];
            $tmp["jourNumber"] = $task["JourNumber"];
            array_push($response, $tmp);
        }

        $stmt->close();

        return $response;
    }

}

?>
