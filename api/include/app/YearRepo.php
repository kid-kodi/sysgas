<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class YearRepo {

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
    public function getAllYears() {

        $stmt = $this->conn->prepare("SELECT * FROM annee");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["anneeId"]  = $task["AnneeId"];
            $tmp["anneeLib"] = $task["AnneeLib"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }

}

?>
