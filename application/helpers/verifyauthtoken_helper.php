<?php

if (!function_exists('verifyAuthToken')) {
    function verifyAuthToken($token)
    {
        $jwt = new JWT();
        $jwtSecret = 'KBTradELinkS_HS256';
        $verification = $jwt->decode($token, $jwtSecret, 'HS256');

        $verification_json = $jwt->jsonEncode($verification);
        return $verification;
    }
}
