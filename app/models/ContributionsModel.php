<?php

class ContributionsModel extends BaseModel
{
    public function getAllContributions($offset = 0, $count = 10, $order = null, $userid = null)
    {
        return $this->query("SELECT *, cb.id AS id, us.username AS username, us.first_name AS first_name, us.last_name AS last_name FROM contributions cb LEFT JOIN users us ON cb.users_id = us.id ".($userid ? "WHERE users_id = ".$userid." " : "").($order ? $order : "")." LIMIT ".$offset.", ".$count);
    }

    public function getUserContributions($userId, $offset = 0, $count = 10)
    {
        return $this->getAllContributions($offset, $count, null, $userId);
    }

    public function addUserContribution($userId, $name, $authors, $abstract, $filename, $submitted = false)
    {
        $this->execute("INSERT INTO contributions (users_id, name, authors, abstract, filename, status, create_date, submission_date) VALUES"
                . "(".$userId.", '".$name."', '".$authors."', '".$abstract."', '".$filename."', '".($submitted ? ContributionStatus::SUBMITTED : ContributionStatus::NEW_CONTRIB)."', '".date('Y-m-d H:i:s')."', ".($submitted ? "'".date('Y-m-d H:i:s')."'" : 'NULL').")");

        return $this->getInsertId();
    }

    public function getContributionById($id)
    {
        return $this->query("SELECT * FROM contributions WHERE id = ".$id)->fetch();
    }

    public function deleteContribution($id)
    {
        $this->execute("DELETE FROM contributions WHERE id = ".$id);
    }

    public function setContributionStatus($id, $status)
    {
        $this->execute("UPDATE contributions SET status = '".$status."' WHERE id = ".$id);
    }

    public function getContributionOverallRating($id)
    {
        return $this->query("SELECT COUNT(1) AS count, SUM(originality) AS originality,"
                . " SUM(topic) AS topic, SUM(structure) AS structure,"
                . " SUM(language) AS language, SUM(recommendation) AS recommendation FROM contribution_rating WHERE contributions_id = ".$id)->fetch();
    }

    public function getContributionRatingRows($id)
    {
        return $this->query("SELECT * FROM contribution_rating WHERE contributions_id = ".$id);
    }

    public function getContributionRatingBy($id, $userid)
    {
        return $this->query("SELECT * FROM contribution_rating WHERE contributions_id = ".$id." AND users_id = ".$userid)->fetch();
    }

    public function removeContributionRating($id, $usersid)
    {
        $this->execute("DELETE FROM contribution_rating WHERE contributions_id = ".$id." AND users_id = ".$usersid."");
    }

    public function getUserRatedContributions($usersid)
    {
        $contarr = array();
        $rq = $this->query("SELECT * FROM contribution_rating WHERE users_id = ".$usersid);
        foreach ($rq as $rat)
            $contarr[] = $rat['contributions_id'];

        return $contarr;
    }

    public function addContributionRating($id, $usersid, $originality, $topic, $structure, $language, $recommend, $notes)
    {
        $this->execute("INSERT INTO contribution_rating (contributions_id, users_id, originality, topic, structure, language, recommendation, notes, rating_date) VALUES "
                . "($id, $usersid, $originality, $topic, $structure, $language, $recommend, '".$notes."', '".date('Y-m-d H:i:s')."')");
    }

    public function getContributionAssignment($id)
    {
        return $this->query("SELECT *, us.username AS username, us.first_name AS first_name, us.last_name AS last_name FROM contribution_rating_assignment cra LEFT JOIN users us ON cra.users_id = us.id WHERE contributions_id = ".$id);
    }

    public function addAssignment($id, $usersid)
    {
        $this->execute("INSERT INTO contribution_rating_assignment VALUES (".$id.", ".$usersid.")");
    }

    public function removeAssignment($id, $usersid)
    {
        $this->removeContributionRating($id, $usersid);
        $this->execute("DELETE FROM contribution_rating_assignment WHERE contributions_id = ".$id." AND users_id = ".$usersid."");
    }

    public function getAssignedContributions($userid)
    {
        return $this->query("SELECT * FROM contributions WHERE status = '".ContributionStatus::SUBMITTED."' AND id IN (SELECT contributions_id FROM contribution_rating_assignment WHERE users_id = ".$userid.")");
    }
}
