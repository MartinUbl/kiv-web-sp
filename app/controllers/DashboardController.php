<?php

/**
 * Controller for dashboard actions
 */
class DashboardController extends BaseSecuredController
{
    /**
     * Verifies role for current action
     * @param string|array $role
     */
    protected function verifyRole($role)
    {
        // if array was supplied, look for role in it, otherwise verify string match
        if ((!is_array($role) && $this->loggedUser['role'] !== $role) ||
            (is_array($role) && array_search($this->loggedUser['role'], $role) === FALSE ))
        {
            // redirect to proper dashboard, and die silently
            $this->sendRedirect('/dashboard/'.$this->loggedUser['role']);
            $this->setRenderEnabled(false);
            die();
        }
    }

    /**
     * Admin action for managing users
     */
    public function actionUsers()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        $this->args->users = $this->users()->getAllUsers();
    }

    /**
     * Admin action for promoting user (generally changing user rank)
     */
    public function actionUserpromotePOST()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        // userid and role has to be present
        if (!Sanitizers::validateFieldsPresence($_POST, array('userid', 'role'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        // filter allowed roles
        $allowedRoles = UserRoles::getRoleTranslations();
        if (!isset($allowedRoles[$_POST['role']]))
        {
            $this->sendResponseJSON(Sanitizers::createErrorMessage('Role', Sanitizers::IS_REQUIRED, Sanitizers::SUBJECT_SHE), AjaxResponseCodes::FORM_ERROR);
            return;
        }

        // update role
        $this->users()->setUserRole((int)$_POST['userid'], $_POST['role']);

        // let frontend know everything's fine
        $this->sendResponseJSON(array(), AjaxResponseCodes::OK);
    }

    /**
     * Admin action for deleting user
     */
    public function actionUserdeletePOST()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        // userid has to be present
        if (!Sanitizers::validateFieldsPresence($_POST, array('userid'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        // delete user with this ID
        $this->users()->deleteUser((int)$_POST['userid']);

        $this->sendResponseJSON(array());
    }

    /**
     * Author action for listing all his contributions
     */
    public function actionMytexts()
    {
        $this->verifyRole(UserRoles::AUTHOR);

        $this->args->contribs = $this->contributions()->getUserContributions($this->loggedUser['id']);
        $this->args->contribCount = $this->args->contribs->rowCount();

        $this->args->statusTranslations = ContributionStatus::getStatusTranslations();
    }

    /**
     * Author action for adding new contribution
     */
    public function actionAddtext()
    {
        $this->verifyRole(UserRoles::AUTHOR);

        // little hack for redirecting falsely detected GET method to POST handler
        if (isset($_POST['name']))
        {
            $this->actionAddtextPOST();
            return;
        }
    }

    /**
     * Author action for proceeding with adding new contribution
     */
    public function actionAddtextPOST()
    {
        $this->verifyRole(UserRoles::AUTHOR);

        // name, authors and abstract fields has to be present for this level of validation
        if (!Sanitizers::validateFieldsPresence($_POST, array('name', 'authors', 'abstract'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $errorArray = array();

        // name has to be present
        if (($err = Sanitizers::sanitizeGeneralString($_POST['name'], true, 4, 64)) !== Sanitizers::OK)
            $errorArray['name'] = Sanitizers::createErrorMessage('Název', $err, Sanitizers::SUBJECT_HE);

        // validate authors field, one author by one
        $authors = explode(',', $_POST['authors']);
        foreach ($authors as $auth)
        {
            if (($err = Sanitizers::sanitizeGeneralString($auth, true, 4, 250)) !== Sanitizers::OK)
            {
                $errorArray['authors'] = Sanitizers::createErrorMessage('Seznam autorů', $err, Sanitizers::SUBJECT_HE);
                break;
            }
        }

        // retrieve trimmed abstract
        $abstr = trim($_POST['abstract']);

        // and validate it
        if (($err = Sanitizers::sanitizeGeneralString($abstr, true, 4, 250)) !== Sanitizers::OK)
            $errorArray['abstract'] = Sanitizers::createErrorMessage('Abstrakt', $err, Sanitizers::SUBJECT_HE);

        // if there is some error, report immediatelly
        if (count($errorArray) > 0)
        {
            if ($this->args->is_ajax)
                $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        // here ends ajax part - first "wave" of validation
        if ($this->args->is_ajax)
        {
            $this->sendResponseJSON(array());
            return;
        }

        // non-ajax part - save to DB, upload file, ..

        $targetDir = UPLOADS_DIR;
        $ff = $_FILES['uploadfile'];

        // limit upload file size to 2MB
        if ($ff["size"] > 2*1024*1024)
        {
            // TODO: flash message
            return;
        }

        // allow only PDF files
        if (strtolower(pathinfo(basename($ff["name"]),PATHINFO_EXTENSION)) !== 'pdf')
        {
            // TODO: flash message
            return;
        }

        // choose filename, that does not exist yet
        do {
            $nname = md5(rand(1,10000000)).'.pdf';
        } while (file_exists($targetDir.$nname));

        // store uploaded file
        if (move_uploaded_file($ff["tmp_name"], $targetDir.$nname))
        {
            // add to database
            // submit flag is set to indicate action - to submit or not to submit, that's the question
            $this->contributions()->addUserContribution($this->loggedUser['id'], htmlspecialchars($_POST['name']), htmlspecialchars($_POST['authors']), htmlspecialchars($_POST['abstract']), $nname,
                    (isset($_POST['submit_flag']) && $_POST['submit_flag'] === 'save_and_send') ? true : false);

            // after successfull insert, redirect to mytexts
            $this->sendRedirect('/dashboard/mytexts');
        }
    }

    /**
     * Author/admin action for deleting contributions
     */
    public function actionMycontribdeletePOST()
    {
        $contrib = $this->validateContribInputs(array(UserRoles::AUTHOR, UserRoles::ADMINISTRATOR), $_POST, array('contribid'), ContributionStatus::NEW_CONTRIB);
        if (!$contrib)
            return;

        $returnLink = ($this->loggedUser['role'] === UserRoles::AUTHOR) ? '/dashboard/mytexts' : '/dashboard/textlist';

        // if it's author, verify, if he deletes his contribution
        if ($this->loggedUser['role'] === UserRoles::AUTHOR && $contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, $returnLink);
            return;
        }

        // delete associated file
        unlink(UPLOADS_DIR.$contrib['filename']);

        // delete contribution from database
        $this->contributions()->deleteContribution($contrib['id']);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, $returnLink);
    }

    /**
     * Author action for sending his contribution to rating and approval
     */
    public function actionMycontribsubmitPOST()
    {
        $contrib = $this->validateContribInputs(UserRoles::AUTHOR, $_POST, array('contribid'), ContributionStatus::NEW_CONTRIB);
        if (!$contrib)
            return;

        // it has to be current user's contribution
        if ($contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        // update status to submitted
        $this->contributions()->setContributionStatus($contrib['id'], ContributionStatus::SUBMITTED);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/mytexts');
    }

    /**
     * Author/admin action for retrieving rendered overall rating dialog
     */
    public function actionGetrating()
    {
        $contrib = $this->validateContribInputs(array(UserRoles::AUTHOR, UserRoles::ADMINISTRATOR), $_GET, array('contribid'), false);
        if (!$contrib)
            return;

        // we do not send rating for not-submitted work
        if ($contrib['status'] === ContributionStatus::NEW_CONTRIB)
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        // author can only retrieve his contribution rating
        if ($this->loggedUser['role'] === UserRoles::AUTHOR && $contrib['users_id'] !== $this->loggedUser['id'])
        {
            // TODO: flash message
            $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, '/dashboard/mytexts');
            return;
        }

        // retrieve rating overall
        $rat = $this->contributions()->getContributionOverallRating($contrib['id']);

        // append name
        $rat['name'] = $contrib['name'];

        // prepare twig render stuff
        $loader = new Twig_Loader_Filesystem(BASE_DIR.'/app/views/');
        $twig = new Twig_Environment($loader);

        // and send rendered template to browser
        $this->sendResponseJSON(array('content' => $twig->render('components/ratingoverall.twig', $rat)));
    }

    /**
     * Method determining, if currently logged user is listed as reviewer for contribution
     * @param array $contrib
     * @return boolean
     */
    protected function isAuthorizedForReview($contrib)
    {
        $assignments = $this->contributions()->getAssignedContributions($this->loggedUser['id']);
        $found = false;
        // find contrib id in user's assignments
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

    /**
     * Action for viewing contribution
     */
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

            // get already submitted rating
            $this->args->rating = $this->contributions()->getContributionRatingBy($contrib['id'], $this->loggedUser['id']);
            // if no rating present, prepare dummy one
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
            // template also needs to have translated scales
            $this->args->ratingScales = RatingCriteriaScales::getAllScales();
        }

        $this->args->contribution = $contrib;

        // this allows reviewing
        $this->args->rating_allowed = ($this->loggedUser['role'] === UserRoles::REVIEWER && $contrib['status'] === ContributionStatus::SUBMITTED);
    }

    /**
     * Action for downloading contribution document
     */
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

        // this should be sufficient for PDFs, otherwise we need to send binary stream, but
        // almost all browsers should handle this fine
        header("Location: $path");
    }

    /**
     * Admin action for listing all contributions
     */
    public function actionTextlist()
    {
        $this->verifyRole(UserRoles::ADMINISTRATOR);

        $this->args->contribs = $this->contributions()->getAllContributions();
        $this->args->contribCount = $this->args->contribs->rowCount();

        $ratCountArray = array();
        $ratres = $this->contributions()->getSubmittedRatingsCount();
        foreach ($ratres as $rr)
            $ratCountArray[$rr['contributions_id']] = $rr['count'];

        $this->args->ratingCounts = $ratCountArray;

        $this->args->statusTranslations = ContributionStatus::getStatusTranslations();
    }

    /**
     * Validate inputs for all admin contributor actions; retrieves contribution record or false if error
     * @param array $arr source array
     * @param array $fields fields that needs to be present
     * @param bollean|string $statusCheck check for status?
     * @return boolean|array
     */
    protected function validateAdminContribInputs($arr, $fields, $statusCheck = false)
    {
        return $this->validateContribInputs(UserRoles::ADMINISTRATOR, $arr, $fields, $statusCheck);
    }

    /**
     * Validate inputs for all contributor actions; retrieves contribution record or false if error
     * @param array $roles allowed roles
     * @param array $arr source array
     * @param array $fields fields that needs to be present
     * @param bollean|string $statusCheck check for status?
     * @return boolean|array
     */
    protected function validateContribInputs($roles, $arr, $fields, $statusCheck = false)
    {
        // at first, verify roles
        $this->verifyRole($roles);

        // build error fallback link
        $returnLink = ($this->loggedUser['role'] === UserRoles::AUTHOR) ? '/dashboard/mytexts' : '/dashboard/textlist';

        // scan for fields presence
        $missingFields = array();
        if (!Sanitizers::validateFieldsPresence($arr, $fields, $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            // if ajax, send JSON response, if not, redirect to errorlink
            if (!$this->args->is_ajax)
                $this->sendRedirect($returnLink);
            else
                $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return false;
        }

        // retrieve contribution record
        $contrib = $this->contributions()->getContributionById((int)$arr['contribid']);

        // if no contrib found, raise error
        if (!$contrib)
        {
            // TODO: flash message
            if (!$this->args->is_ajax)
                $this->sendRedirect($returnLink);
            else
                $this->sendResponseJSON(array(), AjaxResponseCodes::NOT_FOUND, $returnLink);
            return false;
        }

        // if we need to check status of contribution, proceed
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

    /**
     * Admin action for approve/reject contribution
     */
    public function actionApprovalPOST()
    {
        $contrib = $this->validateAdminContribInputs($_POST, array('contribid', 'approve'), false);
        if (!$contrib)
            return;

        $this->contributions()->setContributionStatus($contrib['id'], ((int)$_POST['approve'] === 1) ? ContributionStatus::ACCEPTED : ContributionStatus::REJECTED);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/textlist');
    }

    /**
     * Renders assignment component, and sends output to client in JSON array
     * @param array $contrib
     */
    protected function renderAssignComponent($contrib)
    {
        // list all existing assignments
        $assign = $this->contributions()->getContributionAssignment($contrib['id']);

        $resarray = array();
        $presentusers = array();
        // fetch assignments, and also get all users, which already have been assigned
        foreach ($assign as $ass)
        {
            $resarray[] = $ass;
            $presentusers[] = $ass['users_id'];
        }

        // fill the rest of array with empty records
        while (count($resarray) < 3)
            $resarray[] = 'none';

        // fetch all ratings already saved
        $ratingByUser = array();
        $ratrows = $this->contributions()->getContributionRatingRows($contrib['id']);
        foreach ($ratrows as $rr)
            $ratingByUser[$rr['users_id']] = $rr;

        // build template args array
        $tplarr = array(
            'id' => $contrib['id'],
            'assign' => $resarray,
            'name' => $contrib['name'],
            'reviewers' => $this->users()->getAllUsersInRole(UserRoles::REVIEWER)->fetchAll(),
            'ratings' => $ratingByUser,
            'assigned' => $presentusers
        );

        // create twig rendering stuff
        $loader = new Twig_Loader_Filesystem(BASE_DIR.'/app/views/');
        $twig = new Twig_Environment($loader);

        // hook all extensions, because we need at least links
        $this->hookTemplateExtensions($twig);

        // render component to JSON stuff and send it to client
        $this->sendResponseJSON(array('content' => $twig->render('components/assign.twig', $tplarr)));
    }

    /**
     * Admin action for retrieving assignments component
     */
    public function actionGetassign()
    {
        $contrib = $this->validateAdminContribInputs($_GET, array('contribid'), ContributionStatus::SUBMITTED);
        if (!$contrib)
            return;

        // no implicit rendering
        $this->setRenderEnabled(false);

        // just send rendered component
        $this->renderAssignComponent($contrib);
    }

    /**
     * Admin action for assigning review to reviewer
     */
    public function actionAssign()
    {
        $contrib = $this->validateAdminContribInputs($_GET, array('contribid', 'userid'), ContributionStatus::SUBMITTED);
        if (!$contrib)
            return;

        $this->setRenderEnabled(false);

        // add assignment
        $this->contributions()->addAssignment($contrib["id"], (int)$_GET['userid']);

        $this->renderAssignComponent($contrib);
    }

    /**
     * Cancels stored assignment to user
     */
    public function actionAssigncancel()
    {
        $contrib = $this->validateAdminContribInputs($_GET, array('contribid', 'userid'), ContributionStatus::SUBMITTED);
        if (!$contrib)
            return;

        $this->setRenderEnabled(false);

        // cancel assignment
        $this->contributions()->removeAssignment($contrib["id"], (int)$_GET['userid']);

        $this->renderAssignComponent($contrib);
    }

    /**
     * Reviewer action for retrieving all items to be reviewed
     */
    public function actionReviewlist()
    {
        $this->verifyRole(UserRoles::REVIEWER);

        $this->args->contribs = $this->contributions()->getAssignedContributions($this->loggedUser['id']);
        $this->args->contribCount = $this->args->contribs->rowCount();
        // get array of contributions already reviewed
        $this->args->reviewed = $this->contributions()->getUserRatedContributions($this->loggedUser['id']);
    }

    /**
     * Reviewer action for sending review report
     */
    public function actionReviewPOST()
    {
        // validate base stuff
        $contrib = $this->validateContribInputs(UserRoles::REVIEWER, $_POST, array('contribid', 'note'), false);
        if (!$contrib)
            return;

        // validate presence of reviewing criterias
        $criterias = array('originality', 'topic', 'structure', 'language', 'recommendation');
        $errorArray = array();
        foreach ($criterias as $cr)
        {
            if (!isset($_POST[$cr]) || !is_numeric($_POST[$cr]))
                $errorArray[$cr] = Sanitizers::createErrorMessage('Toto pole', Sanitizers::IS_REQUIRED, Sanitizers::SUBJECT_IT);
        }

        // if some errors are present, send it to client
        if (count($errorArray) > 0)
        {
            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        // verify assignee for reviewer attempt
        if (!$this->isAuthorizedForReview($contrib))
            return;

        // remove old rating, if any
        $this->contributions()->removeContributionRating($contrib['id'], $this->loggedUser['id']);
        // create new rating
        $this->contributions()->addContributionRating($contrib['id'], $this->loggedUser['id'],
                (int)$_POST['originality'], (int)$_POST['topic'], (int)$_POST['structure'],
                (int)$_POST['language'], (int)$_POST['recommendation'], htmlspecialchars($_POST['note']));

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/reviewlist');
    }
}
