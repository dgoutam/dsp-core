<?php

/**
 * REST handler interface for services
 */

interface iRestHandler
{
    // Controller based methods

    public function actionGet();

    public function actionPost();

    public function actionPut();

    public function actionMerge();

    public function actionDelete();
}
