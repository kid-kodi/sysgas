<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class CommandeAnalyseRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        require_once 'EmployeeRepo.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**/

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function create($commande_id, $analyse, $user_id) {

        //print_r($analyse);

        $stmt = $this->conn->prepare("INSERT INTO commandeanalyse (analyseId, netAPayer, medecinId, dateDeRetait, commandeId, forfait, tauxReduction, nbreB, insertAccountId, updateAccountId) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssss", $analyse['analyseId'], $analyse['netAPayer'], $analyse['medecinId'], $analyse['dateRetrait'], $commande_id, $analyse['forfait'],  $analyse['tauxReduction'], $analyse['nbreB'], $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_commande_id = $this->conn->insert_id;

            return $new_commande_id;
        } else {
            // task failed to create
            return NULL;
        }
    }


    public function update(){}

    public function delete( $id ){
        $stmt = $this->conn->prepare("DELETE FROM commandeanalyse WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    public function readOne( $id ){
        $stmt = $this->conn->prepare("SELECT
        ca.id,
        ca.commandeId,
        ca.analyseId,
        an.analyseLib,
        ca.netAPayer,
        ca.medecinId,
        ca.dateDeRetait,
        ca.insertDate,
        ca.forfait,
        ca.tauxReduction,
        ca.nbreB,
        em.Nom,
        em.Prenom,
        co.TypeContratId
        FROM
        commandeanalyse AS ca
        INNER JOIN analyse AS an ON an.id = ca.analyseId
        LEFT OUTER JOIN employee AS em ON em.EmployeeId = ca.medecinId
        INNER JOIN commande AS com ON com.Id = ca.commandeId
        INNER JOIN contrat AS co ON co.ContratId = com.ContratId
        WHERE
        ca.id = ?
        ");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $commandeId, $analyseId, $analyseLib, $netAPayer, $medecinId, $dateDeRetait, $insertDate, $forfait, $tauxReduction, $nbreB, $nom, $prenom, $typeContratId);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"]  = $id;
            $res["medecinFullname"] = $nom . ' ' . $prenom;
            $res["commandeId"] = $commandeId;
            $res["typeContratId"] = $analyseId;
            $res["analyseId"] = $analyseId;
            $res["analyseLib"] = $analyseLib;
            $res["netAPayer"] = $netAPayer;
            $res["dateDeRetait"] = $dateDeRetait;
            $res["insertDate"] = $insertDate;
            $res["medecinId"] = $medecinId;
            $res["forfait"] = $forfait;
            $res["tauxReduction"] = $tauxReduction;
            $res["nbreB"] = $nbreB;
            //$res["medecinFullName"] = $nom . ' ' . $prenom;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }


        /*$stmt->execute();
        $tasks = $stmt->get_result();
        $response  = array();


        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["commandeId"] = $task["commandeId"];
            $tmp["typeContratId"] = $task["TypeContratId"];
            $tmp["analyseId"] = $task["analyseId"];
            $tmp["analyseLib"] = $task["analyseLib"];
            $tmp["netAPayer"] = $task["netAPayer"];
            $tmp["dateDeRetait"] = $task["dateDeRetait"];
            $tmp["insertDate"] = $task["insertDate"];
            $tmp["medecinId"] = $task["medecinId"];
            $tmp["medecinFullname"] = $task["Nom"] . " " . $task["Prenom"];
            $tmp["forfait"] = $task["forfait"];
            $tmp["tauxReduction"] = $task["tauxReduction"];
            $tmp["nbreB"] = $task["nbreB"];
            array_push($response, $tmp);
        }
        $stmt->close();*/
    }

    public function readByCommandeId( $commande_id ){
        $stmt = $this->conn->prepare("SELECT
        ca.id,
        ca.commandeId,
        ca.analyseId,
        an.analyseLib,
        ca.netAPayer,
        ca.medecinId,
        ca.dateDeRetait,
        ca.insertDate,
        ca.forfait,
        ca.tauxReduction,
        ca.nbreB,
        em.Nom,
        em.Prenom,
        co.TypeContratId
        FROM
        commandeanalyse AS ca
        INNER JOIN analyse AS an ON an.id = ca.analyseId
        LEFT OUTER JOIN employee AS em ON em.EmployeeId = ca.medecinId
        INNER JOIN commande AS com ON com.Id = ca.commandeId
        INNER JOIN contrat AS co ON co.ContratId = com.ContratId
        WHERE
        ca.commandeId = ?
        ");
        $stmt->bind_param("i", $commande_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["commandeId"] = $task["commandeId"];
            $tmp["typeContratId"] = $task["TypeContratId"];
            $tmp["analyseId"] = $task["analyseId"];
            $tmp["analyseLib"] = $task["analyseLib"];
            $tmp["netAPayer"] = $task["netAPayer"];
            $tmp["dateDeRetait"] = $task["dateDeRetait"];
            $tmp["insertDate"] = $task["insertDate"];
            $tmp["medecinId"] = $task["medecinId"];
            $tmp["medecinFullname"] = $task["Nom"] . " " . $task["Prenom"];
            $tmp["forfait"] = $task["forfait"];
            $tmp["tauxReduction"] = $task["tauxReduction"];
            $tmp["nbreB"] = $task["nbreB"];
            array_push($response, $tmp);
        }
        $stmt->close();
        return $response;
    }

    public function readAll(){
        $stmt = $this->conn->prepare("SELECT
        c.id,
        c.commandeId,
        c.analyseId,
        a.analyseLib,
        c.netAPayer,
        c.medecinId,
        c.dateDeRetait,
        c.insertDate,
        c.commandeId,
        c.forfait,
        c.tauxReduction,
        c.nbreB,
        e.Nom,
        e.Prenom
        FROM
        commandeanalyse AS c
        INNER JOIN analyse AS a ON a.id = c.analyseId
        LEFT OUTER JOIN employee AS e ON e.EmployeeId = c.medecinId");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["id"];
            $tmp["commandeId"] = $task["commandeId"];
            $tmp["analyseId"] = $task["analyseId"];
            $tmp["analyseLib"] = $task["analyseLib"];
            $tmp["netAPayer"] = $task["netAPayer"];
            $tmp["dateDeRetait"] = $task["dateDeRetait"];
            $tmp["insertDate"] = $task["insertDate"];
            $tmp["medecinId"] = $task["medecinId"];
            $tmp["medecinFullname"] = $task["nom"] . " " . $task["prenom"];
            $tmp["forfait"] = $task["forfait"];
            $tmp["tauxReduction"] = $task["tauxReduction"];
            $tmp["nbreB"] = $task["nbreB"];
            array_push($response, $tmp);
        }
        $stmt->close();
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
