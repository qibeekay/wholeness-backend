<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Users;
use App\Helpers\Utils;
use Google_Client;
use Google_Service_Oauth2;

class AuthController
{
    private $client;
    private $db;
    private $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;

        // Initialize Google Client
        $this->client = new Google_Client();
        $this->client->setClientId($this->config['services']['client_id']);
        $this->client->setClientSecret($this->config['services']['client_secret']);
        $this->client->setRedirectUri('postmessage');


        $this->client->addScope('email');
        $this->client->addScope('profile');
    }

    /**
     * Redirect to Google OAuth login
     */
    public function redirectToGoogle()
    {
        $authUrl = $this->client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        if (!isset($_GET['code'])) {
            return Utils::sendErrorResponse('Authorization code not provided', 400);
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode
            ($_GET['code']);

            // ---- show what we are about to send ----
            // return Utils::sendErrorResponse(
            //     'Redirect URI that will be sent to Google: ' .
            //     $this->client->getRedirectUri(),
            //     400
            // );

            if (isset($token['error'])) {
                return Utils::sendErrorResponse('Error fetching access token: ' . $token['error'], 400);
            }


            $this->client->setAccessToken($token);

            // Get user info from Google
            $oauth2 = new Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            // Create or update user in database
            $user = new Users(
                $this->db,
                $userInfo->name,
                $userInfo->email,
                $userInfo->id,
                $userInfo->picture
            );

            if ($user->findOrCreateGoogleUser()) {
                $userData = $user->getUserByGoogleId();
                if ($userData) {
                    // Start session or generate JWT token
                    session_start();
                    $_SESSION['user'] = $userData;
                    return Utils::sendSuccessResponse('Login successful', $userData);
                }
            }

            return Utils::sendErrorResponse('Failed to save user data', 500);
        } catch (\Exception $e) {
            return Utils::sendErrorResponse('Authentication error: ' . $e->getMessage(), 500);
        }
    }

}