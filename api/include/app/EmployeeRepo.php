<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class EmployeeRepo {

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
    public function getAllDocProf() {

        $stmt = $this->conn->prepare("SELECT
        employee.Nom,
        employee.Prenom,
        employee.EmployeeId
        FROM
        employee
        INNER JOIN titre ON titre.TitreId = employee.TitreId AND 
        (employee.TitreId = 1 OR employee.TitreId = 2)");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["EmployeeId"];
            $tmp["fullname"]  = $task["Nom"] . ' ' . $task["Prenom"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getEmployeeNameById($employeeid) {
        $stmt = $this->conn->prepare("SELECT
        employee.Nom,
        employee.Prenom,
        employee.EmployeeId,
        titre.TauxReduction
        FROM
        employee
        INNER JOIN titre ON titre.TitreId = employee.TitreId
        WHERE
        employee.EmployeeId = ?");
        $stmt->bind_param("i", $employeeid);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($nom, $prenom, $employeeId, $taux);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["fullname"] = $nom . ' ' . $prenom;
            $res["taux"] = $taux;
            //$res["medecinFullName"] = $nom . ' ' . $prenom;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }


    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getEmployeeByAcctId($id) {
        $stmt = $this->conn->prepare("SELECT
        e.Nom,
        e.Prenom,
        u.username
        FROM
        users AS u
        INNER JOIN employee AS e ON e.EmployeeId = u.employeeid AND u.id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($nom, $prenom, $username);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["nom"]      = $nom;
            $res["prenom"]   = $prenom;
            $res["username"] = $username;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

}

?>
