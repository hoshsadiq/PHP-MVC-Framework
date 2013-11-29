<?php

/**
 * @author
 * @copyright 2011
 */

abstract class Valid
{
    /**
     * @return boolean True if the email is valid, otherwise false
     */
    public static function email($email)
    {
        return strstr($email, '@') !== false && filter_var($email, FILTER_VALIDATE_EMAIL) !== '';
    }

    /**
     * @return boolean True if the username is valid, otherwise false
     */
    public static function username($username)
    {
        return strlen($username) >= 6 && strlen($username) <= 25 && !preg_match('/[^a-z0-9_\.]/i', $username);
    }

    /**
     * @return boolean True if the password is valid, otherwise false
     */
    public static function password($password)
    {
        return strlen($password) >= 5 || strlen($password) <= 50;
    }

    /**
     * @return boolean True if the provided post code is valid, otherwise false
     */
    public static function valid_postcode($postcode)
    {
        return preg_match(
            '/^(GIR 0AA)|(((A[BL]|B[ABDHLNRSTX]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[HNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTY]?|T[ADFNQRSW]|UB|W[ADFNRSV]|YO|ZE)[1-9]?[0-9]|((E|N|NW|SE|SW|W)1|EC[1-4]|WC[12])[A-HJKMNPR-Y]|(SW|W)([2-9]|[1-9][0-9])|EC[1-9][0-9]) [0-9][ABD-HJLNP-UW-Z]{2})$/i',
            $postcode
        );
    }

    public static function valid_tel($tel)
    {
        return true;
    }

    public static function website($site)
    {
        return true;
    }

    public static function is_image($loc)
    {
        return true;
    }
}

?>