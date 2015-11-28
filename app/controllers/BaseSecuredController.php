<?php

/**
 * Base for all controllers, that need to have user logged in
 */
class BaseSecuredController extends BaseController
{
    /**
     * Overriden startup method to verify, if the user has logged in
     * @return boolean
     */
    public function startup()
    {
        // if no user stored in session, send to login page
        if (!SessionHolder::getLoggedUserId())
        {
            $this->sendRedirect('/sign/in');
            return false;
        }

        return parent::startup();
    }
}
