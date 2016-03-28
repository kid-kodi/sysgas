<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class MonthRepo {

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
    public function getAllMonths() {

        $stmt = $this->conn->prepare("SELECT * FROM mois");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["moisId"] = $task["MoisId"];
            $tmp["moisLib"] = $task["MoisLib"];
            $tmp["moisNumber"] = $task["MoisNumber"];
            array_push($response, $tmp);
        }
        $stmt->close();

        return $response;
    }

}

?>
