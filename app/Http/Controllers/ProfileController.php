<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\json;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use SendinBlue\Client\Configuration;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\TransactionalEmailsApi;

class ProfileController extends Controller
{
    /*
    public function getUserDetails(Request $request, $profile_url, $language = null)
    {
        $getUser = User::where('profile_url', '=', $profile_url)->first();
        $infoOfUser =  UserResource::make($getUser->load('topics'));
        if (is_null($language)) {
            $getWebLanguage = "en";
        } else {
            $getWebLanguage = $language;
        }

        $request->session()->put([
            'User' => $infoOfUser,
            'Language' => $getWebLanguage
        ]);
        return view('ProfilePage');
    }
    */

    public function applyForListener (Request $request) {
            $user = $request->user();

        // Configure API key authorization: api-key
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('SENDIBLUE_API_KEY'));

            // Uncomment below line to configure authorization using: partner-key
            // $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('partner-key', 'YOUR_API_KEY');

            $apiInstance = new TransactionalEmailsApi(
                // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
                // This is optional, `GuzzleHttp\Client` will be used as default.
                new Client(),
                $config
            );
            $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail(); // \SendinBlue\Client\Model\SendSmtpEmail | Values to send a transactional email
            $sendSmtpEmail['to'] = array(array('email'=>env('ADMIN_EMAIL'), 'name'=>'Admin'));
            $sendSmtpEmail['templateId'] = 3;
            $sendSmtpEmail['params'] = array('name'=>$user->full_name, 'email'=>$user->email, 'userId'=> $user->id);
            $sendSmtpEmail['headers'] = array('X-Mailin-custom'=>'custom_header_1:custom_value_1|custom_header_2:custom_value_2');

            try {
                $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
                print_r($result);
            } catch (Exception $e) {
                echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
            }
    }
}
