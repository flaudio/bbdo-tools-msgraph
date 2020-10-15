<?php

namespace App\Http\Controllers\MSGraph;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

use App\TokenStore\TokenCache;

use App\User;
//use Auth;

class AuthController extends Controller
{
    public function signin()
    {
        // Initialize the OAuth client
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => env('OAUTH_APP_ID'),
            'clientSecret'            => env('OAUTH_APP_PASSWORD'),
            'redirectUri'             => env('OAUTH_REDIRECT_URI'),
            'urlAuthorize'            => env('OAUTH_AUTHORITY').env('OAUTH_AUTHORIZE_ENDPOINT'),
            'urlAccessToken'          => env('OAUTH_AUTHORITY').env('OAUTH_TOKEN_ENDPOINT'),
            'urlResourceOwnerDetails' => '',
            'scopes'                  => env('OAUTH_SCOPES')
        ]);

        $authUrl = $oauthClient->getAuthorizationUrl();

        // Save client state so we can validate in callback
        session(['oauthState' => $oauthClient->getState()]);

        // Redirect to AAD signin page
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        // Validate state
        $expectedState = session('oauthState');
        $request->session()->forget('oauthState');
        $providedState = $request->query('state');

        if (!isset($expectedState)) {
            // If there is no expected state in the session,
            // do nothing and redirect to the home page.
            return redirect('/');
        }

        if (!isset($providedState) || $expectedState != $providedState) {
            return redirect('/')
            ->with('error', 'Invalid auth state')
            ->with('errorDetail', 'The provided auth state did not match the expected value');
        }

        // Authorization code should be in the "code" query param
        $authCode = $request->query('code');
        if (isset($authCode)) {
            // Initialize the OAuth client
            $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => env('OAUTH_APP_ID'),
                'clientSecret'            => env('OAUTH_APP_PASSWORD'),
                'redirectUri'             => env('OAUTH_REDIRECT_URI'),
                'urlAuthorize'            => env('OAUTH_AUTHORITY').env('OAUTH_AUTHORIZE_ENDPOINT'),
                'urlAccessToken'          => env('OAUTH_AUTHORITY').env('OAUTH_TOKEN_ENDPOINT'),
                'urlResourceOwnerDetails' => '',
                'scopes'                  => env('OAUTH_SCOPES')
            ]);

            try {
                // Make the token request
                $accessToken = $oauthClient->getAccessToken('authorization_code', [
                    'code' => $authCode
                ]);

                $graph = new Graph();
                $graph->setAccessToken($accessToken->getToken());

                $user = $graph->createRequest('GET', '/me')
                ->setReturnType(Model\User::class)
                ->execute();



                $finduser = User::where('provider_id', $user->getId())->first();

                if($finduser){

                    //Auth::login($finduser);

                    //return redirect('/home');

                    $photo = $graph->createRequest("GET", "/me/photo/\$value")->execute();
                    $photo = $photo->getRawBody();

                    $meta = $graph->createRequest("GET", "/me/photos/48x48")->execute();
                    $meta = $meta->getBody();

                    $profile_photo_src = 'data:'.$meta['@odata.mediaContentType'].';base64,'.base64_encode($photo);

                }else{

                    $photo = $graph->createRequest("GET", "/me/photo/\$value")->execute();
                    $photo = $photo->getRawBody();

                    $meta = $graph->createRequest("GET", "/me/photos/48x48")->execute();
                    $meta = $meta->getBody();

                    $profile_photo_src = 'data:'.$meta['@odata.mediaContentType'].';base64,'.base64_encode($photo);
                    //die('<img src="'.$profile_photo_src.'">');
                    $newUser = User::create([
                        'firstname' => $user->getGivenName(),
                        'lastname' => $user->getSurname(),
                        'email' => null !== $user->getMail() ? $user->getMail() : $user->getUserPrincipalName(),
                        'provider' => 'ms-graph',
                        'provider_id'=> $user->getId(),
                        'manager' => null != $user->getManager() ? $user->getManager() : null,
                        //'profile_photo' => $profile_photo_src,
                    ]);

                    //Auth::login($newUser);

                    //return redirect('/home');
                }

                $tokenCache = new TokenCache();
                $tokenCache->storeTokens($accessToken, $user, $profile_photo_src);

                return redirect('/');
            }
            catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                return redirect('/')
                ->with('error', 'Error requesting access token')
                ->with('errorDetail', $e->getMessage());
            }
        }

        return redirect('/')
        ->with('error', $request->query('error'))
        ->with('errorDetail', $request->query('error_description'));
    }

    public function signout()
    {
        $tokenCache = new TokenCache();
        $tokenCache->clearTokens();
        return redirect('/');
    }
}
