<?php

class BaseSecuredController extends BaseController
{
    public function startup()
    {
        if (!SessionHolder::getLoggedUserId())
        {
            $this->sendRedirect('/sign/in');
            return false;
        }

        return parent::startup();
    }
}
