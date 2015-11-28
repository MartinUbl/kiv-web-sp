<?php

class DashboardController extends BaseSecuredController
{
    protected function verifyRole($role)
    {
        if ((!is_array($role) && $this->loggedUser['role'] !== $role) ||
            (is_array($role) && array_search($this->loggedUser['role'], $role) === FALSE ))
        {
            $this->sendRedirect('/dashboard/'.$this->loggedUser['role']);
            $this->setRenderEnabled(false);
            die();
        }
    }

    public function actionUsers()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        $this->args->users = $this->users()->getAllUsers();
    }

    public function actionUserpromotePOST()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        if (!Sanitizers::validateFieldsPresence($_POST, array('userid', 'role'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $allowedRoles = UserRoles::getRoleTranslations();
        if (!isset($allowedRoles[$_POST['role']]))
        {
            $this->sendResponseJSON(Sanitizers::createErrorMessage('Role', Sanitizers::IS_REQUIRED, Sanitizers::SUBJECT_SHE), AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $this->users()->setUserRole((int)$_POST['userid'], $_POST['role']);

        $this->sendResponseJSON(array());
    }

    public function actionUserdeletePOST()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        if (!Sanitizers::validateFieldsPresence($_POST, array('userid'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $this->users()->deleteUser((int)$_POST['userid']);

        $this->sendResponseJSON(array());
    }

    public function actionMytexts()
    {
        $this->verifyRole(UserRoles::AUTHOR);

        $this->args->contribs = $this->contributions()->getUserContributions($this->loggedUser['id']);
        $this->args->contribCount = $this->args->contribs->rowCount();

        $this->args->statusTranslations = ContributionStatus::getStatusTranslations();
    }

    public function actionAddtext()
    {
        $this->verifyRole(UserRoles::AUTHOR);

        if (isset($_POST['name']))
        {
            $this->actionAddtextPOST();
            return;
        }
    }

    public function actionAddtextPOST()
    {
        $this->verifyRole(UserRoles::AUTHOR);

        if (!Sanitizers::validateFieldsPresence($_POST, array('name', 'authors', 'abstract'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $errorArray = array();

        if (($err = Sanitizers::sanitizeGeneralString($_POST['name'], true, 4, 64)) !== Sanitizers::OK)
            $errorArray['name'] = Sanitizers::createErrorMessage('Název', $err, Sanitizers::SUBJECT_HE);

        $authors = explode(',', $_POST['authors']);
        foreach ($authors as $auth)
        {
            if (($err = Sanitizers::sanitizeGeneralString($auth, true, 4, 250)) !== Sanitizers::OK)
            {
                $errorArray['authors'] = Sanitizers::createErrorMessage('Seznam autorů', $err, Sanitizers::SUBJECT_HE);
                break;
            }
        }

        $abstr = trim($_POST['abstract']);

        if (($err = Sanitizers::sanitizeGeneralString($abstr, true, 4, 250)) !== Sanitizers::OK)
            $errorArray['abstract'] = Sanitizers::createErrorMessage('Abstrakt', $err, Sanitizers::SUBJECT_HE);

        if (count($errorArray) > 0)
        {
            if ($this->args->is_ajax)
                $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        if ($this->args->is_ajax)
        {
            $this->sendResponseJSON(array());
            return;
        }

        // non-ajax part - save to DB, upload file, ..

        $targetDir = UPLOADS_DIR;
        $ff = $_FILES['uploadfile'];

        if ($ff["size"] > 2*1024*1024)
        {
            return;
        }

        if (strtolower(pathinfo(basename($ff["name"]),PATHINFO_EXTENSION)) !== 'pdf')
        {
            return;
        }

        do
        {
            $nname = md5(rand(1,10000000)).'.pdf';
        }
        while (file_exists($targetDir.$nname));

        if (move_uploaded_file($ff["tmp_name"], $targetDir.$nname))
        {
            $this->contributions()->addUserContribution($this->loggedUser['id'], htmlspecialchars($_POST['name']), htmlspecialchars($_POST['authors']), htmlspecialchars($_POST['abstract']), $nname,
                    (isset($_POST['submit_flag']) && $_POST['submit_flag'] === 'save_and_send') ? true : false);

            $this->sendRedirect('/dashboard/mytexts');
        }
    }

    public function actionMycontribdeletePOST()
    {
        $contrib = $this->validateContribInputs(array(UserRoles::AUTHOR, UserRoles::ADMINISTRATOR), $_POST, array('contribid'), ContributionStatus::NEW_CONTRIB);
        if (!$contrib)
            return;

        $returnLink = ($this->loggedUser['role'] === UserRoles::AUTHOR) ? '/dashboard/mytexts' : '/dashboard/textlist';

        if ($this->loggedUser['role'] === UserRoles::AUTHOR && $contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, $returnLink);
            return;
        }

        unlink(UPLOADS_DIR.$contrib['filename']);

        $this->contributions()->deleteContribution($contrib['id']);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, $returnLink);
    }

    public function actionMycontribsubmitPOST()
    {
        $contrib = $this->validateContribInputs(UserRoles::AUTHOR, $_POST, array('contribid'), ContributionStatus::NEW_CONTRIB);
        if (!$contrib)
            return;

        if ($contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        $this->contributions()->setContributionStatus($contrib['id'], ContributionStatus::SUBMITTED);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/mytexts');
    }

    public function actionGetrating()
    {
        $contrib = $this->validateContribInputs(array(UserRoles::AUTHOR, UserRoles::ADMINISTRATOR), $_GET, array('contribid'), false);
        if (!$contrib)
            return;

        if ($contrib['status'] === ContributionStatus::NEW_CONTRIB)
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        if ($this->loggedUser['role'] === UserRoles::AUTHOR && $contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        $rat = $this->contributions()->getContributionOverallRating($contrib['id']);

        $rat['name'] = $contrib['name'];

        $loader = new Twig_Loader_Filesystem(BASE_DIR.'/app/views/');
        $twig = new Twig_Environment($loader);

        $this->sendResponseJSON(array('content' => $twig->render('components/ratingoverall.twig', $rat)));
    }

    protected function isAuthorizedForReview($contrib)
    {
        $assignments = $this->contributions()->getAssignedContributions($this->loggedUser['id']);
        $found = false;
        foreach ($assignments as $asgmt)
        {
            if ($asgmt['id'] == $contrib['id'])
            {
                $found = true;
                break;
            }
        }

        if (!$found)
        {
            // TODO: flash message
            $this->sendRedirect('/dashboard/reviewlist');
            return false;
        }

        return true;
    }

    public function actionContrib()
    {
        $contrib = $this->validateContribInputs(array(UserRoles::AUTHOR, UserRoles::REVIEWER, UserRoles::ADMINISTRATOR), $_GET, array('contribid'), false);
        if (!$contrib)
            return;

        // when user is author, the contribution needs to be his
        if ($this->loggedUser['role'] === UserRoles::AUTHOR && $contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendRedirect('/dashboard/mytexts');
            return;
        }

        // verify assignee for reviewer attempt
        if ($this->loggedUser['role'] === UserRoles::REVIEWER)
        {
            if (!$this->isAuthorizedForReview($contrib))
                return;

            $this->args->rating = $this->contributions()->getContributionRatingBy($contrib['id'], $this->loggedUser['id']);
            if (!$this->args->rating)
            {
                $this->args->rating = array(
                    'originality' => null,
                    'topic' => null,
                    'structure' => null,
                    'language' => null,
                    'recommendation' => null,
                    'notes' => ''
                );
            }
            $this->args->ratingScales = RatingCriteriaScales::getAllScales();
        }

        $this->args->contribution = $contrib;

        // this allows reviewing
        $this->args->rating_allowed = ($this->loggedUser['role'] === UserRoles::REVIEWER && $contrib['status'] === ContributionStatus::SUBMITTED);
    }

    public function actionDownload()
    {
        $contrib = $this->validateContribInputs(array(UserRoles::AUTHOR, UserRoles::REVIEWER, UserRoles::ADMINISTRATOR), $_GET, array('contribid'), false);
        if (!$contrib)
            return;

        // when user is author, the contribution needs to be his
        if ($this->loggedUser['role'] === UserRoles::AUTHOR && $contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        // verify assignee for reviewer attempt
        if ($this->loggedUser['role'] === UserRoles::REVIEWER)
            if (!$this->isAuthorizedForReview($contrib))
                return;

        $path = $this->args->base_url.'/uploads/'.$contrib['filename'];

        header("Location: $path");
    }

    public function actionTextlist()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        $this->args->contribs = $this->contributions()->getAllContributions();
        $this->args->contribCount = $this->args->contribs->rowCount();

        $this->args->statusTranslations = ContributionStatus::getStatusTranslations();
    }

    protected function validateAdminContribInputs($arr, $fields, $statusCheck = false)
    {
        return $this->validateContribInputs(UserRoles::ADMINISTRATOR, $arr, $fields, $statusCheck);
    }

    protected function validateContribInputs($roles, $arr, $fields, $statusCheck = false)
    {
        $this->verifyRole($roles);

        $returnLink = ($this->loggedUser['role'] === UserRoles::AUTHOR) ? '/dashboard/mytexts' : '/dashboard/textlist';

        $missingFields = array();
        if (!Sanitizers::validateFieldsPresence($arr, $fields, $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            if (!$this->args->is_ajax)
                $this->sendRedirect($returnLink);
            else
                $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return false;
        }

        $contrib = $this->contributions()->getContributionById((int)$arr['contribid']);

        if (!$contrib)
        {
            // TODO: flash message
            if (!$this->args->is_ajax)
                $this->sendRedirect($returnLink);
            else
                $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, $returnLink);
            return false;
        }

        if ($statusCheck)
        {
            if ($contrib['status'] !== $statusCheck)
            {
                // TODO: flash message
                if (!$this->args->is_ajax)
                    $this->sendRedirect($returnLink);
                else
                    $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, $returnLink);
                return false;
            }
        }

        return $contrib;
    }

    public function actionApprovalPOST()
    {
        $contrib = $this->validateAdminContribInputs($_POST, array('contribid', 'approve'), false);
        if (!$contrib)
            return;

        $this->contributions()->setContributionStatus($contrib['id'], ((int)$_POST['approve'] === 1) ? ContributionStatus::ACCEPTED : ContributionStatus::REJECTED);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/textlist');
    }

    protected function renderAssignComponent($contrib)
    {
        $assign = $this->contributions()->getContributionAssignment($contrib['id']);

        $resarray = array();
        $presentusers = array();
        foreach ($assign as $ass)
        {
            $resarray[] = $ass;
            $presentusers[] = $ass['users_id'];
        }

        while (count($resarray) < 3)
            $resarray[] = 'none';

        $ratingByUser = array();
        $ratrows = $this->contributions()->getContributionRatingRows($contrib['id']);
        foreach ($ratrows as $rr)
            $ratingByUser[$rr['users_id']] = $rr;

        $tplarr = array(
            'id' => $contrib['id'],
            'assign' => $resarray,
            'name' => $contrib['name'],
            'reviewers' => $this->users()->getAllUsersInRole(UserRoles::REVIEWER)->fetchAll(),
            'ratings' => $ratingByUser,
            'assigned' => $presentusers
        );

        $loader = new Twig_Loader_Filesystem(BASE_DIR.'/app/views/');
        $twig = new Twig_Environment($loader);

        $this->hookTemplateExtensions($twig);

        $this->sendResponseJSON(array('content' => $twig->render('components/assign.twig', $tplarr)));
    }

    public function actionGetassign()
    {
        $contrib = $this->validateAdminContribInputs($_GET, array('contribid'), ContributionStatus::SUBMITTED);
        if (!$contrib)
            return;

        $this->renderAssignComponent($contrib);
    }

    public function actionAssign()
    {
        $contrib = $this->validateAdminContribInputs($_GET, array('contribid', 'userid'), ContributionStatus::SUBMITTED);
        if (!$contrib)
            return;

        $this->setRenderEnabled(false);

        $this->contributions()->addAssignment($contrib["id"], (int)$_GET['userid']);

        $this->renderAssignComponent($contrib);
    }

    public function actionAssigncancel()
    {
        $contrib = $this->validateAdminContribInputs($_GET, array('contribid', 'userid'), ContributionStatus::SUBMITTED);
        if (!$contrib)
            return;

        $this->setRenderEnabled(false);

        $this->contributions()->removeAssignment($contrib["id"], (int)$_GET['userid']);

        $this->renderAssignComponent($contrib);
    }

    public function actionReviewlist()
    {
        $this->verifyRole(UserRoles::REVIEWER);

        $this->args->contribs = $this->contributions()->getAssignedContributions($this->loggedUser['id']);
        $this->args->contribCount = $this->args->contribs->rowCount();
        $this->args->reviewed = $this->contributions()->getUserRatedContributions($this->loggedUser['id']);
    }

    public function actionReviewPOST()
    {
        $contrib = $this->validateContribInputs(UserRoles::REVIEWER, $_POST, array('contribid', 'note'), false);
        if (!$contrib)
            return;

        $criterias = array('originality', 'topic', 'structure', 'language', 'recommendation');
        $errorArray = array();
        foreach ($criterias as $cr)
        {
            if (!isset($_POST[$cr]) || !is_numeric($_POST[$cr]))
                $errorArray[$cr] = Sanitizers::createErrorMessage('Toto pole', Sanitizers::IS_REQUIRED, Sanitizers::SUBJECT_IT);
        }

        if (count($errorArray) > 0)
        {
            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        // verify assignee for reviewer attempt
        if (!$this->isAuthorizedForReview($contrib))
            return;

        $this->contributions()->removeContributionRating($contrib['id'], $this->loggedUser['id']);
        $this->contributions()->addContributionRating($contrib['id'], $this->loggedUser['id'],
                (int)$_POST['originality'], (int)$_POST['topic'], (int)$_POST['structure'],
                (int)$_POST['language'], (int)$_POST['recommendation'], htmlspecialchars($_POST['note']));

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/reviewlist');
    }
}
