<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createAccount($username, $password, $employeeId, $roleId, $user_id) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($username)) {
            // Generating password hash
            $password_hash = PassHash::encrypt($password);

            // Generating API key
            $api_key = $this->generateApiKey();
            $status = 1;

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(username, password_hash, api_key, status, employeeid, insert_userid, update_userid) values(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $username, $password_hash, $api_key, $status, $employeeId, $user_id, $user_id);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                $new_account_id = $this->conn->insert_id;
                $res = $this->createUserRole($user_id, $roleId, $new_account_id);
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createUserRole($user_id, $roleId, $accountId) {
        $stmt = $this->conn->prepare("INSERT INTO entroleuseraccount(ENTRoleId, ENTUserAccountId, InsertENTUserAccountId, UpdateENTUserAccountId) VALUES(?,?,?,?)");
        $stmt->bind_param("iiii", $roleId, $accountId, $user_id, $user_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteUserRole( $roleId, $accountId ) {
        $stmt = $this->conn->prepare("DELETE FROM entroleuseraccount WHERE ENTRoleId=? AND ENTUserAccountId=?");
        $stmt->bind_param("ii", $roleId, $accountId);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function updateUserRole($roleId, $accountId, $user_id) {
        $userRoleId = $this->getRoleuserId($accountId);
        // insert query
        $stmt = $this->conn->prepare("UPDATE entroleuseraccount set ENTRoleId=?, ENTUserAccountId=?, UpdateENTUserAccountId=? WHERE ENTRoleUserAccountId=?");
        $stmt->bind_param("iiii", $roleId, $accountId, $user_id, $userRoleId);

        $result = $stmt->execute();

        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getRoleuserId($accountId) {
        $stmt = $this->conn->prepare("SELECT ENTRoleUserAccountId FROM entroleuseraccount WHERE ENTUserAccountId=?");
        $stmt->bind_param("i", $accountId);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();
            return $id;
        } else {
            return NULL;
        }
    }

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function updateAccount($account_id, $username, $password, $employeeId, $roleId, $user_id) {
        require_once 'PassHash.php';
        //$pass = PassHash::encrypt("pass12345");
        //echo $pass;
        $response = array();

        // First check if user already existed in db
        
        // Generating password hash
        $password_hash = PassHash::encrypt($password);
        // insert query
        $stmt = $this->conn->prepare("UPDATE users set username=?, password_hash=?, update_userid=? WHERE id=? AND employeeid=?");
        $stmt->bind_param("sssss", $username, $password_hash, $user_id,  $account_id, $employeeId);

        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // User successfully inserted
            return USER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return USER_CREATE_FAILED;
        }

        

        // Check for successful insertion

        return $response;
    }

    

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($username) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    

    

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserByEmployeeId($employee_id) {
        require_once 'PassHash.php';
        $stmt = $this->conn->prepare("SELECT
        users.id,
        users.username,
        users.password_hash,
        users.employeeid,
        users.`status`,
        entrole.RoleName,
        entrole.RoleId
        FROM
        users
        LEFT OUTER JOIN entroleuseraccount ON entroleuseraccount.ENTUserAccountId = users.id
        LEFT OUTER JOIN entrole ON entrole.RoleId = entroleuseraccount.ENTRoleId
        WHERE
        users.employeeid = ?");
        $stmt->bind_param("i", $employee_id);
        if ($stmt->execute()) {
            $stmt->bind_result($id, $username, $password_hash, $employeeId, $status, $roleName, $roleId);
            $stmt->fetch();

            $password = PassHash::decrypt($password_hash);

            $response = array();
            $response['id']         = $id;
            $response['userName']   = $username;
            $response['employeeId'] = $employee_id;
            $response['password']   = $password;
            $response['status']     = $status;
            $response['roleName']   = $roleName;
            $response['roleId']     = $roleId;
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $response;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getAllEmployee( $limit, $offset, $searchText ) {
        $stmt = $this->conn->prepare("SELECT * FROM `employee`
        GROUP BY
        EmployeeId order by EmployeeId ASC LIMIT ? OFFSET ?");
        $num_rows = $this->getTableCount( 'employee' );
        if($num_rows < 50){
            $limit = $num_rows;
            $offset = 0;
        }
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]            = $task["EmployeeId"];
            $tmp["fullName"]      = $task["Nom"] . ' ' . $task["Prenom"];
            $tmp["genre"]         = $task["Genre"];
            $tmp["fullBirthDate"] = $task["AnneeNaissance"] . ' ' . $task["MoisNaissance"] . ' ' . $task["JourNaissance"];
            $tmp["telephone"]     = $task["Telephone"];
            $tmp["insertDate"]    = $task["InsertDate"];
            array_push($response, $tmp);
        }

        $stmt->close();

        return $response;
    }


    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getEmployeeById($emp_id) {
        $stmt = $this->conn->prepare("SELECT
        employee.EmployeeId,
        employee.Nom,
        employee.Prenom,
        employee.Genre,
        employee.Telephone,
        employee.Adresse,
        employee.InsertDate,
        employee.Email,
        employee.PaysId,
        employee.JourNaissance,
        employee.MoisNaissance,
        employee.AnneeNaissance,
        employee.TitreId,
        employee.TypePieceFournitId,
        employee.numeroPiece,
        employee.LaboratoireId
        FROM `employee`
        WHERE
        employee.EmployeeId = ?");
        $stmt->bind_param("i", $emp_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $nom, $prenom, $genre, $telephone, $adresse, $insertDate, $email, $paysId, $jour, $mois, $annee, $titreId, $typePieceFournitId, $numeroPiece, $labId);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"]         = $id;
            $res["nom"]        = $nom;
            $res["prenom"]     = $prenom;
            $res["fullName"]   = $nom . ' ' . $prenom;
            $res["genre"]      = $genre;
            $res["telephone"]  = $telephone;
            $res["adresse"]    = $adresse;
            $res["email"]      = $email;
            $res["paysId"]     = $paysId;
            $res["jourNaissance"]  = $jour;
            $res["moisNaissance"]  = $mois;
            $res["anneeNaissance"] = $annee;
            $res["titreId"]        = $titreId;
            $res["typePieceFournitId"]      = $typePieceFournitId;
            $res["numeroPiece"]      = $numeroPiece;
            $res["laboratoireId"]      = $labId;
            $res["insertDate"] = $insertDate;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getAllRole( $limit, $offset, $searchText ) {
        $stmt = $this->conn->prepare("SELECT
        entrole.RoleName,
        entrole.RoleId,
        entrole.InsertDate,
        Count(entrolecapability.ENTRoleCapabilityId) as totalCapabilities
        FROM
        entrole
        INNER JOIN entrolecapability ON entrolecapability.ENTRoleId = entrole.RoleId
        GROUP BY
        entrole.RoleId order by RoleId ASC LIMIT ? OFFSET ?");
        $num_rows = $this->getTableCount( 'entrole' );
        if($num_rows < 50){
            $limit = $num_rows;
            $offset = 0;
        }
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]         = $task["RoleId"];
            $tmp["roleName"]   = $task["RoleName"];
            $tmp["totalCapabilities"] = $task["totalCapabilities"];
            $tmp["insertDate"] = $task["InsertDate"];
            array_push($response, $tmp);
        }

        $stmt->close();

        return $response;
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getRoleById($role_id) {
        $stmt = $this->conn->prepare("SELECT RoleId, RoleName, InsertDate FROM entrole WHERE entrole.RoleId = ?");
        $stmt->bind_param("i", $role_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $roleName, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"]         = $id;
            $res["roleName"]   = $roleName;
            $res["insertDate"] = $insertDate;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }


    /**
     * Fetching single patient
     * @param integer $patient_id id
     */
    public function getCapabilityByRoleId( $role_id ) {

        $stmt = $this->conn->prepare("SELECT * FROM `entrolecapability` WHERE entrolecapability.ENTRoleId = ?");
        $stmt->bind_param( "i", $role_id );
        $stmt->execute();

        $tasks = $stmt->get_result();

        $response = array();

        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]           = $task["ENTRoleCapabilityId"];
            $tmp["capabilityId"] = $task["ENTCapabilityId"];
            $tmp["accessFlag"]   = $task["AccessFlag"];
            $tmp["insertDate"]   = $task["InsertDate"];
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

    




    /* ------------- `patient` table method ------------------ */

    


    /**
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function getAllRoles() {
        
        $stmt = $this->conn->prepare("SELECT * FROM entrole");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["RoleId"];
            $tmp["roleName"] = $task["RoleName"];
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
    public function getAllTitre() {
        
        $stmt = $this->conn->prepare("SELECT * FROM titre");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["titreId"] = $task["TitreId"];
            $tmp["titreLib"] = $task["TitreLib"];
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
    public function getAllCapabilities() {
        
        $stmt = $this->conn->prepare("SELECT * FROM entcapability");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["ENTCapabilityId"];
            $tmp["capapilityName"] = $task["CapabilityName"];
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
    public function getAllContrats() {

        $stmt = $this->conn->prepare("SELECT * FROM contrat");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["ContratId"];
            $tmp["contratLib"] = $task["ContratLib"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }


    


    


    


    


    



    

    

    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getTotalPatient() {
        $stmt = $this->conn->prepare("SELECT
        Count(patient.PatientId) as TotalPatient
        FROM `patient`");
        $stmt->execute();
        $stmt->bind_result( $total);
        // TODO
        // $task = $stmt->get_result()->fetch_assoc();
        $stmt->fetch();
        $stmt->close();
        return $total;
    }


    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getTotalCommande() {
        $stmt = $this->conn->prepare("SELECT
        Count(Id) as TotalCommande
        FROM commande");
        $stmt->execute();
        $stmt->bind_result( $total);
        // TODO
        // $task = $stmt->get_result()->fetch_assoc();
        $stmt->fetch();
        $stmt->close();
        return $total;
    }

    

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createPatient( 
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
    public function updatePatient($id, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
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
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createEmployee( 
            $titreId, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id
        ) {
        $stmt = $this->conn->prepare("INSERT INTO employee(TitreId, Nom, Prenom, Genre, JourNaissance, MoisNaissance, AnneeNaissance, Email, Telephone, PaysId, Adresse, TypePieceFournitId, numeroPiece, InsertENTUserAccountId, UpdateENTUserAccountId) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssssssii", $titreId, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id, $user_id);
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
    public function updateEmployee($id, $titreId, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id) {
        $stmt = $this->conn->prepare("UPDATE employee set TitreId = ?, Nom = ?, Prenom = ?, Genre = ?, JourNaissance = ?, MoisNaissance = ?, AnneeNaissance = ?, Email = ?, Telephone = ?, PaysId = ?, Adresse = ?, TypePieceFournitId = ?, numeroPiece = ?, InsertENTUserAccountId = ?, UpdateENTUserAccountId = ? WHERE EmployeeId = ?");
        $stmt->bind_param("sssssssssssssiis", $titreId, $nom, $prenom, $genre, $jourNaissance, $moisNaissance, 
            $anneeNaissance, $email,  $telephone, $paysId, $adresse, $typePieceFournitId, $numeroPiece, $user_id, $user_id, $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `commande` table method ------------------ */

    


    

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createCommandeAnalyse($commande_id, $analyse, $user_id) {
        $stmt = $this->conn->prepare("INSERT INTO `commandeanalyse` (`AnalyseId`, `NetAPayer`, `MedecinId`, `DateDeRetait`, `CommandeId`, `Forfait`, `TauxReduction`, `NbreB`, InsertENTUserAccountId, UpdateENTUserAccountId) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssss", $analyse['analyseId'], $analyse['netAPayer'], $analyse['medecinId'], $analyse['dateRetrait'], $commande_id, $analyse['forfait'],  $analyse['tauxReduction'], $analyse['nbreB'], $user_id, $user_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteCommandeAnalyse( $commande_id ) {
        $stmt = $this->conn->prepare("DELETE FROM commandeanalyse WHERE (CommandeId=?)");
        $stmt->bind_param("i", $commande_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `users` table method ------------------ */
    
    /* ------------- `commandes` table method ------------------ */
    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function valideCommande($user_id, $id) {
        $stmt = $this->conn->prepare("UPDATE commande SET CurrentStateId='2', UpdateENTUserAccountId=? WHERE (Id=?)");
        $stmt->bind_param("ii", $user_id, $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();

        //get analyses commandes
        $update_commande  = $this->getCommandeById( $id );
        $analyseCommandes = $this->getAnalyseByCmdId($id);
        $laboratoires     = $this->getAllLabs();
        

        foreach ( $laboratoires as $labs ) {
            $facture = array();
        
            $totalAPaye = 0;
            $totalNbreB = 0;
            $factureAnalyse = array();

            foreach ( $analyseCommandes as $analyseCommande ) {
                if($analyseCommande['laboratoireId'] == $labs['id']){
                    $totalAPaye = $totalAPaye + $analyseCommande['netAPayer'];
                    $totalNbreB = $totalNbreB + $analyseCommande['nbreB'];
                    array_push($factureAnalyse, $analyseCommande);
                }
            }

            if(count($factureAnalyse) > 0){
                $facture['commandeId'] = $id;
                $facture['laboratoireId'] = $labs['id'];
                $facture['numeroFacture'] = $this->getNextKey('Facture');
                $facture['netAPayer'] = $totalAPaye;
                $facture['nbreB'] = $totalNbreB;
                $facture['modeDePaiementId'] = 1;
                $facture['facturiereId'] = $update_commande['ownerId'];
                $facture['regisseurId'] = $update_commande['submitterId'];
                $facture['InsertENTUserAccountId'] = $user_id;
                $facture['updateENTUserAccountId'] = $user_id;
                
                $new_fa_id = $this->createFacture(
                    $user_id, 
                    $update_commande['id'], 
                    $facture['numeroFacture'], 
                    $totalAPaye, 
                    $totalNbreB, 
                    $labs['id'], 
                    1, 
                    $update_commande['ownerId'], 
                    $update_commande['submitterId'],
                    $user_id,
                    $user_id
                );

                foreach ($factureAnalyse as $fa) {
                    $this->createFactureAnalyse(
                    $user_id, 
                    $new_fa_id, 
                    $fa['analyseId'], 
                    $fa['forfait'],  
                    $fa['netAPayer'], 
                    $fa['nbreB'], 
                    $fa['dateRetrait'], 
                    $fa["tauxReduction"] );
                }
            }
        }

        

        //get all labs

        return $num_affected_rows > 0;
    }


    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createFacture($user_id, $cmd_id, $numeroFacture, $netAPayer, $nbreB, $laboratoireId, $modeDePaiementId, $facturiereId, $regisseurId) {
        $stmt = $this->conn->prepare("INSERT INTO `facture` (`CommandeId`, `LaboratoireId`, `NumeroFacture`, `NetAPayer`, `NbreB`, `ModeDePaiementId`, `FacturiereId`, `RegisseurId`, `InsertENTUserAccountId`, `UpdateENTUserAccountId`) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssss", $cmd_id, $laboratoireId, $numeroFacture, $netAPayer, $nbreB,  $modeDePaiementId, $facturiereId, $regisseurId, $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            //$res = $this->createUserTask($user_id, $new_task_id);
            return $new_task_id;
            
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createFactureAnalyse(
    $user_id, $factureId, $analyseId, $forfait, $netAPayer, $nbreB, $dateRetrait, $tauxReduction ) {
        $stmt = $this->conn->prepare("INSERT INTO factureanalyse (factureId, AnalyseId, Forfait, NetAPayer, NbreB, DateDeRetrait, InsertENTUserAccountId, UpdateENTUserAccountId, TauxReduction) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssss", $factureId, $analyseId, $forfait, $netAPayer, $nbreB, $dateRetrait, $user_id, $user_id, $tauxReduction);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            //$res = $this->createUserTask($user_id, $new_task_id);
            return $new_task_id;
        } else {
            // task failed to create
            return NULL;
        }
    }

    

    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getTotalFacture() {
        $stmt = $this->conn->prepare("SELECT
        Count(FactureId) as Total
        FROM facture");
        $stmt->execute();
        $stmt->bind_result( $total);
        // TODO
        // $task = $stmt->get_result()->fetch_assoc();
        $stmt->fetch();
        $stmt->close();
        return $total;
    }


    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createRole($user_id, $roleName, $capabilities) {
        $stmt = $this->conn->prepare("INSERT INTO entrole(RoleName, InsertENTUserAccountId, UpdateENTUserAccountId) VALUES(?,?,?)");
        $stmt->bind_param("sii", $roleName, $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_role_id = $this->conn->insert_id;

            foreach ($capabilities as $capability) {
                $res = $this->createRoleCapabilities( $user_id, $new_role_id, $capability );
                if (!$res) {
                    // task created successfully
                    return NULL;
                }
            }

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
    public function updateRole($user_id, $roleId, $roleName, $capabilities) {
        $stmt = $this->conn->prepare("UPDATE `entrole` SET `RoleName`=?, UpdateENTUserAccountId=? WHERE (`RoleId`= ? )");
        $stmt->bind_param("sii", $roleName, $user_id, $roleId);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();

        $this->deleteRoleCapabilities( $roleId );  
        foreach ($capabilities as $capability) {
            $res = $this->createRoleCapabilities( $user_id, $roleId, $capability );
            if (!$res) {
                // task created successfully
                return NULL;
            }
        }
        return $num_affected_rows > 0;
    }

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createRoleCapabilities($user_id, $role_id, $capability_id) {

        $stmt = $this->conn->prepare("INSERT INTO entrolecapability(ENTRoleId, ENTCapabilityId, InsertENTUserAccountId, UpdateENTUserAccountId) values(?, ?, ?, ?)");
        $stmt->bind_param("iiii", $role_id, $capability_id, $user_id, $user_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteRoleCapabilities( $role_id ) {
        $stmt = $this->conn->prepare("DELETE FROM entrolecapability WHERE ENTRoleId=?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    public function getNextKey( $object ){
        $key = 0;

        $stmt = $this->conn->prepare("SELECT Id, Entite, NextKey, Year from refprimarykey WHERE Entite = ?");
        $stmt->bind_param("s", $object);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $entite, $nextKey, $year);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["entite"] = $entite;
            $res["nextKey"] = $nextKey;
            $res["year"] = $year;
            $stmt->close();

            $currentYear = date('Y');
            if( $currentYear > $year ){
                $key = 1;
                $year    = $currentYear;

                echo $nextKey;
            }
            else{
                $key = $nextKey + 1;
            }

            $stmt = $this->conn->prepare("UPDATE refprimarykey SET NextKey=?, Year=? WHERE Id=? AND Entite = ?");
            $stmt->bind_param("ssss", $key, $year, $id, $entite);
            $stmt->execute();
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();


            return $key;
        } else {
            return NULL;
        }

    }

    public function GenerateKey($typeObjet, $code ,$nextKey)
    {
        $resultat = NULL;
        $last_two = date("y") ;
        switch ($typeObjet)
        {
            case 'Patient':
                $resultat = $last_two . 'P' . $nextKey;
                break;
            case 'Commande':
                $resultat = $last_two . 'C' . $nextKey;
                break;
            case 'Facture':
                $resultat = $code . '-' . $last_two . $nextKey;
                break;
            default:
                $resultat = $last_two . $nextKey;
                break;  
        }
        return $resultat;
    }

}

?>
