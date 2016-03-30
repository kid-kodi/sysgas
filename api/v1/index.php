<?php

require_once '../include/base/AccountRepo.php';
require_once '../include/app/StatistiqueRepo.php';
require_once '../include/app/SocieteRepo.php';
require_once '../include/app/TypeClientRepo.php';
require_once '../include/app/TypePatientRepo.php';
require_once '../include/app/DepartementRepo.php';
require_once '../include/app/ConfigRepo.php';
require_once '../include/app/PatientRepo.php';
require_once '../include/app/CommandeRepo.php';
require_once '../include/app/CommandeAnalyseRepo.php';
require_once '../include/app/FactureRepo.php';
require_once '../include/app/LaboratoireRepo.php';
require_once '../include/app/UniteRepo.php';
require_once '../include/app/AnalyseRepo.php';
require_once '../include/app/EchantillonRepo.php';
require_once '../include/app/DayRepo.php';
require_once '../include/app/MonthRepo.php';
require_once '../include/app/YearRepo.php';
require_once '../include/app/CommuneRepo.php';
require_once '../include/app/CountryRepo.php';
require_once '../include/app/TypePieceRepo.php';
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';
require '.././libs/RedBean/rb.php';

\Slim\Slim::registerAutoloader();

// set up database connection
R::setup('mysql:host=localhost;dbname=sysgasdb','root','');
R::freeze(true);

$app = new \Slim\Slim();
$app->contentType('application/json;charset=utf-8');

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/account', 'authenticate', function() use ($app) {
            $postArray = json_decode($app->request()->getBody(), true);
            // check for required params
            verifyRequiredParams(array('userName', 'password', 'employeeId', 'roleId'), $postArray );

            $response = array();

            // reading post params
            $username   = $postArray['userName'];
            $password   = $postArray['password'];
            $employeeId = $postArray['employeeId'];
            $roleId     = $postArray['roleId'];

            // validating email address
            global $user_id;
            $db = new DbHandler();
            $res = $db->createAccount($username, $password, $employeeId, $roleId, $user_id);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->put('/account', 'authenticate', function() use ($app) {
            $postArray = json_decode($app->request()->getBody(), true);
            // check for required params
            verifyRequiredParams(array('userName', 'password', 'employeeId', 'roleId'), $postArray );

            $response = array();

            // reading post params
            $account_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
            $username   = $postArray['userName'];
            $password   = $postArray['password'];
            $employeeId = $postArray['employeeId'];
            $roleId     = $postArray['roleId'];

            global $user_id;
            $db = new DbHandler();
            $result_account = $db->updateAccount($account_id, $username, $password, $employeeId, $roleId, $user_id);
            $result_roleaccount = $db->updateUserRole($roleId, $account_id, $user_id);


            if ($result_account == USER_CREATED_SUCCESSFULLY && $result_roleaccount) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($result_account == USER_CREATE_FAILED || ! $result_roleaccount) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($result_account == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);

            verifyRequiredParams(array('username', 'password'), $postArray);


            // reading post params
            $username = $postArray['username'];
            $password = $postArray['password'];
            $response = array();

            $db = new AccountRepo();
            // check for correct email and password
            if ($db->checkLogin($username, $password)) {
                // get the user by email
                $user = $db->getUserByUsername($username);

                if ($user != NULL) {
                    $response["error"]           = false;
                    $response['username']        = $user['username'];
                    $response['apiKey']          = $user['api_key'];
                    $response['avatar']          = $user['avatar'];
                    $response['userPermissions'] = $user['userPermissions'];
                    $response['createdAt']       = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 
                'Échec de la connexion. vos références sont incorrectes';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ PATIENT METHODS ------------------------
 */


/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/account', 'authenticate', function() use ($app) {
        //global $user_id;
        $employee_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $db = new DbHandler();

        // fetching all user tasks
        $response["error"] = false;

        $result_account = $db->getUserByEmployeeId( $employee_id );
        $response["account"]  = $result_account;

        $result_employee = $db->getEmployeeById( $employee_id );
        $response["employee"]  = $result_employee;

        echoRespnse(200, $response);
    });


/**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/patient/editconfig', 'authenticate', function() {
            //global $user_id;
            $response = array();

            $response["error"]    = false;
            
            // fetching all days
            $DayRepo = new DayRepo();
            $result_jours = $DayRepo->getAllDay();
            $response["jours"]  = array();
            $response["jours"]  = $result_jours;


            // fetching all mois
            $MonthRepo = new MonthRepo();
            $result_mois = $MonthRepo->getAllMonths();
            $response["mois"]  = array();
            $response["mois"]  = $result_mois;

            // fetching all mois
            $YearRepo = new YearRepo();
            $result_annees = $YearRepo->getAllYears();
            $response["annees"]  = array();
            $response["annees"]  = $result_annees;


            // fetching all pays
            $CountryRepo = new CountryRepo();
            $result_pays = $CountryRepo->getAllCountry();
            $response["pays"]  = array();
            $response["pays"]  = $result_pays;


            // fetching all communes
            $CommuneRepo = new CommuneRepo();
            $result_communes = $CommuneRepo->getAllCommune();
            $response["communes"]  = array();
            $response["communes"]  = $result_communes;


            // fetching all mois
            $TypePieceRepo = new TypePieceRepo();
            $result_typepiece = $TypePieceRepo->getAllTypePiece();
            $response["typePieceFournits"]  = array();
            $response["typePieceFournits"]  = $result_typepiece;



            echoRespnse(200, $response);
        });

/**
 * Listing all roles
 * method GET
 * url /role
 * params page, limit, searchText          
 */
$app->get('/employee', 'authenticate', function() use ($app) {
        //global $user_id;

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
        $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

        $offset = ($page * $limit) + 1; //calculate what data you want
        //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
        //page 2 -> 1 * 10 -> get data from row 10 to row 19

        $response = array();
        $db = new DbHandler();

        // fetching all user tasks
        $result_patient = $db->getAllEmployee( $limit, $offset, $searchText ) ;
        $response["error"] = false;
        $response["employees"] = $result_patient;

        $result_count = $db->getTableCount( 'employee' );
        $response["totalCount"] = $result_count;
        echoRespnse(200, $response);
    });

/**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/employee/editconfig', 'authenticate', function() {
            //global $user_id;
            $response = array();
            $db = new DbHandler();

            $response["error"]    = false;
            
            // fetching all days
            $result_jours = $db->getAllDay();
            $response["jours"]  = array();
            $response["jours"]  = $result_jours;


            // fetching all mois
            $result_mois = $db->getAllMonths();
            $response["mois"]  = array();
            $response["mois"]  = $result_mois;

            // fetching all mois
            $result_annees = $db->getAllYears();
            $response["annees"]  = array();
            $response["annees"]  = $result_annees;


            // fetching all pays
            $result_pays = $db->getAllCountry();
            $response["pays"]  = array();
            $response["pays"]  = $result_pays;


            // fetching all communes
            $result_communes = $db->getAllCommune();
            $response["communes"]  = array();
            $response["communes"]  = $result_communes;


            // fetching all mois
            $result_typepiece = $db->getAllTypePiece();
            $response["typePieceFournits"]  = array();
            $response["typePieceFournits"]  = $result_typepiece;

            // fetching all titres
            $result_titre = $db->getAllTitre();
            $response["titres"]  = array();
            $response["titres"]  = $result_titre;

            echoRespnse(200, $response);
        });


/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/employeeById', 'authenticate', function() use ($app) {
        //global $user_id;
        $emp_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $db = new DbHandler();

        // fetching all user tasks
        $response["error"] = false;

        $result_employee = $db->getEmployeeById($emp_id);
        $response["employee"]  = $result_employee;

        echoRespnse(200, $response);
    });

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/employee', 'authenticate', function() use ($app) {
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);
            // check for required params
            verifyRequiredParams(array(
                'titreId', 'nom', 'prenom', 'genre', 'jourNaissance', 'moisNaissance', 
                'anneeNaissance', 'telephone', 'paysId', 'typePieceFournitId'), $postArray );

            $response = array();

            //print_r($postArray); 

            // reading post params
            $titreId            = $postArray['titreId'];
            $nom                = $postArray['nom'];
            $prenom             = $postArray['prenom'];
            $genre              = $postArray['genre'];

            //echo $genre;
            $jourNaissance      = $postArray['jourNaissance'];
            $moisNaissance      = $postArray['moisNaissance'];
            $anneeNaissance     = $postArray['anneeNaissance'];
            //$email              = $postArray['email'];
            $telephone          = $postArray['telephone'];
            $paysId             = $postArray['paysId'];

            if (!isset($postArray['email']) || strlen(trim($postArray['email'])) <= 0){
                $email            = "";
            }
            else{
                $email            = $postArray['email'];
            }

            if (!isset($postArray['adresse']) || strlen(trim($postArray['adresse'])) <= 0){
                $adresse            = "";
            }
            else{
                $adresse            = $postArray['adresse'];
            }


            $typePieceFournitId = $postArray['typePieceFournitId'];
            if (!isset($postArray['numeroPiece']) || strlen(trim($postArray['numeroPiece'])) <= 0){
                $numeroPiece        = "";
            }
            else{
                $numeroPiece        = $postArray['numeroPiece'];
            }

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $patient_id = $db->createEmployee( $titreId, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id
            );
            //echo 'OK';

            if ($patient_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Employee enregistré!";
                $response["patient_id"] = $patient_id;
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/employee', 'authenticate', function() use($app) {
            //$patient_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);

            //print_r($postArray);

            // check for required params
            verifyRequiredParams(array(
                'id', 'titreId', 'nom', 'prenom', 'genre', 'jourNaissance', 'moisNaissance', 
                'anneeNaissance', 'telephone', 'paysId', 'typePieceFournitId'), $postArray );

            $response = array();

            //print_r($postArray); 

            // reading post params
            $id                 = $postArray['id'];
            $titreId            = $postArray['titreId'];
            $nom                = $postArray['nom'];
            $prenom             = $postArray['prenom'];
            $genre              = $postArray['genre'];

            //echo $genre;
            $jourNaissance      = $postArray['jourNaissance'];
            $moisNaissance      = $postArray['moisNaissance'];
            $anneeNaissance     = $postArray['anneeNaissance'];
            //$email              = $postArray['email'];
            $telephone          = $postArray['telephone'];
            $paysId             = $postArray['paysId'];

            if (!isset($postArray['email']) || strlen(trim($postArray['email'])) <= 0){
                $email            = "";
            }
            else{
                $email            = $postArray['email'];
            }

            if (!isset($postArray['adresse']) || strlen(trim($postArray['adresse'])) <= 0){
                $adresse            = "";
            }
            else{
                $adresse            = $postArray['adresse'];
            }


            $typePieceFournitId = $postArray['typePieceFournitId'];
            if (!isset($postArray['numeroPiece']) || strlen(trim($postArray['numeroPiece'])) <= 0){
                $numeroPiece        = "";
            }
            else{
                $numeroPiece        = $postArray['numeroPiece'];
            }

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $result = $db->updateEmployee( 
                $id, $titreId, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id
            );
            //echo 'OK';

            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Information(s) du patient modifiée(s)!";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Acune(s) modification(s) effectuée(s)!";
            }
            echoRespnse(200, $response);
        });



/**
 * Listing all roles
 * method GET
 * url /role
 * params page, limit, searchText          
 */
$app->get('/role', 'authenticate', function() use ($app) {
        //global $user_id;

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
        $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

        $offset = ($page * $limit) + 1; //calculate what data you want
        //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
        //page 2 -> 1 * 10 -> get data from row 10 to row 19

        $response = array();
        $db = new DbHandler();

        // fetching all user tasks
        $result_patient = $db->getAllRole($limit, $offset, $searchText);
        $response["error"] = false;
        $response["roles"] = $result_patient;

        $result_count = $db->getTableCount( 'entrole' );
        $response["totalCount"] = $result_count;
        echoRespnse(200, $response);
    });


/**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/account/editconfig', 'authenticate', function() {
            //global $user_id;
            $response = array();
            $db = new DbHandler();

            $response["error"]    = false;
            
            // fetching all days
            $result_roles = $db->getAllRoles();
            $response["roles"]  = array();
            $response["roles"]  = $result_roles;

            echoRespnse(200, $response);
        });

/**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/role/editconfig', 'authenticate', function() {
            //global $user_id;
            $response = array();
            $db = new DbHandler();

            $response["error"]    = false;
            
            // fetching all days
            $result_capabilities = $db->getAllCapabilities();
            $response["capabilities"]  = array();
            $response["capabilities"]  = $result_capabilities;

            echoRespnse(200, $response);
        });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/roleById', 'authenticate', function() use ($app) {
        //global $user_id;
        $role_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $db = new DbHandler();

        // fetching all user tasks
        $response["error"] = false;

        $result_role = $db->getRoleById( $role_id );
        $response["role"]  = $result_role;
        
        $capabilities = $db->getCapabilityByRoleId( $role_id );
        $response["capabilities"] = array();
        foreach ($capabilities  as $capability ) {
            array_push($response["capabilities"], $capability["capabilityId"]);
        }

        echoRespnse(200, $response);
    });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/role', 'authenticate', function() use ($app) {
    $postArray = json_decode($app->request()->getBody(), true);
            // check for required params
            verifyRequiredParams(array('roleName'), $postArray);

            $response = array();
            $roleName = $postArray['roleName'];
            $capabilities = $postArray['capabilities'];

            //print_r($capabilities);

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createRole($user_id, $roleName, $capabilities);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });



/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/role', 'authenticate', function() use($app) {
            //$patient_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);

            //print_r($postArray);

            // check for required params
            verifyRequiredParams(array(
                'id', 'roleName'), $postArray );

            $response = array();

            //print_r($postArray); 

            // reading post params
            $roleId       = $postArray['id'];
            $roleName     = $postArray['roleName'];
            $capabilities = $postArray['capabilities'];

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $result = $db->updateRole( 
                $user_id, $roleId, $roleName, $capabilities
            );
            //echo 'OK';

            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Information(s) du patient modifiée(s)!";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Acune(s) modification(s) effectuée(s)!";
            }
            echoRespnse(200, $response);
        });

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/patient', 'authenticate', function() use ($app) {
        //global $user_id;

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
        $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

        $offset = ($page * $limit) + 1; //calculate what data you want
        //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
        //page 2 -> 1 * 10 -> get data from row 10 to row 19

        $response = array();
        $db = new PatientRepo();

        // fetching all user tasks
        $result_patient = $db->getAllPatients($limit, $offset, $searchText);
        $response["error"] = false;
        $response["patients"] = $result_patient;

        $result_count = $db->getTableCount( 'patient' );
        $response["totalCount"] = $result_count;
        echoRespnse(200, $response);
    });

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/commande', 'authenticate', function() use ($app) {
        //global $user_id;

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";
        $filter = isset($_GET['filter']) ? $_GET['filter'] : "_";

        $offset = ($page * $limit) + 1; //calculate what data you want
        //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
        //page 2 -> 1 * 10 -> get data from row 10 to row 19

        $response = array();
        $db = new CommandeRepo();

        // fetching all user tasks
        $result = $db->getAllCommandes($limit, $offset, $searchText, $filter);

        $response["error"] = false;
        $response["commandes"]    = $result['commandes'];

        $result_count = $db->getTableCount( 'commande' );
        $response["totalCount"] = $result_count;


        $response["totalAttente"] = $result['totalAttente'];
        $response["nbreAttente"]  = $result['nbreAttente'];

        $response["totalPaye"] = $result['totalPaye'];
        $response["nbrePaye"]  = $result['nbrePaye'];

        $response["totalAnnule"] = $result['totalAnnule'];
        $response["nbreAnnule"]  = $result['nbreAnnule'];
        echoRespnse(200, $response);
    });

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/facture', 'authenticate', function() use ($app) {
        //global $user_id;

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";
        $labId = isset($_GET['labId']) ? $_GET['labId'] : "_";
        $empId = isset($_GET['empId']) ? $_GET['empId'] : "_";
        $numeroFacture = isset($_GET['numeroFacture']) ? $_GET['numeroFacture'] : "_";
        $date = isset($_GET['date']) ? $_GET['date'] : "_";

        $offset = ($page * $limit) + 1; //calculate what data you want
        //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
        //page 2 -> 1 * 10 -> get data from row 10 to row 19

        $response = array();
        $FactureRepo = new FactureRepo();

        // fetching all user tasks
        $result = $FactureRepo->getAllFactures($limit, $offset, $searchText, $labId);

        $response["error"] = false;
        $response["factures"]  = $result['factures'];
        $response["totalPaye"] = $result['totalPaye'];

        $result_count = $FactureRepo->getTableCount( 'facture' );
        $response["totalCount"] = $result_count;


        $DayRepo = new DayRepo();
        // fetching all days
        $result_jours = $DayRepo->getAllDay();
        $response["jours"]  = array();
        $response["jours"]  = $result_jours;


        $MonthRepo = new MonthRepo();
        // fetching all mois
        $result_mois = $MonthRepo->getAllMonths();
        $response["mois"]  = array();
        $response["mois"]  = $result_mois;

        $YearRepo = new YearRepo();
        // fetching all mois
        $result_annees = $YearRepo->getAllYears();
        $response["annees"]  = array();
        $response["annees"]  = $result_annees;


        $LaboratoireRepo = new LaboratoireRepo();
        $result_laboratoires = $LaboratoireRepo->read();
        $response["laboratoires"]  = array();
        $response["laboratoires"]  = $result_laboratoires;

        // fetching all caisse user
        $AccountRepo = new AccountRepo();
        $result_caisseUser = $AccountRepo->getUserByRole(2);
        $response["caisseUser"]  = array();
        $response["caisseUser"]  = $result_caisseUser;

        echoRespnse(200, $response);
    });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/patientById', 'authenticate', function() use ($app) {
        //global $user_id;
        $patient_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $response["error"] = false;
        
        $patient_db = new PatientRepo();
        // fetching all user tasks
        $result_patient = $patient_db->getPatientById($patient_id);
        $response["patient"] = $result_patient;

        
        $cmd_db = new CommandeRepo();
        $result_commande = $cmd_db->getCommandeByPatientId($patient_id);
        $response["commande_list"] = array();
        $response["commande_list"] = $result_commande;

        echoRespnse(200, $response);
    });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/analyseByCommandeId', 'authenticate', function() use ($app) {
        //global $user_id;
        $commande_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $response["error"] = false;
        
        $CommandeAnalyseRepo = new CommandeAnalyseRepo();
        // fetching all user tasks
        $result_analyse = $CommandeAnalyseRepo->getAnalyseByCommandeId($commande_id);
        $response["analyses"] = $result_analyse;

        echoRespnse(200, $response);
    });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/getCommandeById', 'authenticate', function() use ($app) {
        //global $user_id;
        $cmd_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $CommandeRepo = new CommandeRepo();

        // fetching all user tasks
        $result_commande = $CommandeRepo->getCommandeById($cmd_id);

        $response["error"] = false;
        $response["commande"] = $result_commande;

        $PatientRepo = new PatientRepo();
        $result_patient = $PatientRepo->getPatientById( $result_commande['patientId'] );
        $response["patient"] = $result_patient;

        echoRespnse(200, $response);
    });


/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/patient', 'authenticate', function() use ($app) {
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);
            // check for required params
            verifyRequiredParams(array(
                'nom',            'prenom', 'genre',     'jourNaissance', 'moisNaissance', 
                'anneeNaissance', 'telephone', 'paysId',        'typePieceFournitId'), $postArray );

            $response = array();

            //print_r($postArray); 

            // reading post params
            $nom                = $postArray['nom'];
            $prenom             = $postArray['prenom'];
            $genre              = $postArray['genre'];

            //echo $genre;
            $jourNaissance      = $postArray['jourNaissance'];
            $moisNaissance      = $postArray['moisNaissance'];
            $anneeNaissance     = $postArray['anneeNaissance'];
            //$email              = $postArray['email'];
            $telephone          = $postArray['telephone'];
            $paysId             = $postArray['paysId'];

            if (!isset($postArray['email']) || strlen(trim($postArray['email'])) <= 0){
                $email            = "";
            }
            else{
                $email            = $postArray['email'];
            }

            if (!isset($postArray['adresse']) || strlen(trim($postArray['adresse'])) <= 0){
                $adresse            = "";
            }
            else{
                $adresse            = $postArray['adresse'];
            }


            $typePieceFournitId = $postArray['typePieceFournitId'];
            if (!isset($postArray['numeroPiece']) || strlen(trim($postArray['numeroPiece'])) <= 0){
                $numeroPiece        = "";
            }
            else{
                $numeroPiece        = $postArray['numeroPiece'];
            }

            global $user_id;
            $db = new DbHandler();

            $nextKey = $db->getNextKey('Patient');
            $numeroPatient = $db->GenerateKey( 'Patient', '' , $nextKey );

            $PatientRepo = new PatientRepo();
            // creating new task
            $patient_id = $PatientRepo->create( 
                $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
                $anneeNaissance, $email, $telephone, $paysId, $adresse, 
                $typePieceFournitId, $numeroPiece, $user_id, $numeroPatient
            );
            //echo 'OK';

            if ($patient_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Patient enregistré!";

                
                $response["patient"] = $PatientRepo->getPatientById( $patient_id );
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/patient', 'authenticate', function() use($app) {
            //$patient_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);

            //print_r($postArray);

            // check for required params
            verifyRequiredParams(array(
                'id', 'nom', 'prenom', 'genre', 'jourNaissance', 'moisNaissance', 
                'anneeNaissance', 'telephone', 'paysId', 'typePieceFournitId'), $postArray );

            $response = array();

            //print_r($postArray); 

            // reading post params
            $id                 = $postArray['id'];
            $nom                = $postArray['nom'];
            $prenom             = $postArray['prenom'];
            $genre              = $postArray['genre'];

            //echo $genre;
            $jourNaissance      = $postArray['jourNaissance'];
            $moisNaissance      = $postArray['moisNaissance'];
            $anneeNaissance     = $postArray['anneeNaissance'];
            //$email              = $postArray['email'];
            $telephone          = $postArray['telephone'];
            $paysId             = $postArray['paysId'];

            if (!isset($postArray['email']) || strlen(trim($postArray['email'])) <= 0){
                $email            = "";
            }
            else{
                $email            = $postArray['email'];
            }

            if (!isset($postArray['adresse']) || strlen(trim($postArray['adresse'])) <= 0){
                $adresse            = "";
            }
            else{
                $adresse            = $postArray['adresse'];
            }


            $typePieceFournitId = $postArray['typePieceFournitId'];
            if (!isset($postArray['numeroPiece']) || strlen(trim($postArray['numeroPiece'])) <= 0){
                $numeroPiece        = "";
            }
            else{
                $numeroPiece        = $postArray['numeroPiece'];
            }

            global $user_id;
            $PatientRepo = new PatientRepo();

            // creating new task
            $result = $PatientRepo->update( 
                $id, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id
            );
            //echo 'OK';

            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Information(s) du patient modifiée(s)!";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Acune(s) modification(s) effectuée(s)!";
            }
            echoRespnse(200, $response);
        });



 /*
 * ------------------------ METHODES COMMANDES WITH AUTHENTICATION ------------------------
 */

 /**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/societe/editconfig', 'authenticate', function() {
            //global $user_id;
            $response = array();
            $db = new SocieteRepo();

            $response["error"]    = false;
            
            // fetching all typePatients
            $result_typeContrats = $db->getAllTypeContrat();
            $response["typeContrats"]  = array();
            $response["typeContrats"]  = $result_typeContrats;

            echoRespnse(200, $response);
        });

$app->get('/societe', 'authenticate', function() use ($app) {
        //global $user_id;

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

        $offset = ($page * $limit) + 1; //calculate what data you want
        //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
        //page 2 -> 1 * 10 -> get data from row 10 to row 19

        $db = new SocieteRepo();
        
        $response = array();
        $response["error"]    = false;

        // fetching all user tasks
        $result_societes = $db->getSocietes($limit, $offset, $searchText);
        $response["societes"] = $result_societes;

        $result_count = $db->getTableCount( 'societe' );
        $response["totalCount"] = $result_count;
        echoRespnse(200, $response);
    });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/societeById', 'authenticate', function() use ($app) {
        //global $user_id;
        $societe_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $db = new SocieteRepo();

        // fetching all user tasks
        $result_societe = $db->getSocieteById($societe_id);

        $response["error"] = false;
        $response["societe"] = $result_societe;
        echoRespnse(200, $response);
    });

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/societe', 'authenticate', function() use ($app) {
            // check for required params
            $postArray = json_decode($app->request()->getBody(), true);
            // check for required params
            verifyRequiredParams(array(
                'societeLib'), $postArray );

            $response = array();

            //print_r($postArray); 

            // reading post params
            $societeLib = $postArray['societeLib'];

            global $user_id;
            $db = new SocieteRepo();

            // creating new task
            $societe_id = $db->createSociete( $societeLib, $user_id );
            //echo 'OK';

            if ($societe_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Société enregistré!";
                $response["societe_id"] = $societe_id;
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->put('/societe', 'authenticate', function() use ($app) {
    $societeId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'societeLib'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $societeLib = $postArray['societeLib'];

    global $user_id;
    $db = new SocieteRepo();

    // creating new task
    $result= $db->updateSociete( $societeId, $societeLib, $user_id );
    //echo 'OK';

    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Société modifiée!";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Erreur de modification!";
    }
    echoRespnse(200, $response);          
});

 /**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/patient/cmdconfig', 'authenticate', function() {
    $response = array();
    $ConfigRepo = new ConfigRepo(); 
    $response = $ConfigRepo->getCommandeConfig();
    echoRespnse(200, $response);
});


/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/patientCmd', 'authenticate', function() use ($app) {
        //global $user_id;
        $tcontratid = (isset($_GET['tcontratid']) && $_GET['tcontratid'] > 0) ? $_GET['tcontratid'] : 0;
        $analyseid = (isset($_GET['analyseid']) && $_GET['analyseid'] > 0) ? $_GET['analyseid'] : 0;
        $employeeid = (isset($_GET['employeeid']) && $_GET['employeeid'] > 0) ? $_GET['employeeid'] : 0;

        $response = array();
        $AnalyseRepo = new AnalyseRepo();

        // fetching all user tasks
        $result_commandeAnalyse = $AnalyseRepo->getAnalyseDetails($tcontratid, $analyseid, $employeeid);

        $response["error"] = false;
        $response["commandeAnalyse"] = $result_commandeAnalyse;

        echoRespnse(200, $response);
    });


/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/patientCmd', 'authenticate', function() use ($app) {
    global $user_id;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);

    //print_r($postArray); 


    // check for required params
    verifyRequiredParams(array(
        'patientId','typePatientId', 'societeId', 'contratId', 'nomMedecin', 'telephoneMedecin', 
        'serviceMedecinId', 'etablissementSanitaireId', 'ownerId', 'currentStateId', 'submitterId'), $postArray );

    $response = array();

    global $user_id;
    $db = new CommandeRepo();

    $typePatientId = $postArray['typePatientId'];
    $societeId = $postArray['societeId'];
    $contratId = $postArray['contratId'];
    $nomMedecin = $postArray['nomMedecin'];
    $telephoneMedecin = $postArray['telephoneMedecin'];
    $serviceMedecinId = $postArray['serviceMedecinId'];
    $etablissementSanitaireId = $postArray['etablissementSanitaireId'];
    $ownerId = $postArray['ownerId'];
    $totalNetAPayer = 0;
    $totalNbreB = 0;
    $patientId = $postArray['patientId'];
    $currentStateId = $postArray['currentStateId'];
    $submitterId = $postArray['submitterId'];
    if (!isset($postArray['numeroAssurance']) || strlen(trim($postArray['numeroAssurance'])) <= 0){
        $numeroAssurance        = "";
    }
    else{
        $numeroAssurance        = $postArray['numeroAssurance'];
    }

    $nextKey = $db->getNextKey('Commande');
    $numeroCommande = $db->GenerateKey( 'Commande', '' , $nextKey );

    //$analyse_list = $postArray['analyse_list'];

    

    // creating new task
    $task_id = $db->create($user_id, $patientId, $typePatientId, $societeId, $contratId, $nomMedecin, $telephoneMedecin, $serviceMedecinId, $etablissementSanitaireId, $ownerId, $totalNetAPayer, $totalNbreB, $currentStateId, 
        $submitterId, $numeroCommande, $numeroAssurance);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }           
});


/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->put('/patientCmd', 'authenticate', function() use ($app) {
    global $user_id;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);

    //print_r($postArray); 


    // check for required params
    verifyRequiredParams(array(
        'id', 'patientId','typePatientId', 'societeId', 'contratId', 'nomMedecin', 'telephoneMedecin', 
        'serviceMedecinId', 'etablissementSanitaireId', 'ownerId', 'totalNetAPayer',
        'totalNbreB', 'currentStateId', 'submitterId'), $postArray );

    $response = array();

    global $user_id;
    $db = new CommandeRepo();

    $id = $postArray['id'];
    $typePatientId = $postArray['typePatientId'];
    $societeId = $postArray['societeId'];
    $contratId = $postArray['contratId'];
    $nomMedecin = $postArray['nomMedecin'];
    $telephoneMedecin = $postArray['telephoneMedecin'];
    $serviceMedecinId = $postArray['serviceMedecinId'];
    $etablissementSanitaireId = $postArray['etablissementSanitaireId'];
    $ownerId = $postArray['ownerId'];
    $patientId = $postArray['patientId'];
    $currentStateId = $postArray['currentStateId'];
    $submitterId = $postArray['submitterId'];

    if (!isset($postArray['numeroAssurance']) || strlen(trim($postArray['numeroAssurance'])) <= 0){
        $numeroAssurance        = "";
    }
    else{
        $numeroAssurance        = $postArray['numeroAssurance'];
    }

    //$analyse_list = $postArray['analyse_list'];

    

    // creating new task
    $task_id = $db->update($user_id, $id, $patientId, $typePatientId, $societeId, $contratId, $nomMedecin, $telephoneMedecin, $serviceMedecinId, $etablissementSanitaireId, $ownerId, $currentStateId, 
        $submitterId, $numeroAssurance);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }           
});

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/cmdanalyse', 'authenticate', function() use ($app) {
    global $user_id;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);

    //print_r($postArray); 


    // check for required params
    verifyRequiredParams(array(
        'patientId','typePatientId', 'societeId', 'contratId', 'nomMedecin', 'telephoneMedecin', 
        'serviceMedecinId', 'etablissementSanitaireId', 'ownerId', 'currentStateId', 'submitterId'), $postArray );

    $response = array();

    global $user_id;
    $db = new CommandeRepo();

    $typePatientId = $postArray['typePatientId'];
    $societeId = $postArray['societeId'];
    $contratId = $postArray['contratId'];
    $nomMedecin = $postArray['nomMedecin'];
    $telephoneMedecin = $postArray['telephoneMedecin'];
    $serviceMedecinId = $postArray['serviceMedecinId'];
    $etablissementSanitaireId = $postArray['etablissementSanitaireId'];
    $ownerId = $postArray['ownerId'];
    $totalNetAPayer = 0;
    $totalNbreB = 0;
    $patientId = $postArray['patientId'];
    $currentStateId = $postArray['currentStateId'];
    $submitterId = $postArray['submitterId'];
    if (!isset($postArray['numeroAssurance']) || strlen(trim($postArray['numeroAssurance'])) <= 0){
        $numeroAssurance        = "";
    }
    else{
        $numeroAssurance        = $postArray['numeroAssurance'];
    }

    $nextKey = $db->getNextKey('Commande');
    $numeroCommande = $db->GenerateKey( 'Commande', '' , $nextKey );

    //$analyse_list = $postArray['analyse_list'];

    

    // creating new task
    $task_id = $db->create($user_id, $patientId, $typePatientId, $societeId, $contratId, $nomMedecin, $telephoneMedecin, $serviceMedecinId, $etablissementSanitaireId, $ownerId, $totalNetAPayer, $totalNbreB, $currentStateId, 
        $submitterId, $numeroCommande, $numeroAssurance);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }           
});


/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->put('/cmdanalyse', 'authenticate', function() use ($app) {
    global $user_id;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);

    //print_r($postArray); 


    // check for required params
    verifyRequiredParams(array(
        'id', 'patientId','typePatientId', 'societeId', 'contratId', 'nomMedecin', 'telephoneMedecin', 
        'serviceMedecinId', 'etablissementSanitaireId', 'ownerId', 'totalNetAPayer',
        'totalNbreB', 'currentStateId', 'submitterId'), $postArray );

    $response = array();

    global $user_id;
    $db = new CommandeRepo();

    $id = $postArray['id'];
    $typePatientId = $postArray['typePatientId'];
    $societeId = $postArray['societeId'];
    $contratId = $postArray['contratId'];
    $nomMedecin = $postArray['nomMedecin'];
    $telephoneMedecin = $postArray['telephoneMedecin'];
    $serviceMedecinId = $postArray['serviceMedecinId'];
    $etablissementSanitaireId = $postArray['etablissementSanitaireId'];
    $ownerId = $postArray['ownerId'];
    $patientId = $postArray['patientId'];
    $currentStateId = $postArray['currentStateId'];
    $submitterId = $postArray['submitterId'];

    if (!isset($postArray['numeroAssurance']) || strlen(trim($postArray['numeroAssurance'])) <= 0){
        $numeroAssurance        = "";
    }
    else{
        $numeroAssurance        = $postArray['numeroAssurance'];
    }

    //$analyse_list = $postArray['analyse_list'];

    

    // creating new task
    $task_id = $db->update($user_id, $id, $patientId, $typePatientId, $societeId, $contratId, $nomMedecin, $telephoneMedecin, $serviceMedecinId, $etablissementSanitaireId, $ownerId, $currentStateId, 
        $submitterId, $numeroAssurance);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }           
});

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/valideCommande', 'authenticate', function() use($app) {
    //global $user_id;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);

    $cmd_id = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;



            // check for required params
            //verifyRequiredParams(array('id'), $postArray);

            global $user_id;            
            //$cmd_id = $postArray['id'];

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->valideCommande($user_id, $cmd_id);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
                
                $result_commande = $db->getCommandeById($cmd_id);
                $response["commande"] = $result_commande;

                $result_patient = $db->getPatientById( $result_commande['patientId'] );
                $response["patient"] = $result_patient;

            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });


 /*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/stats', 'authenticate', function() {
            //global $user_id;
            $response = array();
            $db = new StatistiqueRepo();

            $response["error"]    = false;
            
            // fetching all user tasks
            $result_patient = $db->getPatientStat();
            $response["patient"]  = array();
            $response["patient"]  = $result_patient;


            // fetching all user tasks
            $result_commande = $db->getCommandeStat();
            $response["commande"]  = array();
            $response["commande"]  = $result_commande;

            echoRespnse(200, $response);
        });



/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });


/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/departements', 'authenticate', function() {
    $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
    $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

    $offset = ($page * $limit) + 1; //calculate what data you want
    //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
    //page 2 -> 1 * 10 -> get data from row 10 to row 19

    $response = array();
    $db = new DepartementRepo();

    // fetching all user tasks
    $departements = $db->readAll($limit, $offset, $searchText);
    $response["error"] = false;
    $response["departements"] = $departements;

    $result_count = count( $departements);
    $response["totalCount"] = $result_count;
    echoRespnse(200, $response);
});


 /**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/departement/editconfig', 'authenticate', function() {
            //global $user_id;
            $response = array();
            $db = new SocieteRepo();

            $response["error"]    = false;
            
            // fetching all typePatients
            $result_typeContrats = $db->getAllTypeContrat();
            $response["typeContrats"]  = array();
            $response["typeContrats"]  = $result_typeContrats;

            echoRespnse(200, $response);
        });


/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/departement', 'authenticate', function() use ($app) {
        //global $user_id;
        $departementId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $db = new DepartementRepo();

        // fetching all user tasks
        $departement = $db->readById( $departementId );

        $response["error"] = false;
        $response["departement"] = $departement;

        echoRespnse(200, $response);
    });


/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/departement', 'authenticate', function() use ($app) {
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'departementLib'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $departementLib = $postArray['departementLib'];
    $reference      = $postArray['reference'];

    global $user_id;
    $db = new DepartementRepo();

    // creating new task
    $departement_id = $db->createDepartement( $departementLib, $reference, $user_id );
    //echo 'OK';

    if ($departement_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Departement enregistré!";
        $response["departement_id"] = $departement_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }            
});



/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->put('/departement', 'authenticate', function() use ($app) {
    $departementId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'departementLib'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $departementLib = $postArray['departementLib'];
    $reference      = $postArray['reference'];

    global $user_id;
    $db = new DepartementRepo();

    // creating new task
    $result= $db->updateDepartement($departementId, $departementLib, $reference, $user_id);
    //echo 'OK';

    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Departement modifiée!";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Erreur de modification!";
    }
    echoRespnse(200, $response);          
});


/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/analyses', 'authenticate', function() {
    $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
    $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

    $offset = ($page * $limit) + 1; //calculate what data you want
    //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
    //page 2 -> 1 * 10 -> get data from row 10 to row 19

    $response = array();
    $AnalyseRepo = new AnalyseRepo();

    // fetching all user tasks
    $analyses = $AnalyseRepo->readAll($limit, $offset, $searchText);
    $response["error"] = false;
    $response["analyses"] = $analyses;

    $result_count = $AnalyseRepo->getTableCount( 'analyse' );
    $response["totalCount"] = $result_count;
    echoRespnse(200, $response);
});

 /**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/analyse/config', 'authenticate', function() {
        //global $user_id;
        $response = array();
        $response["error"]    = false;
        
        // fetching all Departement
        $DepartementRepo = new DepartementRepo();
        $result_departements = $DepartementRepo->read();
        $response["departements"]  = array();
        $response["departements"]  = $result_departements;

        // fetching all Unite
        $UniteRepo = new UniteRepo();
        $unites = $UniteRepo->read();
        $response["unites"]  = array();
        $response["unites"]  = $unites;

        // fetching all Unite
        $LaboratoireRepo = new LaboratoireRepo();
        $laboratoires = $LaboratoireRepo->read();
        $response["laboratoires"]  = array();
        $response["laboratoires"]  = $laboratoires;


        // fetching all Unite
        $EchantillonRepo = new EchantillonRepo();
        $echantillons = $EchantillonRepo->read();
        $response["echantillons"]  = array();
        $response["echantillons"]  = $echantillons;

        echoRespnse(200, $response);
    });


/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/analyse', 'authenticate', function() use ($app) {
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'laboratoireId', 'echantillonId', 'analyseLib', 'nbreB', 'nbreJourRetrait' ), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $laboratoireId   = $postArray['laboratoireId'];
    $echantillonId   = $postArray['echantillonId'];
    $analyseLib      = $postArray['analyseLib'];
    $nbreB           = $postArray['nbreB'];
    $nbreJourRetrait = $postArray['nbreJourRetrait'];

    global $user_id;
    $AnalyseRepo = new AnalyseRepo();

    // creating new task
    $analyse_id = $AnalyseRepo->create( $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $user_id );
    //echo 'OK';

    if ($analyse_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Analyse enregistré!";
        $response["analyse_id"] = $analyse_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }            
});

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/analyse', 'authenticate', function() use ($app) {
        //global $user_id;
        $analyseId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $AnalyseRepo = new AnalyseRepo();

        // fetching all user tasks
        $analyse = $AnalyseRepo->readById( $analyseId );

        $response["error"]   = false;
        $response["analyse"] = $analyse;

        echoRespnse(200, $response);
    });

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->put('/analyse', 'authenticate', function() use ($app) {
    $analyseId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
    'laboratoireId', 'echantillonId', 'analyseLib', 'nbreB', 'nbreJourRetrait'), $postArray );

    $response = array(); 

    // reading post params
    $laboratoireId   = $postArray['laboratoireId'];
    $echantillonId   = $postArray['echantillonId'];
    $analyseLib      = $postArray['analyseLib'];
    $nbreB           = $postArray['nbreB'];
    $nbreJourRetrait = $postArray['nbreJourRetrait'];

    global $user_id;
    $AnalyseRepo = new AnalyseRepo();

    // creating new task
    $result= $AnalyseRepo->update( $analyseId, $laboratoireId, $echantillonId, $analyseLib, $nbreB, $nbreJourRetrait, $user_id );
    //echo 'OK';

    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Analyse modifiée!";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Erreur de modification!";
    }
    echoRespnse(200, $response);          
});



/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/unites', 'authenticate', function() {
    $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
    $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

    $offset = ($page * $limit) + 1; //calculate what data you want
    //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
    //page 2 -> 1 * 10 -> get data from row 10 to row 19

    $response = array();
    $UniteRepo = new UniteRepo();

    // fetching all user tasks
    $unites = $UniteRepo->readAll($limit, $offset, $searchText);
    $response["error"] = false;
    $response["unites"] = $unites;

    $result_count = $UniteRepo->getTableCount( 'unite' );
    $response["totalCount"] = $result_count;
    echoRespnse(200, $response);
});

 /**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/unite/config', 'authenticate', function() {
        //global $user_id;
        $response = array();
        $DepartementRepo = new DepartementRepo();

        $response["error"]    = false;
        
        // fetching all typePatients
        $result_departements = $DepartementRepo->read();
        $response["departements"]  = array();
        $response["departements"]  = $result_departements;

        echoRespnse(200, $response);
    });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/unite', 'authenticate', function() use ($app) {
        //global $user_id;
        $uniteId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $UniteRepo = new UniteRepo();

        // fetching all user tasks
        $unite = $UniteRepo->readById( $uniteId );

        $response["error"] = false;
        $response["unite"] = $unite;

        echoRespnse(200, $response);
    });

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/unite', 'authenticate', function() use ($app) {
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'uniteLib', 'departementId'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $uniteLib      = $postArray['uniteLib'];
    $departementId = $postArray['departementId'];

    global $user_id;
    $UniteRepo = new UniteRepo();

    // creating new task
    $unite_id = $UniteRepo->create( $uniteLib, $departementId, $user_id );
    //echo 'OK';

    if ($unite_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Unité enregistré!";
        $response["unite_id"] = $unite_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }            
});

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->put('/unite', 'authenticate', function() use ($app) {
    $uniteId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'uniteLib', 'departementId'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $uniteLib      = $postArray['uniteLib'];
    $departementId = $postArray['departementId'];

    global $user_id;
    $UniteRepo = new UniteRepo();

    // creating new task
    $result= $UniteRepo->update( $uniteId, $uniteLib, $departementId, $user_id );
    //echo 'OK';

    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Unité modifiée!";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Erreur de modification!";
    }
    echoRespnse(200, $response);          
});


/*LABORATOIRE*/
/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/laboratoires', 'authenticate', function() {
    $page = (isset($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 50;
    $searchText = isset($_GET['searchText']) ? $_GET['searchText'] : "_";

    $offset = ($page * $limit) + 1; //calculate what data you want
    //page 1 -> 0 * 10 -> get data from row 0 (first entry) to row 9
    //page 2 -> 1 * 10 -> get data from row 10 to row 19

    $response = array();
    $LaboratoireRepo = new LaboratoireRepo();

    // fetching all user tasks
    $labs = $LaboratoireRepo->readAll($limit, $offset, $searchText);
    $response["error"] = false;
    $response["laboratoires"] = $labs;

    $result_count = $LaboratoireRepo->getTableCount( 'laboratoire' );
    $response["totalCount"] = $result_count;
    echoRespnse(200, $response);
});

 /**
 * Listing all stats for the dashboard
 * method GET
 * url /stats          
 */
$app->get('/laboratoire/config', 'authenticate', function() {
        //global $user_id;
        $response = array();
        $response["error"]    = false;
        
        // fetching all Departement
        $DepartementRepo = new DepartementRepo();
        $result_departements = $DepartementRepo->read();
        $response["departements"]  = array();
        $response["departements"]  = $result_departements;

        // fetching all Unite
        $UniteRepo = new UniteRepo();
        $unites = $UniteRepo->read();
        $response["unites"]  = array();
        $response["unites"]  = $unites;

        echoRespnse(200, $response);
    });

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/laboratoire', 'authenticate', function() use ($app) {
        //global $user_id;
        $laboratoireId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $LaboratoireRepo = new LaboratoireRepo();

        // fetching all user tasks
        $laboratoire = $LaboratoireRepo->readById( $laboratoireId );

        $response["error"] = false;
        $response["laboratoire"] = $laboratoire;

        echoRespnse(200, $response);
    });

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->post('/laboratoire', 'authenticate', function() use ($app) {
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'laboratoireLib', 'uniteId'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $uniteId       = $postArray['uniteId'];
    $laboratoireLib = $postArray['laboratoireLib'];

    global $user_id;
    $LaboratoireRepo = new LaboratoireRepo();

    // creating new task
    $unite_id = $LaboratoireRepo->create( $laboratoireLib, $uniteId, $user_id );
    //echo 'OK';

    if ($unite_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Unité enregistré!";
        $response["unite_id"] = $unite_id;
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }            
});

/**
 * Creating new patient in db
 * method POST
 * params - nom, prenom, genre, jourNaissance, moisNaissance, anneeNaissance, email
 * telephone, paysId, typePieceId, numeroPiece, adresse
 * url - /patient/
 */
$app->put('/laboratoire', 'authenticate', function() use ($app) {
    $laboratoireId = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'laboratoireLib', 'uniteId'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $laboratoireLib = $postArray['laboratoireLib'];
    $uniteId        = $postArray['uniteId'];

    global $user_id;
    $LaboratoireRepo = new LaboratoireRepo();

    // creating new task
    $result= $LaboratoireRepo->update( $laboratoireId, $laboratoireLib, $uniteId, $user_id );
    //echo 'OK';

    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Laboratoire modifiée!";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Erreur de modification!";
    }
    echoRespnse(200, $response);          
});

/**
 * get patient by id
 * method GET
 * url /tasks          
 */
$app->get('/commandeanalyse', 'authenticate', function() use ($app) {
        //global $user_id;
        $commandeid = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

        $response = array();
        $response["error"] = false;

        $CommandeRepo = new CommandeRepo();
        $commande = $CommandeRepo->getCommandeById( $commandeid );
        $response["commande"] = $commande;

        $CommandeAnalyseRepo = new CommandeAnalyseRepo();
        $commandeanalyse = $CommandeAnalyseRepo->readByCommandeId( $commandeid );
        $response["commandeanalyses"] = $commandeanalyse;

        echoRespnse(200, $response);
    });


/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/commandeanalyse', 'authenticate', function() use ($app) {
    // check for required params
    $postArray = json_decode($app->request()->getBody(), true);
    // check for required params
    verifyRequiredParams(array(
        'analyse_id', 'commande_id', 'type_contrat_id', 'employee_id'), $postArray );

    $response = array();

    //print_r($postArray); 

    // reading post params
    $commande_id = $postArray['commande_id'];
    $type_contrat_id = $postArray['type_contrat_id'];
    $employee_id = $postArray['employee_id'];
    $analyse_id  = $postArray['analyse_id'];

    /*echo  $commande_id . "/";
    echo  $type_contrat_id;
    echo  $employee_id;*/

    $analyseRepo = new AnalyseRepo();
    $analyse = $analyseRepo->getAnalyseDetails($type_contrat_id, $analyse_id, $employee_id);

    global $user_id;
    $commandeAnalyseRepo = new CommandeAnalyseRepo();
    $new_id = $commandeAnalyseRepo->create($commande_id, $analyse, $user_id);


    if ($new_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Analyse enregistré!";

        $CommandeRepo = new CommandeRepo();
        $commande = $CommandeRepo->getCommandeById( $commande_id );
        $response["commande"] = $commande;

        $response["commandeanalyse"] = $commandeAnalyseRepo->readOne( $new_id );
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }      
});

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/commandeanalyse', 'authenticate', function() use($app) {
    global $user_id;
    $commandeanalyseid = (isset($_GET['id']) && $_GET['id'] > 0) ? $_GET['id'] : 0;

    $response = array();
    $commandeAnalyseRepo = new CommandeAnalyseRepo();
    $commandeanalyse = $commandeAnalyseRepo->readOne( $commandeanalyseid );
    $response["commandeanalyseId"] = $commandeanalyseid;

    


    $result = $commandeAnalyseRepo->delete($commandeanalyseid);
    if ($result) {
        // task deleted successfully
        $response["error"] = false;
        $response["message"] = "Task deleted succesfully";

        $CommandeRepo = new CommandeRepo();
        $commande = $CommandeRepo->getCommandeById( $commandeanalyse["commandeId"] );
        $response["commande"] = $commande;

    } else {
        // task failed to delete
        $response["error"] = true;
        $response["message"] = "Task failed to delete. Please try again!";
    }
    echoRespnse(200, $response);
});



/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields, $request_params) {
    $error = false;
    $error_fields = "";
    //$request_params = array();
    //$request_params = $_REQUEST;
    // Handling PUT request params
    /*if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }*/
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Champ(s) obligatoire(s) ' . substr($error_fields, 0, -2) . ' ';
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Invalide email adresse';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>