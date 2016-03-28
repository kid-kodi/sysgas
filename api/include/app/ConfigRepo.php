<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class ConfigRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        require_once 'TypePatientRepo.php';
        require_once 'SocieteRepo.php';
        require_once 'EtablissementSanitairesRepo.php';
        require_once 'EmployeeRepo.php';
        require_once 'AnalyseRepo.php';
        require_once 'ServiceMedecinRepo.php';
        require_once '../include/base/AccountRepo.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    public function getCommandeConfig(){

      //global $user_id;
        $response = array();
        $response["error"]    = false;
        
        $TypePatientRepo = new TypePatientRepo();
        // fetching all typePatients
        $result_typePatients = $TypePatientRepo->getAllTypePatient();
        $response["typePatients"]  = array();
        $response["typePatients"]  = $result_typePatients;


        $SocieteRepo = new SocieteRepo();
        // fetching all societes
        $result_societes = $SocieteRepo->getAllSocietes();
        $response["societes"]  = array();
        $response["societes"]  = $result_societes;


        $EtabSanitairesRepo = new EtablissementSanitairesRepo();
        // fetching all mois
        $result_etabSanitaires = $EtabSanitairesRepo->getAllEtabAsnitaires();
        $response["etablissementSanitaire"]  = array();
        $response["etablissementSanitaire"]  = $result_etabSanitaires;


        $AccountRepo = new AccountRepo();
        // fetching all caisse user
        $result_caisseUser = $AccountRepo->getUserByRole(2);
        $response["caisseUser"]  = array();
        $response["caisseUser"]  = $result_caisseUser;

        $EmployeeRepo = new EmployeeRepo();
        // fetching all docteur and proffesseur
        $result_employees = $EmployeeRepo->getAllDocProf();
        $response["employees"]  = array();
        $response["employees"]  = $result_employees;


        $AnalyseRepo = new AnalyseRepo();
        // fetching all analyses
        $result_analyses = $AnalyseRepo->getAllAnalyses();
        $response["analyses"]  = array();
        $response["analyses"]  = $result_analyses;


        $ServiceMedecinRepo = new ServiceMedecinRepo();
        // fetching all services Medecins
        $result_servicesMedeceins = $ServiceMedecinRepo->getAllServiceMedecins();
        $response["serviceMedecins"]  = array();
        $response["serviceMedecins"]  = $result_servicesMedeceins;


        return $response;

    }

}

?>
