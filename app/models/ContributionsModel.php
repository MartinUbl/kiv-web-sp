<?php

/**
 * Model for working with contributions data
 */
class ContributionsModel extends BaseModel
{
    /**
     * Retrieves all contributions from DB
     * @param int $offset
     * @param int $count
     * @param string $order
     * @param int $userid
     * @return PDOStatement
     */
    public function getAllContributions($offset = 0, $count = 100, $order = null, $userid = null)
    {
        return $this->query("SELECT *, cb.id AS id, us.username AS username, us.first_name AS first_name, us.last_name AS last_name FROM contributions cb LEFT JOIN users us ON cb.users_id = us.id ".($userid ? "WHERE users_id = ".$userid." " : "").($order ? $order : "")." LIMIT ".$offset.", ".$count);
    }

    /**
     * Retrieves all user contributions
     * @param int $userId
     * @param int $offset
     * @param int $count
     * @return PDOStatement
     */
    public function getUserContributions($userId, $offset = 0, $count = 100)
    {
        return $this->getAllContributions($offset, $count, null, $userId);
    }

    /**
     * Adds contribution; returns its ID
     * @param int $userId
     * @param string $name
     * @param string $authors
     * @param string $abstract
     * @param string $filename
     * @param boolean $submitted
     * @return int
     */
    public function addUserContribution($userId, $name, $authors, $abstract, $filename, $submitted = false)
    {
        $this->execute("INSERT INTO contributions (users_id, name, authors, abstract, filename, status, create_date, submission_date) VALUES"
                . "(".$userId.", '".$name."', '".$authors."', '".$abstract."', '".$filename."', '".($submitted ? ContributionStatus::SUBMITTED : ContributionStatus::NEW_CONTRIB)."', '".date('Y-m-d H:i:s')."', ".($submitted ? "'".date('Y-m-d H:i:s')."'" : 'NULL').")");

        return $this->getInsertId();
    }

    /**
     * Retrieves contribution record by its ID
     * @param int $id
     * @return PDOStatement
     */
    public function getContributionById($id)
    {
        return $this->query("SELECT * FROM contributions WHERE id = ".$id)->fetch();
    }

    /**
     * Removes contribution record by ID
     * @param int $id
     */
    public function deleteContribution($id)
    {
        $this->execute("DELETE FROM contributions WHERE id = ".$id);
    }

    /**
     * Sets status of contribution
     * @param int $id
     * @param string $status
     */
    public function setContributionStatus($id, $status)
    {
        $this->execute("UPDATE contributions SET status = '".$status."' WHERE id = ".$id);
    }

    /**
     * Retrieves averages for all rating criterias
     * @param int $id
     * @return PDOStatement
     */
    public function getContributionOverallRating($id)
    {
        return $this->query("SELECT COUNT(1) AS count, SUM(originality) AS originality,"
                . " SUM(topic) AS topic, SUM(structure) AS structure,"
                . " SUM(language) AS language, SUM(recommendation) AS recommendation FROM contribution_rating WHERE contributions_id = ".$id)->fetch();
    }

    /**
     * Retrieves all ratings for contribution
     * @param int $id
     * @return PDOStatement
     */
    public function getContributionRatingRows($id)
    {
        return $this->query("SELECT * FROM contribution_rating WHERE contributions_id = ".$id);
    }

    /**
     * Retrieves contribution rating from one user
     * @param int $id
     * @param int $userid
     * @return array
     */
    public function getContributionRatingBy($id, $userid)
    {
        return $this->query("SELECT * FROM contribution_rating WHERE contributions_id = ".$id." AND users_id = ".$userid)->fetch();
    }

    /**
     * Removes contribution rating done by user
     * @param int $id
     * @param int $usersid
     */
    public function removeContributionRating($id, $usersid)
    {
        $this->execute("DELETE FROM contribution_rating WHERE contributions_id = ".$id." AND users_id = ".$usersid."");
    }

    /**
     * Retrieves all rated contribution IDs of user
     * @param int $usersid
     * @return array
     */
    public function getUserRatedContributions($usersid)
    {
        $contarr = array();
        $rq = $this->query("SELECT * FROM contribution_rating WHERE users_id = ".$usersid);
        // fetch IDs to array
        foreach ($rq as $rat)
            $contarr[] = $rat['contributions_id'];

        return $contarr;
    }

    /**
     * Add contribution rating
     * @param int $id
     * @param int $usersid
     * @param int $originality
     * @param int $topic
     * @param int $structure
     * @param int $language
     * @param int $recommend
     * @param string $notes
     */
    public function addContributionRating($id, $usersid, $originality, $topic, $structure, $language, $recommend, $notes)
    {
        $this->execute("INSERT INTO contribution_rating (contributions_id, users_id, originality, topic, structure, language, recommendation, notes, rating_date) VALUES "
                . "($id, $usersid, $originality, $topic, $structure, $language, $recommend, '".$notes."', '".date('Y-m-d H:i:s')."')");
    }

    /**
     * Retrieves all rating assignments to contribution ID
     * @param int $id
     * @return PDOStatement
     */
    public function getContributionAssignment($id)
    {
        return $this->query("SELECT *, us.username AS username, us.first_name AS first_name, us.last_name AS last_name FROM contribution_rating_assignment cra LEFT JOIN users us ON cra.users_id = us.id WHERE contributions_id = ".$id);
    }

    /**
     * Adds new assignment to contribution
     * @param int $id
     * @param int $usersid
     */
    public function addAssignment($id, $usersid)
    {
        $this->execute("INSERT INTO contribution_rating_assignment VALUES (".$id.", ".$usersid.")");
    }

    /**
     * Removes existing user assignment to contribution
     * @param int $id
     * @param int $usersid
     */
    public function removeAssignment($id, $usersid)
    {
        $this->removeContributionRating($id, $usersid);
        $this->execute("DELETE FROM contribution_rating_assignment WHERE contributions_id = ".$id." AND users_id = ".$usersid."");
    }

    /**
     * Retrieves all user assigned contributions
     * @param int $userid
     * @return PDOStatement
     */
    public function getAssignedContributions($userid)
    {
        return $this->query("SELECT * FROM contributions WHERE status = '".ContributionStatus::SUBMITTED."' AND id IN (SELECT contributions_id FROM contribution_rating_assignment WHERE users_id = ".$userid.")");
    }
}
