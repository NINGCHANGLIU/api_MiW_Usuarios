<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Interface ApiResultsCommandInterface
 *
 * P4: Results 写入与修改（POST / PUT）
 */
interface ApiResultsCommandInterface
{
    /**
     * **POST** Action
     * Summary: Creates a new Result resource
     */
    public function postAction(Request $request): Response;

    /**
     * **PUT** Action
     * Summary: Updates a Result resource by id
     */
    public function putAction(Request $request, int $resultId): Response;
}
