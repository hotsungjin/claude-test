<?php


namespace Bundle\Component\Mobile;


use Framework\Utility\ComponentUtils;

/**
 * Class HpAuth
 * @package Bundle\Component\Mobile
 * @author  jongchan-hong
 */
class HpAuthSecurity
{
    const CONFIG_KEY = "manage.security";
    const CONFIG_JSON_KEY = "authCellPhoneFl";
    const TRUE = "y";
    const FALSE = "n";

    /**
     * @return bool
     */
    public static function isApply(){
        $policy = ComponentUtils::getPolicy(self::CONFIG_KEY);
        return $policy[self::CONFIG_JSON_KEY] == self::TRUE;
    }

}