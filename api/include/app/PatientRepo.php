<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class PatientRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getAllPatients($limit, $offset, $searchText) {
        $stmt = $this->conn->prepare("SELECT * FROM patient order by PatientId DESC LIMIT ? OFFSET ?");
        if($this->getTableCount( 'patient' ) < 50){
            $limit = $this->getTableCount( 'patient' );
            $offset = 0;
        }
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $tasks = $stmt->get_result();

        $response = array();

        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["PatientId"];
            $tmp["nom"] = $task["Nom"];
            $tmp["prenom"] = $task["Prenom"];
            $tmp["genre"] = $task["Genre"];
            $tmp["telephone"] = $task["Telephone"];
            $tmp["jourNaissance"] = $task["JourNaissance"];
            $tmp["moisNaissance"] = $task["MoisNaissance"];
            $tmp["anneeNaissance"] = $task["AnneeNaissance"];
            $tmp["adresse"] = $task["Adresse"];
            $tmp["email"] = $task["Email"];
            $tmp["insertDate"] = $task["InsertDate"];
            array_push($response, $tmp);
        }

        $stmt->close();
        return $response;
    }

    /**
     * Fetching single patient
     * @param integer $patient_id id
     */
    public function getPatientById($patient_id) {
        $stmt = $this->conn->prepare("SELECT
        patient.PatientId,
        patient.Nom,
        patient.Prenom,
        patient.Genre,
        patient.Telephone,
        patient.Adresse,
        patient.InsertDate,
        patient.Email,
        patient.PaysId,
        patient.JourNaissance,
        patient.MoisNaissance,
        patient.AnneeNaissance,
        patient.CommuneId,
        patient.TypePieceFournitId,
        patient.numeroPiece,
        patient.NumeroPatient
        FROM
        patient
        WHERE
        patient.PatientId = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result( $id, $nom, $prenom, $genre, 
                $telephone, $adresse, $insertDate, $email, $paysId, 
                $jourNaissance, $moisNaissance, $anneeNaissance, $communeId, 
                $typePieceFournitId, $numeroPiece, $numeroPatient);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["nom"] = $nom;
            $res["prenom"] = $prenom;
            $res["fullname"] = $nom . ' ' .$prenom;
            $res["genre"] = $genre;
            $res["telephone"] = $telephone;
            $res["adresse"] = $adresse;
            $res["insertDate"] = $insertDate;
            $res["email"] = $email;
            $res["paysId"] = $paysId;
            $res["jourNaissance"] = $jourNaissance;
            $res["moisNaissance"] = $moisNaissance;
            $res["anneeNaissance"] = $anneeNaissance;
            $res["fullBirthDate"] = $jourNaissance . '/' . $moisNaissance . '/' . $anneeNaissance;
            $res["communeId"] = $communeId;
            $res["typePieceFournitId"] = $typePieceFournitId;
            $res["numeroPiece"] = $numeroPiece;
            $res["numeroPatient"] = $numeroPatient;
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
    public function create( 
            $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id, $numeroPatient
        ) {
        $stmt = $this->conn->prepare("INSERT INTO patient(Nom, Prenom, Genre, JourNaissance, MoisNaissance, AnneeNaissance, Email, Telephone, PaysId, Adresse, TypePieceFournitId, numeroPiece, InsertENTUserAccountId, UpdateENTUserAccountId, NumeroPatient) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssssiis", $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id, $user_id, $numeroPatient);
        $result = $stmt->execute();
        $stmt->close();

        //echo 'OK'.$result;

        if ($result) {
            // task row created
            // now assign the task to user
            $new_patient_id = $this->conn->insert_id;
            //$res = $this->createUserTask($user_id, $new_task_id);
            return $new_patient_id;
        } else {
            // task failed to create
            return NULL;
        }
    }



    /**
     * Updating patient
     * @param String $patient_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function update($id, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id) {
        $stmt = $this->conn->prepare("UPDATE patient set Nom = ?, Prenom = ?, Genre = ?, JourNaissance = ?, MoisNaissance = ?, AnneeNaissance = ?, Email = ?, Telephone = ?, PaysId = ?, Adresse = ?, TypePieceFournitId = ?, numeroPiece = ?, InsertENTUserAccountId = ?, UpdateENTUserAccountId = ? WHERE PatientId = ?");
        $stmt->bind_param("ssssssssssssiis", $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id, $user_id, $id);
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
