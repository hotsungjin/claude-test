<?php


namespace Bundle\Component\Member;

use Encryptor;
use Component\Member\Member;
use Framework\Utility\ComponentUtils;

/**
 * Class MemberCertificationValidation
 * @package Bundle\Component\Member
 * @author  jongchan-hong
 */
class MemberCertificationValidation extends \Component\AbstractComponent
{
    protected $_memId;
    protected $_session;
    protected $_sessionUserCertification;

    const TOKEN_NAME = "cToken";
    const TOKEN_MEMBER_ID_KEY = "memId";
    const TOKEN_TIME_ISSUED_KEY = "tokenTimeIssued";
    const CONFIG_KEY = "manage.security";
    const CONFIG_JSON_KEY = "memberCertificationValidationFl";

    const TRUE = "y";
    const FALSE = "n";

    /**
     * Certification constructor.
     * @param $memId
     */
    public function __construct($memId)
    {
        parent::__construct();
        $this->_memId = $memId;
        $this->_session = \App::getInstance('session');
        $this->_sessionUserCertification = $this->_session->get(Member::SESSION_USER_CERTIFICATION);
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        $token = Encryptor::encrypt($this->getTokenPlainText());
        $this->setTokenSession($token);
        return $token;
    }

    /**
     * @param $encToken
     * @return bool
     */
    public function validateToken($encToken){
        if ($this->_sessionUserCertification[self::TOKEN_NAME] != $encToken){
            return false;
        }
        return $this->_memId == $this->getTokenMemId($encToken);
    }
    /**
     * @return bool
     */
    public static function isApply(){
        $policy = ComponentUtils::getPolicy(self::CONFIG_KEY);
        return $policy[self::CONFIG_JSON_KEY] == self::TRUE;
    }


    /**
     * @param $encToken
     * @return string
     */
    protected function getTokenMemId($encToken)
    {
        $tokenInfo = $this->getTokenInfo($encToken);
        return $tokenInfo[self::TOKEN_MEMBER_ID_KEY];
    }

    /**
     * @return string
     */
    protected function getTokenPlainText()
    {
        $cToken = [
            self::TOKEN_MEMBER_ID_KEY => $this->_memId,
            self::TOKEN_TIME_ISSUED_KEY  => time()
        ];
        return json_encode($cToken);
    }

    /**
     * @param $encToken
     * @return string[]
     */
    protected function getTokenInfo($encToken)
    {
        $plainText = Encryptor::decrypt($encToken);
        return json_decode($plainText, true);
    }

    /**
     * @param string $token
     */
    protected function setTokenSession(string $token)
    {
        $this->_sessionUserCertification[self::TOKEN_NAME] = $token;
        $this->_session->set(Member::SESSION_USER_CERTIFICATION, $this->_sessionUserCertification);
    }

}