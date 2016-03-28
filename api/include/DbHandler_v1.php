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
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($username, $password) {
        require_once 'PassHash.php';
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE username = ?");

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
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
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByUsername($username) {
        $stmt = $this->conn->prepare("SELECT
        users.username,
        users.api_key,
        users.`status`,
        users.created_at,
        employee.Nom,
        employee.Prenom
        FROM
        users
        INNER JOIN employee ON employee.EmployeeId = users.employeeid
        WHERE username = ?");
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($username, $api_key, $status, $created_at, $nom, $prenom);
            $stmt->fetch();
            $user        = array();
            $permissions = array();


            $user["username"]        = $nom . ' ' . $prenom;
            $user["api_key"]         = $api_key;
            $user["status"]          = $status;
            $user["avatar"]          = "";
            $user["created_at"]      = $created_at;
            $stmt->close();


            $user["userPermissions"] = $this->getAllUserPermissions($username);

            

            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserPermissions($username) {
        $stmt = $this->conn->prepare("SELECT
        entrole.RoleName,
        entmenuitem.MenuItemName,
        entmenuitem.Url,
        entmenuitem.Icons,
        entmenuitem.IsAlwaysEnabled,
        entmenuitem.DisplaySequence,
        entmenuitem.ParentENTMenuItemId
        FROM
        employee
        INNER JOIN users ON employee.EmployeeId = users.employeeid
        INNER JOIN entroleuseraccount ON users.id = entroleuseraccount.ENTUserAccountId
        INNER JOIN entrole ON entroleuseraccount.ENTRoleId = entrole.RoleId
        INNER JOIN entrolecapability ON entrolecapability.ENTRoleId = entrole.RoleId
        INNER JOIN entcapability ON entrolecapability.ENTCapabilityId = entcapability.ENTCapabilityId
        INNER JOIN entmenuitem ON entcapability.ENTMenuItemId = entmenuitem.ENTMenuItemId
        WHERE users.username = ?");

        //die($this->conn->error);

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $Permissions = array();

        $tasks = $stmt->get_result();
        while ($task = $tasks->fetch_assoc()) {
                $tmp = array();
                $tmp["permissionLib"]       = $task["MenuItemName"];
                $tmp["permissionUrl"]       = $task["Url"];
                $tmp["permissionIcon"]      = $task["Icons"];
                $tmp["IsAlwaysEnabled"]     = $task["IsAlwaysEnabled"];
                $tmp["DisplaySequence"]     = $task["DisplaySequence"];
                $tmp["ParentENTMenuItemId"] = $task["ParentENTMenuItemId"];
                array_push($Permissions, $tmp);
            }
        $stmt->close();
        return $Permissions;
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
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function getUserByRole($role_id) {

        $stmt = $this->conn->prepare("SELECT
        u.id,
        employee.Nom,
        employee.Prenom,
        entrole.RoleName,
        entrole.RoleId
        FROM
        users AS u
        INNER JOIN employee ON employee.EmployeeId = u.employeeid
        INNER JOIN entroleuseraccount ON entroleuseraccount.ENTUserAccountId = u.id
        INNER JOIN entrole ON entrole.RoleId = entroleuseraccount.ENTRoleId AND entrole.RoleId = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]       = $task["id"];
            $tmp["fullName"] = $task["Nom"] . ' ' . $task["Prenom"];
            if($role_id == 2){
                $tmp["montantEncaisse"] = $this->getTotalMontantByUserCaisse($tmp["id"]);
            }
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }

    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getTotalMontantByUserCaisse($user_id) {
        $stmt = $this->conn->prepare("SELECT
        Sum(facture.NetAPayer) as montantEncaisse
        FROM `facture`
        WHERE
        facture.FacturiereId = ?
        GROUP BY
        facture.FacturiereId
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result( $total);
        // TODO
        // $task = $stmt->get_result()->fetch_assoc();
        $stmt->fetch();
        $stmt->close();
        return $total;
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




    /* ------------- `patient` table method ------------------ */

    /**
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function getAllDay() {
        
        $stmt = $this->conn->prepare("SELECT * FROM jour");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["jourId"] = $task["JourId"];
            $tmp["jourNumber"] = $task["JourNumber"];
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
    public function getAllYears() {

        $stmt = $this->conn->prepare("SELECT * FROM annee");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["anneeId"]  = $task["AnneeId"];
            $tmp["anneeLib"] = $task["AnneeLib"];
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
    public function getAllCountry() {

        $stmt = $this->conn->prepare("SELECT * FROM pays");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]      = $task["PaysId"];
            $tmp["paysLib"] = $task["PaysLib"];
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
    public function getAllCommune() {

        $stmt = $this->conn->prepare("SELECT * FROM commune");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]      = $task["CommuneId"];
            $tmp["communeLib"] = $task["CommuneLib"];
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
    public function getAllTypePiece() {

        $stmt = $this->conn->prepare("SELECT * FROM typepiecefournit");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]      = $task["TypePieceFournitId"];
            $tmp["typePieceFournitLib"] = $task["TypePieceFournitLib"];
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
    public function getAllTypePatient() {

        $stmt = $this->conn->prepare("SELECT * FROM typepatient");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]      = $task["TypePatientId"];
            $tmp["typePatientLib"] = $task["TypePatientLib"];
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
     * Fetching patient form param
     * @param  no
     * @return no
     */
    public function getAllEtabAsnitaires() {

        $stmt = $this->conn->prepare("SELECT * FROM etablissementsanitaire");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["Id"];
            $tmp["etablissementSanitaireLib"] = $task["EtablissementSanitaireLib"];
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
    public function getAllAnalyses() {

        $stmt = $this->conn->prepare("SELECT * FROM analyse");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["AnalyseId"];
            $tmp["analyseLib"] = $task["AnalyseLib"];
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
    public function getAllServiceMedecins() {

        $stmt = $this->conn->prepare("SELECT * FROM servicemedecin");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["ServiceMedecinId"];
            $tmp["serviceMedecinLib"] = $task["ServiceMedecinLib"];
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
    public function getAllDocProf() {

        $stmt = $this->conn->prepare("SELECT
        employee.Nom,
        employee.Prenom,
        employee.EmployeeId
        FROM
        employee
        INNER JOIN titre ON titre.TitreId = employee.TitreId AND 
        (employee.TitreId = 1 OR employee.TitreId = 2)");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $task["EmployeeId"];
            $tmp["fullname"]  = $task["Nom"] . ' ' . $task["Prenom"];
            array_push($response, $tmp);
        }
        $stmt->close();


        return $response;
    }


    /**
     * Fetching laboratoire form param
     * @param  no
     * @return no
     */
    public function getAllLabs() {

        $stmt = $this->conn->prepare("SELECT * FROM laboratoire");
        $stmt->execute();
        $tasks = $stmt->get_result();
        
        $response  = array();
        // looping through result and preparing tasks array
        while ($task = $tasks->fetch_assoc()) {
            $tmp = array();
            $tmp["id"]  = $task["LaboratoireId"];
            $tmp["laboratoireLib"] = $task["LaboratoireLib"];
            $tmp["uniteId"] = $task["UniteId"];
            array_push($response, $tmp);
        }
        $stmt->close();

        return $response;
    }



    /**
     * Fetching all patient
     * @param $pageSize $pageNumber $searchText
     */
    public function getAllPatients($limit, $offset, $searchText) {
        $stmt = $this->conn->prepare("SELECT * FROM patient order by PatientId ASC LIMIT ? OFFSET ?");
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

        if( $this->getTotalCommande() < 50 ){
            $limit = $this->getTotalCommande();
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
            $owner     = $this->getEmployeeByAcctId($task["OwnerId"]);
            $submitter = $this->getEmployeeByAcctId($task["SubmitterId"]);
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

            $owner     = $this->getEmployeeByAcctId($ownerId);
            $submitter = $this->getEmployeeByAcctId($submitterId);

            $res["ownerNom"]     = $owner["nom"] . ' ' . $owner["prenom"];
            $res["submitterNom"] = $submitter["nom"] . ' ' . $submitter["prenom"];
            $res["analyse_list"] = $this->getAnalyseByCmdId( $id );


            
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

            $owner     = $this->getEmployeeByAcctId($task["OwnerId"]);
            $submitter = $this->getEmployeeByAcctId($task["SubmitterId"]);

            $tmp["ownerNom"]     = $owner["nom"] . ' ' . $owner["prenom"];
            $tmp["submitterNom"] = $submitter["nom"] . ' ' . $submitter["prenom"];
            $tmp["analyse_list"] = $this->getAnalyseByCmdId( $task["Id"] );
            array_push($response, $tmp);
        }

        $stmt->close();
        return $response;
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
    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getEmployeeByAcctId($id) {
        $stmt = $this->conn->prepare("SELECT
        e.Nom,
        e.Prenom,
        u.username
        FROM
        users AS u
        INNER JOIN employee AS e ON e.EmployeeId = u.employeeid AND u.id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($nom, $prenom, $username);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["nom"]      = $nom;
            $res["prenom"]   = $prenom;
            $res["username"] = $username;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    /* ------------- `commandes` table method ------------------ */

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getAnalyseDetails($tcontratid, $analyseid, $employeeid) {
        $stmt = $this->conn->prepare("SELECT
        analyse.AnalyseId,
        analyse.AnalyseLib,
        analyse.NbreJourRetrait,
        analyse.LaboratoireId,
        prixanalyse.Forfait,
        prixanalyse.NbreB,
        typecontrat.TypeContratId
        FROM
        analyse
        INNER JOIN prixanalyse ON prixanalyse.AnalyseId = analyse.AnalyseId AND analyse.AnalyseId = ?
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
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getEmployeeNameById($employeeid) {
        $stmt = $this->conn->prepare("SELECT
        employee.Nom,
        employee.Prenom,
        employee.EmployeeId,
        titre.TauxReduction
        FROM
        employee
        INNER JOIN titre ON titre.TitreId = employee.TitreId
        WHERE
        employee.EmployeeId = ?");
        $stmt->bind_param("i", $employeeid);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($nom, $prenom, $employeeId, $taux);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["fullname"] = $nom . ' ' . $prenom;
            $res["taux"] = $taux;
            //$res["medecinFullName"] = $nom . ' ' . $prenom;
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
        INNER JOIN laboratoire ON laboratoire.LaboratoireId = facture.LaboratoireId
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
            

            $owner     = $this->getEmployeeByAcctId($task["FacturiereId"]);
            $submitter = $this->getEmployeeByAcctId($task["RegisseurId"]);

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
