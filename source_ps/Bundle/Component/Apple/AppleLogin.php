<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Enamoo S5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link      http://www.godo.co.kr
 */

namespace Bundle\Component\Apple;

use Bundle\Component\Apple\JwtUtil;
use Framework\Utility\ComponentUtils;
use Bundle\Component\Validator\Validator;
use Framework\Http\Response;


/**
 * Class AppleLogin
 * @package Bundle\Component\Apple
 * @author <sirume92@godo.co.kr>
 */
class AppleLogin
{
    /**
     * @var
     */
    private $__logger;
    private $__db;
    private $__request;

    private $__config;
    private $__keyFile;
    private $__teamId;
    private $__clientId;
    private $__keyId;

    public $clientSecret;
    public $useFl;

    const SESSION_ACCESS_TOKEN = 'apple_access_token';
    const SESSION_USER_PROFILE = 'apple_user_profile';
    const SESSION_USER_NAME = 'apple_user_name';
    const SESSION_APPLE_SERVICE_CODE = 'apple_service_code';
    const SESSION_APPLE_HACK = 'apple_hack';
    const MYAPP_APPLE_AUTO_LOGIN_FLAG = 'saveAutoLogin';
    const MYAPP_APPLE_USER_ID_TOKEN = 'apple_id_token';

    const CONTROLLER_PATH = 'member/apple/apple_login.php';
    const APPLE_AUTH_TOKEN_PATH = 'https://appleid.apple.com/auth/token';

    public function __construct()
    {
        $this->__logger = \App::getInstance('logger')->channel('appleLogin');
        $this->__db = \App::getInstance('DB');
        $this->__request = \App::getInstance('request');
        $this->__config = gd_policy('member.appleLogin');

        if (empty($this->__config) === false) {
            $this->__teamId = $this->__config['team_id'];
            $this->__clientId = $this->__config['client_id'];
            $this->__keyId = $this->__config['key_id'];
            $this->__keyFile = $this->__config['key_file'];
            $this->useFl = $this->__config['useFl'];
        }

//        if (fileExists("/config/app/key.p8")) {
//            $this->__keyFile = file("/config/app/key.p8");
//        }
    }

    /**
     * appleLoginConfigValidator
     *
     * @param array $data
     * @return array
     */
    private function appleLoginConfigValidator(array &$data)
    {
        $validator = new Validator();
        $validator->init();

        $validator->add('useFl', 'alpha', true);
        $validator->add('client_id', '');
        $validator->add('key_file', '');
        $validator->add('key_file_name', '');
        $validator->add('key_id', 'alphaNum');
        $validator->add('team_id', 'alphaNum');


        $result['result'] = $validator->actAfterIsset($data, true);

        if ($result['result'] === false) {
            $result['code'] = Response::HTTP_BAD_REQUEST;
            $result['msg'] = sprintf(__('%s'), implode(',', $validator->errors));
        }

        return $result;
    }

    /**
     * API로 애플로그인 설정 (미사용)
     * setAppleLoginConfig
     *
     * @return array
     */
    public function setAppleLoginConfig()
    {
        $data = $this->__request->post()->toArray();
        $this->__logger->info("AppleLogin Policy Request", [$data]);
        $result = $this->appleLoginConfigValidator($data);

        // 유효성 검사 실패
        if ($result['result'] === false) return $result;

        $this->__config = array_merge($this->__config, $data);
        ComponentUtils::setPolicy('member.appleLogin', $this->__config);

        return $this->__config;
    }

    /**
     * getAppleLoginUseConfig
     *
     * @return boolean
     */
    public function getAppleLoginUseConfig()
    {
        return $this->useFl == 'y';
    }

    /**
     * getClientSecret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret = $this->generateClientSecret();
    }

    /**
     * getClientId
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->__clientId;
    }

    /**
     * generateJWT
     *
     * @return string
     */
    public function generateClientSecret()
    {
//        $keyFile = "-----BEGIN PRIVATE KEY-----\n
//            MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQgfy9EmE7xPXzKfloS\n
//            NVtLJ2kVmpQ+SN1CeLxAFN+9XLCgCgYIKoZIzj0DAQehRANCAARDWQ40dC0QXFLe\n
//            BPSv006I9hHCIY1MXaJSDlV7EGYoqGbIXLc/YVRcwmQTnjz9kbOpDS9q1Z08uwUC\n
//            cZImCDRr\n
//            -----END PRIVATE KEY-----";

        # Define the JWT's headers and claims
        $header = [
            # The token must be signed with your key
            'kid' => $this->__keyId,
            'alg' => 'ES256',
        ];
        $claims = [
            # The token is issued by your Apple team
            'iss' => $this->__teamId,
            # The token applies to Apple ID authentication
            'aud' => 'https://appleid.apple.com',
            # The token is scoped to your application
            'sub' => $this->__clientId,
            # The token is valid immediately
            'iat' => time(),
            # The token expires in 6 months (maximum allowed)
            'exp' => time() + 86400*90,
        ];

        # Read in the key and generate the JWT
        $privKey = openssl_pkey_get_private($this->__keyFile);

        if ($privKey === false) {
            $this->__logger->error("SSL Key error ", [openssl_error_string()]);
            return null;
        }

        $payload = $this->encode(json_encode($header)) . '.' . $this->encode(json_encode($claims));
        $signature = '';
        $makeSignature = openssl_sign($payload, $signature, $privKey, OPENSSL_ALGO_SHA256);
        if (!$makeSignature) {
            $this->__logger->error("Fail to make Signature ", [$payload]);
            return null;
        }

        $raw_signature = JwtUtil::fromDER($signature, 64);
        $this->clientSecret = $payload.'.'.$this->encode($raw_signature);

        return $this->clientSecret;
    }

    /**
     * encode
     *
     * @param $data
     *
     * @return string
     */
    public function encode($data) {
        $encoded = strtr(base64_encode($data), '+/', '-_');
        return rtrim($encoded, '=');
    }

    /**
     * setToken
     *
     * @param string    $authorizationCode
     * @param string    $client_id  # ios13 이상 옵션
     *
     * @return array
     */
    public function getAccessToken($authorizationCode, $client_id = '')
    {
        // ios 13 이상에서 client_id를 패키지명으로 받도록 예외 처리
        if ($client_id) $this->__clientId = $client_id;

        $this->clientSecret = $this->getClientSecret();

        $redirect_domain = $this->__request->isMobile() ? URI_MOBILE : 'https://' . gd_policy('basic.info')['mallDomain'] . '/';
        // 마이앱인 경우 포트 삭제
        if ($redirect_domain == URI_MOBILE && $this->__request->isMyapp()) {
            $url_array = parse_url($redirect_domain);
            $redirect_domain = $url_array['scheme'] . '://' . $url_array['host'] . '/';
            unset($url_array);
        }
        $redirect_url = $redirect_domain . self::CONTROLLER_PATH;

        $data = [
            'client_id' => $this->__clientId,
            'client_secret' => $this->clientSecret,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_url
        ];
        $this->__logger->info("Apple connection Request : ", [self::APPLE_AUTH_TOKEN_PATH, http_build_query($data)]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::APPLE_AUTH_TOKEN_PATH);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOutput = curl_exec($ch);
        curl_close($ch);

        $this->__logger->info("Apple connection Response : ", [$serverOutput]);

        /**
         "access_token": "xxxxxxxxxxxxxxx",
         "token_type": "Bearer",
         "expires_in": 3600,
         "refresh_token": "yyyyyyyyyyyy",
         "id_token": "zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz"
         or
         "error":"error_message"
        */
        $result = json_decode($serverOutput, true);
        $result['user_info'] = $this->decryptIdToken($result['id_token']);
        $result['user_info']['uuid'] = $result['user_info']['sub'];

        return $result;
    }

    /**
     * decryptIdToken
     *
     * @param string $idToken
     *
     * @return array
     */
    public function decryptIdToken(string $idToken)
    {
        $claims = explode('.', $idToken);
        $claims = $claims[1];
        $claims = json_decode(base64_decode($claims), true);
        /**
         * [iss] => https://appleid.apple.com
         * [aud] => applesignintest.myapp.godo.com
         * [exp] => 1596679196
         * [iat] => 1596678596
         * [sub] => xxxxxx.yyyyyyyyyy.zzzz
         * [at_hash] => Y_XNxkTzes-W6EusuJDHLQ
         * [email] => abc@def.com
         * [email_verified] => true
         * [auth_time] => 1596678594
         * [nonce_supported] => 1
         */
        return $claims;
    }

    /**
     * validateRefreshToken
     *
     * @param string $refreshToken
     * @param string    $client_id  # ios13 이상 옵션
     *
     * @return boolean
     */
    public function validateRefreshToken(string $refreshToken, $client_id = '')
    {
        // ios 13 이상에서 client_id를 패키지명으로 받도록 예외 처리
        if ($client_id) $this->__clientId = $client_id;

        $data = [
            'client_id' => $this->__clientId,
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];

        $this->__logger->info("Apple connection Request : ", [self::APPLE_AUTH_TOKEN_PATH, http_build_query($data)]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::APPLE_AUTH_TOKEN_PATH);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOutput = curl_exec($ch);
        curl_close($ch);

        $this->__logger->info("Apple connection Response : ", [$serverOutput]);

        /**
        "access_token": "xxxxxxxxxxxxxxx",
        "token_type": "Bearer",
        "expires_in": 3600,
        "refresh_token": "yyyyyyyyyyyy",
        "id_token": "zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz"
        or
        "error":"error_message"
         */
        $result = json_decode($serverOutput, true);

        if ($result['error']) {
            return false;
        }

        return true;
    }

    /**
     * logger
     *
     * @param array $data
     */
    public function logger(array $data)
    {
        $this->__logger->info("애플 로그인 로그 : ", [$data]);
    }
}
