<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class StatistiqueRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `stats` table method ------------------ */
    /**
     * Fetching patient stats
     * @param  no
     * @return no
     */
    public function getPatientStat() {
        $stmt = $this->conn->prepare("SELECT * FROM patient");
        $patient = array();
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->store_result();
            $patient["statsLib"]    = "patient";
            $patient["statsNumber"] = $stmt->num_rows;
            $stmt->close();

            return $patient;
        } else {
            return NULL;
        }
    }


    /**
     * Fetching commande stats
     * @param  no
     * @return no
     */
    public function getCommandeStat() {
        $stmt = $this->conn->prepare("SELECT * FROM commande");
        $commande = array();
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->store_result();
            $commande["statsLib"]    = "commande";
            $commande["statsNumber"] = $stmt->num_rows;
            $stmt->close();

            return $commande;
        } else {
            return NULL;
        }
    }

}

?>
