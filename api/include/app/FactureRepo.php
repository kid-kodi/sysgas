<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class FactureRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        require_once 'EmployeeRepo.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllFactures($user_id) {
        $stmt = $this->conn->prepare("SELECT
        facture.FactureId,
        facture.CommandeId,
        facture.LaboratoireId,
        facture.NumeroFacture,
        facture.NetAPayer,
        facture.NbreB,
        facture.ModeDePaiementId,
        facture.FacturiereId,
        facture.RegisseurId,
        laboratoire.LaboratoireLib,
        modedepaiement.ModeDePaiementLib,
        facture.InsertDate,
        patient.Nom,
        patient.Prenom
        FROM
        facture
        INNER JOIN laboratoire ON laboratoire.id = facture.LaboratoireId
        INNER JOIN modedepaiement ON modedepaiement.ModeDePaiementId = facture.ModeDePaiementId
        INNER JOIN commande ON commande.Id = facture.CommandeId
        INNER JOIN patient ON patient.PatientId = commande.PatientId
        ");
        //$stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();

        $response = array();
        $response['factures'] = array();
        $response['totalPaye'] = 0;
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {

            $tmp = array();
            $tmp["id"] = $task["FactureId"];
            $tmp["commandeId"] = $task["CommandeId"];
            $tmp["laboratoireId"] = $task["LaboratoireId"];
            $tmp["numeroFacture"] = $task["NumeroFacture"];
            $tmp["nbreB"] = $task["NbreB"];
            $tmp["modeDePaiementId"] = $task["ModeDePaiementId"];

            $tmp["laboratoireLib"] = $task["LaboratoireLib"];
            $tmp["modeDePaiementLib"] = $task["ModeDePaiementLib"];
            
            $tmp["insertDate"] = $task["InsertDate"];
            $tmp["netAPayer"] = $task["NetAPayer"];
            $tmp["nbreB"] = $task["NbreB"];
            $tmp["patientFullName"] = $task["Nom"] . ' ' . $task["Prenom"];
            

            $EmployeeRepo = new EmployeeRepo();

            $owner     = $EmployeeRepo->getEmployeeByAcctId($task["FacturiereId"]);
            $submitter = $EmployeeRepo->getEmployeeByAcctId($task["RegisseurId"]);

            $tmp["ownerNom"]     = $owner["nom"] . ' ' . $owner["prenom"];
            $tmp["submitterNom"] = $submitter["nom"] . ' ' . $submitter["prenom"];



            //$totalPaye = $totalPaye + $task["NetAPayer"];
            $response['totalPaye'] = $response['totalPaye'] + $task["NetAPayer"];
            /*if($tmp["currentStateId"] == 2){
                $nbrePaye  = $nbrePaye + 1;
            }*/

            array_push($response['factures'], $tmp);
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
