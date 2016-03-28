<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class SocieteRepo {

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
    public function getAllSocietes() {

        $stmt = $this->conn->prepare("SELECT * FROM societe");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["SocieteId"];
            $tmp["societeLib"] = $task["SocieteLib"];
            $tmp["contrats"]   = $this->getSocieteContrat($task["SocieteId"]);
            $tmp["typePatientSociete"]   = $this->getTypePatientBySocieteId($task["SocieteId"]);
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createSociete( $societeLib, $user_id ) {
        $stmt = $this->conn->prepare("INSERT INTO societe(SocieteLib, InsertENTUserAccountId, UpdateENTUserAccountId) VALUES(?,?,?)");
        $stmt->bind_param("sii", $societeLib, $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_societe_id = $this->conn->insert_id;
            return $new_societe_id;
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
    public function updateSociete( $societeId, $societeLib, $user_id ) {
        $stmt = $this->conn->prepare("UPDATE societe set SocieteLib = ?, UpdateENTUserAccountId = ? WHERE SocieteId = ?");
        $stmt->bind_param("sii", $societeLib, $user_id, $societeId);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function getAllTypeContrat() {

        $stmt = $this->conn->prepare("SELECT * FROM typecontrat");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]      = $task["TypeContratId"];
            $tmp["typeContratLib"] = $task["TypeContratLib"];
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
    public function getSocietes( $limit, $offset, $searchText) {

        $stmt = $this->conn->prepare("SELECT * FROM societe order by SocieteId ASC LIMIT ? OFFSET ?");

        if($this->getTableCount( 'societe' ) < 50){
            $limit = $this->getTableCount( 'societe' );
            $offset = 0;
        }
        $stmt->bind_param("ii", $limit, $offset);

        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["SocieteId"];
            $tmp["societeLib"] = $task["SocieteLib"];
            $tmp["contrats"]   = $this->getSocieteContrat($task["SocieteId"]);
            $tmp["typePatientSociete"]   = $this->getTypePatientBySocieteId($task["SocieteId"]);
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
    public function getSocieteContrat($societe_id) {

        $stmt = $this->conn->prepare("SELECT
        typecontrat.IsPacket,
        typecontrat.TypeContratLib,
        typecontrat.TypeContratId,
        contrat.ContratId
        FROM
        contrat
        INNER JOIN typecontrat ON typecontrat.TypeContratId = contrat.TypeContratId
        WHERE
        contrat.SocieteId = ?");
        $stmt->bind_param("s", $societe_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["isPacket"]       = $task["IsPacket"];
            $tmp["typeContratLib"] = $task["TypeContratLib"];
            $tmp["typeContratId"]  = $task["TypeContratId"];
            $tmp["id"]      = $task["ContratId"];
            array_push($response, $tmp);
        }
        $stmt->close();

        return $response;
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getSocieteById($societe_id) {
        $stmt = $this->conn->prepare("SELECT SocieteId, SocieteLib, InsertDate from societe WHERE SocieteId = ?");
        $stmt->bind_param("i", $societe_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($societeId, $societeLib, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $societeId;
            $res["societeLib"] = $societeLib;
            $res["insertDate"] = $insertDate;
            $stmt->close();

            $res["selectedTypeContratId"] = array();

            $typeContrats =$this->getSocieteContrat($societeId);
            foreach ($typeContrats as $typeContrat) {
                array_push($res["selectedTypeContratId"], $typeContrat['typeContratId']);
            }
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function getTypePatientBySocieteId($societe_id) {

        $stmt = $this->conn->prepare("SELECT
        typepatientsociete.TypePatientId,
        typepatientsociete.Id,
        typepatientsociete.SocieteId
        FROM
        typepatientsociete
        WHERE
        typepatientsociete.SocieteId = ?");
        $stmt->bind_param("i", $societe_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["Id"];
            $tmp["typePatientId"]  = $task["TypePatientId"];
            $tmp["societeId"] = $task["SocieteId"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
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
