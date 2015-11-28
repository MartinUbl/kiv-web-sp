<?php

class UsersModel extends BaseModel
{
    public function getAllUsers()
    {
        return $this->query("SELECT * FROM users");
    }

    public function getUserById($id)
    {
        return $this->query("SELECT * FROM users WHERE id = ".$id)->fetch();
    }

    public function getUserByUsername($username)
    {
        return $this->query("SELECT * FROM users WHERE username = '".$username."'")->fetch();
    }

    public function createUser($username, $firstname, $lastname, $email, $password, $role)
    {
        $this->execute("INSERT INTO users (username, password_hash, first_name, last_name, email, role)"
                . "VALUES ('".$username."', '".MiscHelpers::passwordHash($password)
                . "', '".$firstname."', '".$lastname."', '".$email."', '".$role."')");

        return $this->getInsertId();
    }

    public function getAllUsersInRole($role)
    {
        return $this->query("SELECT * FROM users WHERE role = '".$role."'");
    }

    public function setUserRole($id, $role)
    {
        $this->execute("UPDATE users SET role = '".$role."' WHERE id = ".$id);
    }

    public function deleteUser($id)
    {
        if ($this->execute("INSERT INTO users_deleted (SELECT *, '".date("Y-m-d H:i:s")."' FROM users WHERE id = ".$id.")") > 0)
            $this->execute("DELETE FROM users WHERE id = ".$id);
    }
}
