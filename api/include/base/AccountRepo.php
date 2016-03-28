<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class AccountRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($username, $password) {
        require_once '../include/PassHash.php';
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
        WHERE users.username = ?
        ORDER BY ParentENTMenuItemId, DisplaySequence, MenuItemName");

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

}

?>
