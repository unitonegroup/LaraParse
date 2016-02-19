<?php

namespace LaraParse;

use App\Addons\Parse\Models\LinkedAccount;
use App\Models\User;
use App\PasswordReset;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class UserControllerParse extends ParseBaseController
{

    /**
     * login authentication attempt.
     *
     * @return Response
     */
    public function login()
    {
        $username = Input::get('username');
        $password = Input::get('password');

        $validator = Validator::make(Input::all(),
            array(
                'username' => 'required',
                'password' => 'required|min:3'
            )
        );

        if ($validator->fails()) {
            $validator->errors()->first();

            ParseHelper::throwException(102, ', ');
        }

        // Check data
        $login = Auth::attempt(array(
            'username' => $username,
            'password' => $password
        ));

        // Check Authentication Right
        if ($login) {
            $user = Auth::user();
            $session_id = Session::getId();
            $user->sessionToken = $session_id;
            Session::put("sessionToken", $session_id);
            return new ParseResponse($user);
        } else {
            ParseHelper::throwException(101);
        }
    }

    public function logout()
    {
        $header = apache_request_headers();
        $session_token_from_parse = $header['X-Parse-Session-Token'];
        $laravel_session_id = Session::getId();

        if ($laravel_session_id == $session_token_from_parse) {
            Auth::logout();
        }

        return \Response::json(new \stdClass());
    }


    /**
     * @return User|array|null
     */
    public function getUserBySessionToken()
    {
        if (Auth::check()) {
            $header = apache_request_headers();
            $session_token_from_parse = $header['X-Parse-Session-Token'];
            $laravel_session_id = Session::getId();

            if ($laravel_session_id == $session_token_from_parse) {
                $result_array = Auth::user();
                $result_array['sessionToken'] = Session::getId();

                return $result_array;
            } else {
                $this->logout();

                return ParseHelperClass::error_message_return(209, ", user are logged out");
            }
        }

        return ParseHelperClass::error_message_return(209);
    }


    /**
     * @param string $username
     * @param string $active_code
     *
     * @return string
     */
    public function userActivate($active_code)
    {
        //select user by username and password
        $user = User::where('active_code', '=', $active_code)->first();

        if (count($user)) {

            if (!($user->active)) {
                // active the user
                $user->active = 1;
                $user->active_code = "";
                $user->save();

                return "<h3><br/>Thanks " . $user->username . "..</h3><h2> your account is activated</h2>";
            }
        }

        return "<h1>Please.. Check the link on your email or try sign up again</h1>";
    }


    /**
     * @return array
     */
    public function resetPassword()
    {
        $email = Input::get('email');
        $user = User::where('email', "=", $email)->first();

        if (!(count($user))) {
            return ParseHelperClass::error_message_return(205, $email);
        }

//        \Password::sendResetLink( [ 'email' => $email ], function ( $message ) {
//            $message->subject( "Reset Your Password - Parse server" );
//        } );

        return \Response::json(new \stdClass());
    }


    /**
     * @param null $token
     *
     * @return $this|string
     */
    public function getResetFromEmail($token = null)
    {
        if (is_null($token)) {
            return "<h2>Please check your email link, or try again</h2>";
        }

        return view('auth.reset')->with('token', $token);
    }


    /**
     * @return $this|array|\Illuminate\Http\RedirectResponse
     */
    public function postResetFromEmail()
    {

        $email = Input::get('email');
        $password = Input::get('password');
        $password_confirmation = Input::get('password_confirmation');
        $token = Input::get('token');
        Validator::make(Input::all(), [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed',
        ]);


        if ($password != $password_confirmation) {
            return ParseHelperClass::error_message_return(141, "check password value");
        }

        $user_token = PasswordReset::where('email', '=', $email)->where('token', "=", $token)->first();

        if (!$user_token) {
            return redirect()->to('/1/reset-password/' . $token)
                ->withInput()
                ->with('error_message', 'please check the email address')
                ->withErrors(['email' => $email]);
        }

        User::where('email', '=', $email)->update(array('password' => Hash::make($password)));

        return redirect()->to('/1/reset-password/' . $token)->with('success_message', 'Your password are reset');
    }


    /**
     * Create New User
     * @return User|json
     */
    public function create($className = "User")
    {
        $this->prepareClassMetadata($className);
        $inputs = new Collection($this->getInputs());

        // by default, the post method create new user
        $code = 201;

        // if no authData, it must be a regular signup
        if ($inputs->has('username') && $inputs->has('password')) {
            $this->validateSignupRequest($inputs);

            // hash the plain password before save in the database
            $hashedPassword = Hash::make($inputs->get('password'));
            $inputs->setoffsetSet('password', $hashedPassword);

            // save the user regularly
            $parameters = array_except($inputs->toArray(), array('authData', 'profile'));
            $this->save($className, $parameters);
        } elseif ($authData = $inputs->get('authData')) {
            // try to get currently linked account
            // otherwise, create new one
            $code = $this->getOrCreateUserFromLink($authData);
        }

        if (\Auth::loginUsingId($this->object->getKey())) {
            // wherever the user created using regular username and password
            // or from linked data, we need to return 200 for current user
            // and 201 for newly created user

            // todo return the session, and the authData
            return new ParseResponse($this->object, $code);
        }else{
            ParseHelper::throwException(141, "user not created");
        }
    }


    /**
     * ParseController::UpdateObject
     *
     * @param $objectId
     * @param string $className
     * @return array
     */
    public function update($objectId, $className = "User")
    {
        // todo check the current active session!
        // we must not being able to update the user without active session?

        $this->prepareClassMetadata($className);
        $inputs = new Collection($this->getInputs());

        // save the user regularly
        $parameters = array_except($inputs->toArray(), array('authData', 'profile'));
        $this->save($className, $parameters, $objectId);

        if ($authData = $inputs->get('authData')) {
            // todo , support multi authData
            foreach($authData as $type => $details){
                $details = is_null($details)? $details : new Collection($details);

                // get the current user linked from this type or create new object
                $account = LinkedAccount::firstOrNew(array(
                    "user_id" => $this->object->getKey(),
                    "auth" => $type
                ));

                // if null and not final, or final and user have user/pass delete the link
                if(is_null($details)){
                    $this->unlinkAccount($account);
                }else{
                    $this->linkAccount($account, $details, $type, $inputs, $authData);
                }
            }
        }

        // the anonymous must be deleted once any other type created!
        // we must change the role, and update the user name
        $anonymousAccount = LinkedAccount::first(array(
            "user_id" => $this->object->getKey(),
            "auth" => "anonymous"
        ));
        if($this->object->linked_accounts()->count() > 1){
            $anonymousAccount->delete();
            // todo remove anonymous role and check the username
        }


        return new ParseResponse($this->object);
    }

    /**
     * @param $inputs
     * @throws \Exception
     */
    private function validateSignupRequest($inputs)
    {
        $validator = Validator::make($inputs,
            array(
                'username' => 'required|unique:users',
                'password' => 'required|min:3',
            )
        );

        if ($validator->fails()) {
            ParseHelper::throwException(102, 'Please check your input data, ' . $validator->errors()->first());
        }
    }

    /**
     * @param Collection $inputs
     * @return mixed
     */
    private function getAuthData($inputs)
    {
        $auth_data = $inputs->get('authData');
        $inputs->forget('authData');

        return $auth_data;
    }

    /**
     * @param $authData
     */
    private function getOrCreateUserFromLink($authData)
    {
        $code = 200;

        $type = array_keys($authData)[0];
        $details = new Collection($authData[$type]);

        $account = LinkedAccount::firstOrNew(array(
            'auth'    => $type,
            'auth_id' => $details->get('id')
        ));
        $account->auth_data = json_encode($details);
        $account->user_data = $details->get('profile');

        // if we have not found an already linked user, we must create new one
        if (!$account->exists) {
            $this->object->username = $type . "_" . $details->get('id');
            if ($type == 'anonymous') {
                $this->object->role = 'anonymous';
            }
            $this->object->save();
            $account->user()->associate($this->object);
            $code = 201;
        }
        // create new link or update the current one
        $account->save();
        $this->object = $account->user;

        return $code;
    }

    /**
     * @param $account
     * @throws \Exception
     */
    private function unlinkAccount($account)
    {
        $count = $this->object->linked_accounts()->count();
        if ($count == 1) {
            ParseHelper::throwException(105, "cannot unlink the last account");
        } else {
            $account->delete();
        }
    }

    /**
     * @param $account
     * @param $details
     * @param $type
     * @param $inputs
     * @param $authData
     * @throws \Exception
     */
    private function linkAccount($account, $details, $type, $inputs, $authData)
    {
        // if new link or update link with different auth id, check for duplication
        $this->validateLink($account, $details, $type);

        // create or update the account
        $account->auth_id = $details->get('id');
        $account->auth_data = json_encode($details);
        $account->user_data = $inputs->get('profile');
        $account->save();
    }

    /**
     * @param $account
     * @param $details
     * @param $type
     * @throws \Exception
     */
    private function validateLink($account, $details, $type)
    {
        if ($account->auth_id != $details->get('id')) {
            $duplicatedAccount = LinkedAccount::first(array(
                "auth"    => $type,
                "auth_id" => $details->get('id')
            ));
            if ($duplicatedAccount) {
                // todo can we merge the two users?
                ParseHelper::throwException(105,
                    "this auth ID already linked with user:" . $duplicatedAccount->user_id);
            }
        }
    }


}