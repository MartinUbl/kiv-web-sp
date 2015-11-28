<?php

/**
 * Homepage controller
 */
class HomepageController extends BaseController
{
    public function actionTopics()
    {
        $this->args->contributions = $this->contributions()->getApprovedContributions();
    }
}
