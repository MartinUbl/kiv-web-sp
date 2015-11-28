<?php

class SignController extends BaseController
{
    public function startup()
    {
        if ($this->loggedUser)
        {
            $this->sendRedirect('/dashboard/'.$this->loggedUser['role']);
            return false;
        }
        
        return parent::startup();
    }

    public function actionIn()
    {
        //
    }

    public function actionInPOST()
    {
        if (!Sanitizers::validateFieldsPresence($_POST, array('username', 'password'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }
        
        $errorArray = array();

        if (($err = Sanitizers::sanitizeUsername($_POST['username'])) !== Sanitizers::OK)
            $errorArray['username'] = Sanitizers::createErrorMessage('Uživatelské jméno', $err, Sanitizers::SUBJECT_IT);

        if (($err = Sanitizers::validatePassword($_POST['password'])) !== Sanitizers::OK)
            $errorArray['password'] = Sanitizers::createErrorMessage('Heslo', $err, Sanitizers::SUBJECT_IT);

        if (count($errorArray) > 0)
        {
            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $user = $this->users()->getUserByUsername($_POST['username']);

        if (!$user)
        {
            $this->sendResponseJSON(array('username' => Sanitizers::createErrorMessage('Uživatelské jméno', Sanitizers::UNKNOWN, Sanitizers::SUBJECT_IT)), AjaxResponseCodes::FORM_ERROR);
            return;
        }

        if (strtoupper($user['password_hash']) !== strtoupper(MiscHelpers::passwordHash($_POST['password'])))
        {
            $this->sendResponseJSON(array('password' => Sanitizers::createErrorMessage('Heslo', Sanitizers::NOT_VALID_DB, Sanitizers::SUBJECT_IT)), AjaxResponseCodes::FORM_ERROR);
            return;
        }

        SessionHolder::setLoggedUserId($user['id']);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/'.$user['role']);
    }

    public function actionRegisterPOST()
    {
        $errorArray = array();

        $missingFields = array();
        if (!Sanitizers::validateFieldsPresence($_POST, array('username', 'email', 'first_name', 'last_name', 'password', 'password_again'), $missingFields))
        {
            foreach ($missingFields as $mf)
                $errorArray[$mf] = Sanitizers::createErrorMessage('Pole', Sanitizers::IS_MISSING, Sanitizers::SUBJECT_IT);

            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        if (($err = Sanitizers::sanitizeUsername($_POST['username'])) !== Sanitizers::OK)
            $errorArray['username'] = Sanitizers::createErrorMessage('Uživatelské jméno', $err, Sanitizers::SUBJECT_IT);

        if (($err = Sanitizers::sanitizeRealName($_POST['first_name'])) !== Sanitizers::OK && $err !== Sanitizers::IS_REQUIRED)
            $errorArray['first_name'] = Sanitizers::createErrorMessage('Křestní jméno', $err, Sanitizers::SUBJECT_IT);

        if (($err = Sanitizers::sanitizeRealName($_POST['last_name'])) !== Sanitizers::OK && $err !== Sanitizers::IS_REQUIRED)
            $errorArray['last_name'] = Sanitizers::createErrorMessage('Příjmení', $err, Sanitizers::SUBJECT_IT);

        if (($err = Sanitizers::validatePassword($_POST['password'])) !== Sanitizers::OK)
            $errorArray['password'] = Sanitizers::createErrorMessage('Heslo', $err, Sanitizers::SUBJECT_IT);

        if (($err = Sanitizers::sanitizeEmail($_POST['email'])) !== Sanitizers::OK)
            $errorArray['email'] = Sanitizers::createErrorMessage('E-mail', $err, Sanitizers::SUBJECT_HE);

        if ($_POST['password'] !== $_POST['password_again'])
            $errorArray['password_again'] = Sanitizers::createErrorMessage('Zadaná hesla', Sanitizers::DO_NOT_MATCH, Sanitizers::SUBJECT_IT);

        if (count($errorArray) > 0)
        {
            $this->sendResponseJSON($errorArray, AjaxResponseCodes::FORM_ERROR);
            return;
        }

        $usr = $this->users()->getUserByUsername($_POST['username']);

        if ($usr)
        {
            $this->sendResponseJSON(array(
                    'username' => Sanitizers::createErrorMessage('Uživatelské jméno', Sanitizers::ALREADY_IN_USE, Sanitizers::SUBJECT_IT)
                ),
                AjaxResponseCodes::FORM_ERROR
            );
            return;
        }

        $id = $this->users()->createUser($_POST['username'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['password'], UserRoles::AUTHOR);

        SessionHolder::setLoggedUserId($id);

        $this->sendResponseJSON(array(), AjaxResponseCodes::OK, '/dashboard/author');
    }
}
