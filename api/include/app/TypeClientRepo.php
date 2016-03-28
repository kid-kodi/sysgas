<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class TypeClientRepo {

    private $conn;

    function __construct() {
        require_once '../include/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }


    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTypeClient( $typeClientLib, $user_id ) {
        $stmt = $this->conn->prepare("INSERT INTO typeclient(TypeClientLib, InsertENTUserAccountId, UpdateENTUserAccountId) VALUES(?,?,?,?)");
        $stmt->bind_param("sii", $typeClientId, $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_tp_id = $this->conn->insert_id;
            return $new_tp_id;
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTypeClient($typeClientId, $typeClientLib, $user_id) {
        $stmt = $this->conn->prepare("SELECT TypeClientId, TypeClientLib, InsertDate from typeclient WHERE TypeClientId = ?");
        $stmt->bind_param("i", $typeClientId);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($typeClientId, $typeClientLib, $insertDate);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $typeClientId;
            $res["typeClientLib"] = $typeClientLib;
            $res["insertDate"] = $insertDate;
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
    public function getAllTypeClients($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM typeclient");
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
    public function updateTypeClient($typeClientId, $typeClientLib, $user_id) {
        $stmt = $this->conn->prepare("UPDATE typeclient set TypeClientLib = ?, UpdateENTUserAccountId = ? WHERE TypeClientId = ?");
        $stmt->bind_param("sii", $typeClientLib, $user_id, $typeClientId);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTypeClient($typeClientId) {
        $stmt = $this->conn->prepare("DELETE FROM typeclient WHERE TypeClientId = ?");
        $stmt->bind_param("i", $typeClientId);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

}

?>
