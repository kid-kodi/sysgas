<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class AnalyseRepo {

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
    public function getAllAnalyses() {

        $stmt = $this->conn->prepare("SELECT * FROM analyse");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["analyseLib"] = $task["analyseLib"];
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
    public function readAll( $limit, $offset, $searchText) {

        $stmt = $this->conn->prepare("SELECT * FROM analyse order by id ASC LIMIT ? OFFSET ?");

        if($this->getTableCount( 'analyse' ) < 50){
            $limit = $this->getTableCount( 'analyse' );
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
            $tmp["laboratoireId"] = $task["laboratoireId"];
            $tmp["echantillonId"] = $task["echantillonId"];
            $tmp["analyseLib"] = $task["analyseLib"];
            $tmp["nbreB"] = $task["nbreB"];
            $tmp["nbreJourRetrait"] = $task["nbreJourRetrait"];
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
        $stmt = $this->conn->prepare("SELECT id, laboratoireId, echantillonId, analyseLib, nbreB, nbreJourRetrait, insertDate from analyse WHERE id = ?");
        $stmt->bind_param( "i", $id );
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["laboratoireId"] = $laboratoireId;
            $res["echantillonId"] = $echantillonId;
            $res["analyseLib"] = $analyseLib;
            $res["nbreB"] = $nbreB;
            $res["nbreJourRetrait"] = $nbreJourRetrait;
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
    public function create( $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $user_id ) {
        $stmt = $this->conn->prepare("INSERT INTO analyse(laboratoireId, echantillonId, analyseLib, nbreB, nbreJourRetrait,insertAccountId, updateAccountId) VALUES(?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss", $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_analyse_id = $this->conn->insert_id;
            return $new_analyse_id;
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
    public function update( $id, $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $user_id  ) {
        $stmt = $this->conn->prepare("UPDATE analyse set laboratoireId = ?, echantillonId = ?, analyseLib = ?, nbreB = ?, nbreJourRetrait = ?, updateAccountId = ? WHERE id = ?");
        $stmt->bind_param("sssssss", $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $user_id, $id );
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }


    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getAnalyseDetails($tcontratid, $analyseid, $employeeid) {
        $stmt = $this->conn->prepare("SELECT
        analyse.id,
        analyse.analyseLib,
        analyse.nbreJourRetrait,
        analyse.laboratoireId,
        prixanalyse.forfait,
        prixanalyse.nbreB,
        typecontrat.TypeContratId
        FROM
        analyse
        INNER JOIN prixanalyse ON prixanalyse.AnalyseId = analyse.id AND analyse.id = ?
        INNER JOIN typecontrat ON typecontrat.TypeContratId = prixanalyse.TypeContratId AND typecontrat.TypeContratId = ?");
        $stmt->bind_param("ii", $analyseid, $tcontratid);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($AnalyseId, $AnalyseLib, $NbreJourRetrait, $LaboratoireId, $Forfait, $NbreB, $TypeContratId);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["analyseId"]       = $AnalyseId;
            $res["analyseLib"]      = $AnalyseLib;
            $res["nbreJourRetrait"] = $NbreJourRetrait;
            $res["laboratoireId"]   = $LaboratoireId;
            $res["forfait"]         = $Forfait;
            $res["nbreB"]           = $NbreB;
            $res["typeContratId"]   = $TypeContratId;
            $res["medecinFullName"] = '';
            $res["tauxReduction"]   = 0;
            $res["netAPayer"]       = $Forfait;
            $res["dateRetrait"]     = date("Y-m-d", strtotime("+ ".$NbreJourRetrait." days"));

            $stmt->close();

            if( $employeeid != 0 ){
                $employee = $this->getEmployeeNameById($employeeid);
                $res["medecinFullName"] = $employee["fullname"];
                $res["tauxReduction"] = $employee["taux"];
                $res["netAPayer"]       = $this->computeNetAPayer($employeeid, $Forfait);
            }
            return $res;
        } else {
            return NULL;
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

?>
