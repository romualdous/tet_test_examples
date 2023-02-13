<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function getTranslation($language,$route) {

        $get_languages = file_get_contents("https://translation-tool.ccstudio.lv/VoiceApp/texts_export_backend.php");
        $array = json_decode($get_languages, true);
        array_push($route,$language ?? 'en');
        $count = count($route);
        for ($i = 0;$i <= $count-1;$i++)
        {
            $array = $array[$route[$i]];
        }
        return $array;

    }
    /**
     * @OA\Info(
     *      version="1",
     *      title="Laravel OpenApi Demo Documentation",
     *      description="L5 Swagger OpenApi description",
     * )
     *
     * @OA\Tag(
     *     name="Projects",
     *     description="API Endpoints of Projects"
     * )
     *
     */
}
