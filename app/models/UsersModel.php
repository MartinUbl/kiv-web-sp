<?php

/**
 * Model for working with users
 */
class UsersModel extends BaseModel
{
    /**
     * Retrieves selection of all users
     * @return PDOStatement
     */
    public function getAllUsers()
    {
        return $this->query("SELECT * FROM users");
    }

    /**
     * Retrieves user by ID
     * @param int $id
     * @return array|null
     */
    public function getUserById($id)
    {
        return $this->query("SELECT * FROM users WHERE id = ".$id)->fetch();
    }

    /**
     * Retrieves user by username
     * @param string $username
     * @return array|null
     */
    public function getUserByUsername($username)
    {
        return $this->query("SELECT * FROM users WHERE username = '".$username."'")->fetch();
    }

    /**
     * Creates user; returns his ID
     * @param string $username
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $password
     * @param string $role
     * @return int
     */
    public function createUser($username, $firstname, $lastname, $email, $password, $role)
    {
        $this->execute("INSERT INTO users (username, password_hash, first_name, last_name, email, role)"
                . "VALUES ('".$username."', '".MiscHelpers::passwordHash($password)
                . "', '".$firstname."', '".$lastname."', '".$email."', '".$role."')");

        return $this->getInsertId();
    }

    /**
     * Retrieves selection of all users in specified role
     * @param string $role
     * @return PDOStatement
     */
    public function getAllUsersInRole($role)
    {
        return $this->query("SELECT * FROM users WHERE role = '".$role."'");
    }

    /**
     * Updates role of user
     * @param int $id
     * @param string $role
     */
    public function setUserRole($id, $role)
    {
        $this->execute("UPDATE users SET role = '".$role."' WHERE id = ".$id);
    }

    /**
     * Moves user from users table to users_deleted for the rest of eternity
     * @param int $id
     */
    public function deleteUser($id)
    {
        // delete user record only if insertion went well
        if ($this->execute("INSERT INTO users_deleted (SELECT *, '".date("Y-m-d H:i:s")."' FROM users WHERE id = ".$id.")") > 0)
            $this->execute("DELETE FROM users WHERE id = ".$id);
    }
}
