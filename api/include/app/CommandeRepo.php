<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class CommandeRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        require_once 'EmployeeRepo.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getAllCommandes($limit, $offset, $searchText, $filter) {
        $stmt = $this->conn->prepare("SELECT
        commande.Id,
        patient.Nom,
        patient.Prenom,
        societe.SocieteLib,
        typecontrat.TypeContratLib,
        commande.InsertDate,
        commande.OwnerId,
        commande.SubmitterId,
        entwfstate.StateName,
        commande.CurrentStateId,
        commande.NomMedecin,
        commande.TelephoneMedecin,
        servicemedecin.ServiceMedecinLib,
        etablissementsanitaire.EtablissementSanitaireLib,
        commande.NetAPayer,
        commande.NbreB,
        Count(commandeanalyse.Id) as analyseNumber
        FROM
        commande
        INNER JOIN patient ON patient.PatientId = commande.PatientId
        INNER JOIN contrat ON contrat.ContratId = commande.ContratId
        INNER JOIN societe ON societe.SocieteId = contrat.SocieteId
        INNER JOIN typecontrat ON typecontrat.TypeContratId = contrat.TypeContratId
        INNER JOIN entwfstate ON entwfstate.ENTWFStateId = commande.CurrentStateId
        INNER JOIN servicemedecin ON servicemedecin.ServiceMedecinId = commande.ServiceMedecinId
        INNER JOIN etablissementsanitaire ON etablissementsanitaire.Id = commande.EtablissementSanitaireId
        INNER JOIN commandeanalyse ON commandeanalyse.CommandeId = commande.Id
        WHERE  commande.InsertDate >= ? AND commande.InsertDate <= ?
        GROUP BY
        commande.Id
        order by Id ASC LIMIT ? OFFSET ?");

        if( $this->getTableCount( 'commande' ) < 50 ){
            $limit = $this->getTableCount( 'commande' );
            $offset = 0;
        }

        $enddate   = date("Y-m-d H:i:s");
        $startdate = date("Y-m-d", strtotime("- 365 days"));

        if($filter == 1){
            $startdate = date("Y-m-d");
            $enddate   = date("Y-m-d H:i:s");
        }

        

        $stmt->bind_param("ssii", $startdate, $enddate, $limit, $offset);
        $stmt->execute();
        $tasks = $stmt->get_result();

        $response = array();
        $response['commandes'] = array();

        $totalAttente = 0;
        $nbreAttente = 0;

        $totalPaye = 0;
        $nbrePaye = 0;

        $totalAnnule = 0;
        $nbreAnnule = 0;

        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["Id"];
            $tmp["patientFullname"] = $task["Nom"] . ' ' . $task["Prenom"];
            $tmp["contratLib"] = $task["TypeContratLib"];
            $tmp["societeLib"] = $task["SocieteLib"];
            $tmp["totalNetAPayer"] = $task["NetAPayer"];
            $tmp["nbreB"] = $task["NbreB"];
            $tmp["currentStateId"] = $task["CurrentStateId"];
            $tmp["currentStateLib"] = $task["StateName"];

            /*$owner     = $this->getEmployeeByAcctId($task["OwnerId"]);
            $submitter = $this->getEmployeeByAcctId($task["SubmitterId"]);*/
            $tmp["analyseNumber"] = $task["analyseNumber"];
            $tmp["insertDate"] = $task["InsertDate"];

            if($tmp["currentStateId"] == 1){
                $nbreAttente  = $nbreAttente + 1;
                $totalAttente = $totalAttente + $task["NetAPayer"];
            }

            if($tmp["currentStateId"] == 2){
                $nbrePaye  = $nbrePaye + 1;
                $totalPaye = $totalPaye + $task["NetAPayer"];
            }
            if($tmp["currentStateId"] == 3){
                $nbreAnnule  = $nbreAnnule + 1;
                $totalAnnule = $totalAnnule + $task["NetAPayer"];
            }
            array_push($response['commandes'], $tmp);
        }


        $response['totalAttente'] = $totalAttente;
        $response['nbreAttente']  = $nbreAttente;

        $response['totalPaye']    = $totalPaye;
        $response['nbrePaye']     = $nbrePaye;

        $response['totalAnnule']  = $totalAnnule;
        $response['nbreAnnule']   = $nbreAnnule;
        
        $stmt->close();
        return $response;
    }


    


    /**
     * Fetching single patient
     * @param integer $patient_id id
     */
    public function getCommandeById($cmd_id) {
        $stmt = $this->conn->prepare("SELECT
        commande.Id,
        commande.PatientId,
        societe.SocieteLib,
        typecontrat.TypeContratLib,
        commande.OwnerId,
        commande.SubmitterId,
        entwfstate.StateName,
        commande.CurrentStateId,
        commande.NomMedecin,
        commande.TelephoneMedecin,
        servicemedecin.ServiceMedecinLib,
        etablissementsanitaire.EtablissementSanitaireLib,
        commande.NetAPayer,
        commande.NbreB,
        typepatient.TypePatientLib,
        Count(commandeanalyse.Id) AS analyseNumber,
        commande.InsertDate
        FROM
        commande
        INNER JOIN contrat ON contrat.ContratId = commande.ContratId
        INNER JOIN societe ON societe.SocieteId = contrat.SocieteId
        INNER JOIN typecontrat ON typecontrat.TypeContratId = contrat.TypeContratId
        INNER JOIN entwfstate ON entwfstate.ENTWFStateId = commande.CurrentStateId
        INNER JOIN servicemedecin ON servicemedecin.ServiceMedecinId = commande.ServiceMedecinId
        INNER JOIN etablissementsanitaire ON etablissementsanitaire.Id = commande.EtablissementSanitaireId
        INNER JOIN commandeanalyse ON commandeanalyse.CommandeId = commande.Id AND commande.Id = ?
        INNER JOIN typepatient ON typepatient.TypePatientId = commande.TypePatientId
        GROUP BY
        commande.Id
        ");
        $stmt->bind_param("i", $cmd_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $patientId, $societeLib, $typeContratId, $ownerId, $submitterId, $stateName, $currentStateId, $nomMedecin, $telephoneMedecin, $serviceMedecinLib, $etabSanitaireLib, $netAPayer, $nbreB, $typePatientLib, $analyseNumber, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"]                = $id;
            $res["patientId"]         = $patientId;
            $res["typePatientLib"]    = $typePatientLib;
            $res["societeLib"]        = $societeLib;
            $res["typeContratId"]     = $typeContratId;
            $res["ownerId"]           = $ownerId;
            $res["submitterId"]       = $submitterId;
            $res["currentStateLib"]   = $stateName;
            $res["currentStateId"]    = $currentStateId;
            $res["nomMedecin"]        = $nomMedecin;
            $res["telephoneMedecin"]  = $telephoneMedecin;
            $res["serviceMedecinLib"] = $serviceMedecinLib;
            $res["etabSanitaireLib"]  = $etabSanitaireLib;
            $res["totalNetAPayer"]    = $netAPayer;
            $res["totalNbreB"]        = $nbreB;
            $res["analyseNumber"]     = $analyseNumber;
            $res["insertDate"]        = $insertDate;

            /*echo $ownerId . '###' . $submitterId;*/
            $stmt->close();

            $EmployeeRepo = new EmployeeRepo();

            $owner     = $EmployeeRepo->getEmployeeByAcctId($ownerId);
            $submitter = $EmployeeRepo->getEmployeeByAcctId($submitterId);

            $res["ownerNom"]     = $owner["nom"] . ' ' . $owner["prenom"];
            $res["submitterNom"] = $submitter["nom"] . ' ' . $submitter["prenom"];
            $res["analyse_list"] = $this->getAnalyseByCmdId( $id );


            
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Creating new commande
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createCommande($user_id, $patientId, $typePatientId, $societeId, $contratId, $nomMedecin, $telephoneMedecin, $serviceMedecinId, $etablissementSanitaireId, $ownerId, $totalNetAPayer, $totalNbreB, $currentStateId, 
        $submitterId, $numeroCommande, $numeroAssurance, $analyse_list) {
        $stmt = $this->conn->prepare("INSERT INTO `commande` (PatientId, `TypePatientId`, `ContratId`, `NomMedecin`, `TelephoneMedecin`, `ServiceMedecinId`, `SubmitterId`, `OwnerId`, `CurrentStateId`, InsertENTUserAccountId, UpdateENTUserAccountId, EtablissementSanitaireId, NetAPayer, NbreB, NumeroCommande, NumeroAssurance ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssssssss", $patientId, $typePatientId, $contratId, $nomMedecin, $telephoneMedecin, 
            $serviceMedecinId, $user_id, $ownerId, $currentStateId, $user_id, $user_id, $etablissementSanitaireId, 
            $totalNetAPayer, $totalNbreB, $numeroCommande, $numeroAssurance);
        $result = $stmt->execute();
        $stmt->close();

        //echo 'OK'.$result;

        if ($result) {
            // task row created
            // now assign the task to user
            $new_commande_id = $this->conn->insert_id;
            foreach ($analyse_list as  $analyse) {
                $res = $this->createCommandeAnalyse($new_commande_id, $analyse, $user_id);
                if( ! $res ){
                    return NULL;
                }
            }

            //$res = $this->createUserTask($user_id, $new_task_id);
            return $new_commande_id;
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Creating new commande
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function updateCommande($user_id, $id, $patientId, $typePatientId, $societeId, $contratId, $nomMedecin, $telephoneMedecin, $serviceMedecinId, $etablissementSanitaireId, $ownerId, $totalNetAPayer, $totalNbreB, $currentStateId, 
        $submitterId, $numeroAssurance, $analyse_list) {

        $stmt = $this->conn->prepare("UPDATE commande set PatientId = ?, TypePatientId = ?, ContratId = ?, NomMedecin = ?, TelephoneMedecin = ?, ServiceMedecinId = ?, SubmitterId = ?, OwnerId = ?, CurrentStateId = ?, InsertENTUserAccountId = ?, UpdateENTUserAccountId = ?, EtablissementSanitaireId = ?, NetAPayer = ?, NbreB = ?, NumeroAssurance = ? WHERE Id = ?");
        $stmt->bind_param("ssssssssssssssss", $patientId, $typePatientId, $contratId, $nomMedecin, $telephoneMedecin, 
            $serviceMedecinId, $user_id, $ownerId, $currentStateId, $user_id, $user_id, $etablissementSanitaireId, 
            $totalNetAPayer, $totalNbreB, $numeroAssurance, $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();


        if($num_affected_rows > 0){

            $commande_id = $id;
            $is_deleted = $this->deleteCommandeAnalyse( $commande_id );

            if( $is_deleted ){
                foreach ($analyse_list as  $analyse) {
                    $res = $this->createCommandeAnalyse($commande_id, $analyse, $user_id);
                    if( ! $res ){
                        return NULL;
                    }
                }
            }
        }

        return $num_affected_rows > 0;
    }


    /**
     * Fetching single patient
     * @param integer $patient_id id
     */
    public function getCommandeByPatientId($patient_id) {

        $stmt = $this->conn->prepare("SELECT
        commande.Id,
        commande.PatientId,
        commande.TypePatientId,
        commande.ContratId,
        commande.NomMedecin,
        commande.TelephoneMedecin,
        commande.ServiceMedecinId,
        commande.TypeHopitalId,
        commande.NomHopital,
        commande.SubmitterId,
        commande.OwnerId,
        commande.CurrentStateId,
        commande.InsertDate,
        commande.InsertENTUserAccountId,
        commande.UpdateDate,
        commande.UpdateENTUserAccountId,
        commande.EtablissementSanitaireId,
        commande.NetAPayer,
        commande.NbreB,
        commande.numeroAssurance,
        commande.NumeroCommande,
        tp.TypePatientLib,
        s.SocieteId,
        s.SocieteLib,
        tc.TypeContratId,
        tc.TypeContratLib,
        tc.IsPacket,
        th.TypeHopitalLib,
        ews.StateName,
        Count(ca.Id) AS AnalyseNum,
        Sum(ca.NetAPayer) AS TotalNetAPayer,
        Sum(ca.NbreB) AS TotalNbreB,
        p.Nom,
        p.Prenom
        FROM
        commande
        INNER JOIN typepatient AS tp ON tp.TypePatientId = commande.TypePatientId
        INNER JOIN contrat AS c ON c.ContratId = commande.ContratId
        INNER JOIN societe AS s ON s.SocieteId = c.SocieteId
        INNER JOIN typecontrat AS tc ON tc.TypeContratId = c.TypeContratId
        LEFT OUTER JOIN typehopital AS th ON th.TypeHopitalId = commande.TypeHopitalId
        INNER JOIN entwfstate AS ews ON ews.ENTWFStateId = commande.CurrentStateId
        INNER JOIN commandeanalyse AS ca ON ca.CommandeId = commande.Id
        INNER JOIN patient AS p ON p.PatientId = commande.PatientId
        WHERE
        commande.PatientId = ?
        GROUP BY
        commande.Id
        ");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();

        $tasks = $stmt->get_result();

        $response = array();
        $EmployeeRepo = new EmployeeRepo();

        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["Id"];
            $tmp["patientId"] = $task["PatientId"];
            $tmp["typePatientId"] = $task["TypePatientId"];
            $tmp["contratId"] = $task["ContratId"];
            $tmp["societeId"] = $task["SocieteId"];
            $tmp["typeContratId"] = $task["TypeContratId"];
            $tmp["nomMedecin"] = $task["NomMedecin"];
            $tmp["telephoneMedecin"] = $task["TelephoneMedecin"];
            $tmp["serviceMedecinId"] = $task["ServiceMedecinId"];
            $tmp["typeHopitalId"] = $task["TypeHopitalId"];
            $tmp["submitterId"] = $task["SubmitterId"];
            $tmp["ownerId"] = $task["OwnerId"];
            $tmp["currentStateId"] = $task["CurrentStateId"];
            $tmp["insertDate"] = $task["InsertDate"];
            $tmp["etablissementSanitaireId"] = $task["EtablissementSanitaireId"];
            $tmp["netAPayer"] = $task["NetAPayer"];
            $tmp["nbreB"] = $task["NbreB"];
            $tmp["numeroAssurance"] = $task["numeroAssurance"];
            $tmp["numeroCommande"] = $task["NumeroCommande"];
            $tmp["typePatientLib"] = $task["TypePatientLib"];
            $tmp["societeLib"] = $task["SocieteLib"];
            $tmp["typeContratLib"] = $task["TypeContratLib"];
            $tmp["isPacket"] = $task["IsPacket"];
            $tmp["typeHopitalLib"] = $task["TypeHopitalLib"];
            $tmp["currentStateLib"] = $task["StateName"];
            $tmp["analyseNum"] = $task["AnalyseNum"];
            $tmp["totalNetAPayer"] = $task["TotalNetAPayer"];
            $tmp["totalNbreB"] = $task["TotalNbreB"];
            $tmp["patientFullname"] = $task["Nom"] . ' ' . $task["Prenom"];


            $owner     = $EmployeeRepo->getEmployeeByAcctId($task["OwnerId"]);
            $submitter = $EmployeeRepo->getEmployeeByAcctId($task["SubmitterId"]);

            $tmp["ownerNom"]     = $owner["nom"] . ' ' . $owner["prenom"];
            $tmp["submitterNom"] = $submitter["nom"] . ' ' . $submitter["prenom"];
            $tmp["analyse_list"] = $this->getAnalyseByCmdId( $task["Id"] );
            array_push($response, $tmp);
        }

        $stmt->close();
        return $response;
    }


    

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAnalyseByCmdId($cmd_id) {
        $stmt = $this->conn->prepare("SELECT
        commandeanalyse.AnalyseId,
        commandeanalyse.MedecinId,
        contrat.TypeContratId
        FROM
        commandeanalyse
        INNER JOIN commande ON commande.Id = commandeanalyse.CommandeId AND commandeanalyse.CommandeId = ?
        INNER JOIN contrat ON contrat.ContratId = commande.ContratId");
        $stmt->bind_param("i", $cmd_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $response = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $analyseid = $task["AnalyseId"];
            $employeeid = $task["MedecinId"];
            $tcontratid = $task["TypeContratId"];

            $analyse = $this->getAnalyseDetails($tcontratid, $analyseid, $employeeid);
            
            array_push($response, $analyse);
        }

        $stmt->close();
        return $response;
    }


    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getAnalyseDetails($tcontratid, $analyseid, $employeeid) {
        $stmt = $this->conn->prepare("SELECT
        analyse.id,
        analyse.AnalyseLib,
        analyse.NbreJourRetrait,
        analyse.LaboratoireId,
        prixanalyse.Forfait,
        prixanalyse.NbreB,
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
                $EmployeeRepo = new EmployeeRepo();
                $employee = $EmployeeRepo->getEmployeeNameById($employeeid);
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
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function computeNetAPayer($employeeid, $forfait) {
        $stmt = $this->conn->prepare("SELECT
        titre.TauxReduction
        FROM
        employee
        INNER JOIN titre ON titre.TitreId = employee.TitreId
        WHERE
        employee.EmployeeId = ?");
        $stmt->bind_param("i", $employeeid);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result( $taux );
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $netAPayer = $forfait - ( $taux / 100 * $forfait  );
            $stmt->close();
            return $netAPayer;
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
