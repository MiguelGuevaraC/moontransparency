<?php

namespace App\Http\Controllers;

use App\Traits\Filterable;
use App\Traits\HandleServiceTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 *  @OA\Info(
 *      title="API's Moon Group Transparency",
 *      version="1.0.0",
 *      description="API's for transparency of Moon Group",
 * ),
 *   @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     in="header",
 *     name="Authorization",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 * ),

 */

class Controller extends BaseController
{
    use HandleServiceTrait, AuthorizesRequests, DispatchesJobs, ValidatesRequests,Filterable;
}
