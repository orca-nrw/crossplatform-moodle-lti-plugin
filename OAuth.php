<?php
// This file is part of BasicLTI4Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu
//
// OAuth.php is distributed under the MIT License
//
// The MIT License
//
// Copyright (c) 2007 Andy Smith
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace moodle\mod\orcalti;// Using a namespace as the basicLTI module imports classes with the same names.

defined('MOODLE_INTERNAL') || die;

$oauthlastcomputedsignature = false;

/* Generic exception class
 */
class OAuthException extends \Exception {
    // Pass.
}

class OAuthConsumer {
    public $key;
    public $secret;

    public function __construct($key, $secret, $callbackurl = null) {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callbackurl;
    }

    public function __toString() {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}

class OAuthToken {
    // Access tokens and request tokens.
    public $key;
    public $secret;

    /**
     * key = the token
     * secret = the token secret
     */
    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with
     */
    public function to_string() {
        return "oauth_token=" .
        OAuthUtil::urlencode_rfc3986($this->key) .
        "&oauth_token_secret=" .
        OAuthUtil::urlencode_rfc3986($this->secret);
    }

    public function __toString() {
        return $this->to_string();
    }
}

class OAuthSignatureMethod {
    public function check_signature(&$request, $consumer, $token, $signature) {
        $built = $this->build_signature($request, $consumer, $token);
        return $built == $signature;
    }
}

class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
    public function get_name() {
        return "HMAC-SHA1";
    }

    public function build_signature($request, $consumer, $token) {
        global $oauthlastcomputedsignature;
        $oauthlastcomputedsignature = false;

        $basestring = $request->get_signature_base_string();
        $request->base_string = $basestring;

        $keyparts = array(
            $consumer->secret,
             ($token) ? $token->secret : ""
        );

        $keyparts = OAuthUtil::urlencode_rfc3986($keyparts);
        $key = implode('&', $keyparts);

        $computedsignature = base64_encode(hash_hmac('sha1', $basestring, $key, true));
        $oauthlastcomputedsignature = $computedsignature;
        return $computedsignature;
    }

}

class OAuthSignatureMethod_PLAINTEXT extends OAuthSignatureMethod {
    public function get_name() {
        return "PLAINTEXT";
    }

    public function build_signature($request, $consumer, $token) {
        $sig = array(
            OAuthUtil::urlencode_rfc3986($consumer->secret)
        );

        if ($token) {
            array_push($sig, OAuthUtil::urlencode_rfc3986($token->secret));
        } else {
            array_push($sig, '');
        }

        $raw = implode("&", $sig);
        // For debug purposes.
        $request->base_string = $raw;

        return OAuthUtil::urlencode_rfc3986($raw);
    }
}

class OAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod {
    public function get_name() {
        return "RSA-SHA1";
    }

    protected function fetch_public_cert(&$request) {
        // Not implemented yet, ideas are.
        // (1) do a lookup in a table of trusted certs keyed off of consumer.
        // (2) fetch via http using a url provided by the requester.
        // (3) some sort of specific discovery code based on request.
        //
        // Either way should return a string representation of the certificate.
        throw new OAuthException("fetch_public_cert not implemented");
    }

    protected function fetch_private_cert(&$request) {
        // Not implemented yet, ideas are.
        // (1) do a lookup in a table of trusted certs keyed off of consumer.
        //
        // Either way should return a string representation of the certificate.
        throw new OAuthException("fetch_private_cert not implemented");
    }

    public function build_signature(&$request, $consumer, $token) {
        $basestring = $request->get_signature_base_string();
        $request->base_string = $basestring;

        // Fetch the private key cert based on the request.
        $cert = $this->fetch_private_cert($request);

        // Pull the private key ID from the certificate.
        $privatekeyid = openssl_get_privatekey($cert);

        // Sign using the key.
        $ok = openssl_sign($basestring, $signature, $privatekeyid);

        // Release the key resource.
        openssl_free_key($privatekeyid);

        return base64_encode($signature);
    }

    public function check_signature(&$request, $consumer, $token, $signature) {
        $decodedsig = base64_decode($signature);

        $basestring = $request->get_signature_base_string();

        // Fetch the public key cert based on the request.
        $cert = $this->fetch_public_cert($request);

        // Pull the public key ID from the certificate.
        $publickeyid = openssl_get_publickey($cert);

        // Check the computed signature against the one passed in the query.
        $ok = openssl_verify($basestring, $decodedsig, $publickeyid);

        // Release the key resource.
        openssl_free_key($publickeyid);

        return $ok == 1;
    }
}

class OAuthRequest {
    private $parameters;
    private $httpmethod;
    private $httpurl;
    // For debug purposes.
    public $basestring;
    public static $version = '1.0';
    public static $postinput = 'php://input';

    public function __construct($httpmethod, $httpurl, $parameters = null) {
        @$parameters || $parameters = array();
        $this->parameters = $parameters;
        $this->httpmethod = $httpmethod;
        $this->httpurl = $httpurl;
    }

    /**
     * attempt to build up a request from what was passed to the server
     */
    public static function from_request($httpmethod = null, $httpurl = null, $parameters = null) {
        $scheme = (!is_https()) ? 'http' : 'https';
        $port = "";
        if ($_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443" && strpos(':', $_SERVER['HTTP_HOST']) < 0) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        @$httpurl || $httpurl = $scheme .
        '://' . $_SERVER['HTTP_HOST'] .
        $port .
        $_SERVER['REQUEST_URI'];
        @$httpmethod || $httpmethod = $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to.
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own.
        // Parsed parameter-list.
        if (!$parameters) {
            // Find request headers.
            $requestheaders = OAuthUtil::get_headers();

            // Parse the query-string to find GET parameters.
            $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

            $ourpost = $_POST;
            // Add POST Parameters if they exist.
            $parameters = array_merge($parameters, $ourpost);

            // We have a Authorization-header with OAuth data. Parse the header.
            // and add those overriding any duplicates from GET or POST.
            if (@substr($requestheaders['Authorization'], 0, 6) == "OAuth ") {
                $headerparameters = OAuthUtil::split_header($requestheaders['Authorization']);
                $parameters = array_merge($parameters, $headerparameters);
            }

        }

        return new OAuthRequest($httpmethod, $httpurl, $parameters);
    }

    /**
     * pretty much a helper function to set up the request
     */
    public static function from_consumer_and_token($consumer, $token, $httpmethod, $httpurl, $parameters = null) {
        @$parameters || $parameters = array();
        $defaults = array(
            "oauth_version" => self::$version,
            "oauth_nonce" => self::generate_nonce(),
            "oauth_timestamp" => self::generate_timestamp(),
            "oauth_consumerkey" => $consumer->key
        );
        if ($token) {
            $defaults['oauth_token'] = $token->key;
        }

        $parameters = array_merge($defaults, $parameters);

        // Parse the query-string to find and add GET parameters.
        $parts = parse_url($httpurl);
        if (isset($parts['query'])) {
            $qparms = OAuthUtil::parse_parameters($parts['query']);
            $parameters = array_merge($qparms, $parameters);
        }

        return new OAuthRequest($httpmethod, $httpurl, $parameters);
    }

    public function set_parameter($name, $value, $allowduplicates = true) {
        if ($allowduplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list.
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string).
                // Into an array so we can add the duplicates.
                $this->parameters[$name] = array($this->parameters[$name]);
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    public function get_parameter($name) {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    public function get_parameters() {
        return $this->parameters;
    }

    public function unset_parameter($name) {
        unset($this->parameters[$name]);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     * @return string
     */
    public function get_signable_parameters() {
        // Grab all parameters.
        $params = $this->parameters;

        // Remove oauth_signature if present.
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.").
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);
    }

    /**
     * Returns the base string of this request
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and the concated with &.
     */
    public function get_signature_base_string() {
        $parts = array(
            $this->get_normalized_httpmethod(),
            $this->get_normalized_httpurl(),
            $this->get_signable_parameters()
        );

        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * just uppercases the http method
     */
    public function get_normalized_httpmethod() {
        return strtoupper($this->httpmethod);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     */
    public function get_normalized_httpurl() {
        $parts = parse_url($this->httpurl);

        $port = @$parts['port'];
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = @$parts['path'];

        $port || $port = ($scheme == 'https') ? '443' : '80';

        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }
        return "$scheme://$host$path";
    }

    /**
     * builds a url usable for a GET request
     */
    public function to_url() {
        $postdata = $this->to_postdata();
        $out = $this->get_normalized_httpurl();
        if ($postdata) {
            $out .= '?'.$postdata;
        }
        return $out;
    }

    /**
     * builds the data one would send in a POST request
     */
    public function to_postdata() {
        return OAuthUtil::build_http_query($this->parameters);
    }

    /**
     * builds the Authorization: header
     */
    public function to_header() {
        $out = 'Authorization: OAuth realm=""';
        $total = array();
        foreach ($this->parameters as $k => $v) {
            if (substr($k, 0, 5) != "oauth") {
                continue;
            }
            if (is_array($v)) {
                throw new OAuthException('Arrays not supported in headers');
            }
            $out .= ',' .
            OAuthUtil::urlencode_rfc3986($k) .
            '="' .
            OAuthUtil::urlencode_rfc3986($v) .
            '"';
        }
        return $out;
    }

    public function __toString() {
        return $this->to_url();
    }

    public function sign_request($signaturemethod, $consumer, $token) {
        $this->set_parameter("oauth_signaturemethod", $signaturemethod->get_name(), false);
        $signature = $this->build_signature($signaturemethod, $consumer, $token);
        $this->set_parameter("oauth_signature", $signature, false);
    }

    public function build_signature($signaturemethod, $consumer, $token) {
        $signature = $signaturemethod->build_signature($this, $consumer, $token);
        return $signature;
    }

    /**
     * util function: current timestamp
     */
    private static function generate_timestamp() {
        return time();
    }

    /**
     * util function: current nonce
     */
    private static function generate_nonce() {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt.$rand);
    }
}

class OAuthServer {
    protected $timestampthreshold = 300;
    protected $version = 1.0;
    protected $signaturemethods = array();
    protected $datastore;

    public function __construct($datastore) {
        $this->datastore = $datastore;
    }

    public function add_signaturemethod($signaturemethod) {
        $this->signaturemethods[$signaturemethod->get_name()] = $signaturemethod;
    }



    /**
     * process a request_token request
     * returns the request token on success
     */
    public function fetch_request_token(&$request) {
        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // No token required for the initial token request.
        $token = null;

        $this->check_signature($request, $consumer, $token);

        $newtoken = $this->datastore->new_request_token($consumer);

        return $newtoken;
    }

    /**
     * process an access_token request
     * returns the access token on success
     */
    public function fetch_access_token(&$request) {
        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // Requires authorized request token.
        $token = $this->get_token($request, $consumer, "request");

        $this->check_signature($request, $consumer, $token);

        $newtoken = $this->datastore->new_access_token($token, $consumer);

        return $newtoken;
    }

    /**
     * verify an api call, checks all the parameters
     */
    public function verify_request(&$request) {
        global $oauthlastcomputedsignature;
        $oauthlastcomputedsignature = false;
        $this->get_version($request);
        $consumer = $this->get_consumer($request);
        $token = $this->get_token($request, $consumer, "access");
        $this->check_signature($request, $consumer, $token);
        return array(
            $consumer,
            $token
        );
    }

    // Internals from here.
    /**
     * version 1
     */
    private function get_version(&$request) {
        $version = $request->get_parameter("oauth_version");
        if (!$version) {
            $version = 1.0;
        }
        if ($version && $version != $this->version) {
            throw new OAuthException("OAuth version '$version' not supported");
        }
        return $version;
    }

    /**
     * figure out the signature with some defaults
     */
    private function get_signaturemethod(&$request) {
        $signaturemethod = @ $request->get_parameter("oauth_signaturemethod");
        if (!$signaturemethod) {
            $signaturemethod = "PLAINTEXT";
        }
        if (!in_array($signaturemethod, array_keys($this->signaturemethods))) {
            throw new OAuthException("Signature method '$signaturemethod' not supported " .
            "try one of the following: " .
            implode(", ", array_keys($this->signaturemethods)));
        }
        return $this->signaturemethods[$signaturemethod];
    }

    /**
     * try to find the consumer for the provided request's consumer key
     */
    private function get_consumer(&$request) {
        $consumerkey = @ $request->get_parameter("oauth_consumerkey");
        if (!$consumerkey) {
            throw new OAuthException("Invalid consumer key");
        }

        $consumer = $this->datastore->lookup_consumer($consumerkey);
        if (!$consumer) {
            throw new OAuthException("Invalid consumer");
        }

        return $consumer;
    }

    /**
     * try to find the token for the provided request's token key
     */
    private function get_token(&$request, $consumer, $tokentype = "access") {
        $tokenfield = @ $request->get_parameter('oauth_token');
        if (!$tokenfield) {
            return false;
        }
        $token = $this->datastore->lookup_token($consumer, $tokentype, $tokenfield);
        if (!$token) {
            throw new OAuthException("Invalid $tokentype token: $tokenfield");
        }
        return $token;
    }

    /**
     * all-in-one function to check the signature on a request
     * should guess the signature method appropriately
     */
    private function check_signature(&$request, $consumer, $token) {
        // This should probably be in a different method.
        global $oauthlastcomputedsignature;
        $oauthlastcomputedsignature = false;

        $timestamp = @ $request->get_parameter('oauth_timestamp');
        $nonce = @ $request->get_parameter('oauth_nonce');

        $this->check_timestamp($timestamp);
        $this->check_nonce($consumer, $token, $nonce, $timestamp);

        $signaturemethod = $this->get_signaturemethod($request);

        $signature = $request->get_parameter('oauth_signature');
        $validsig = $signaturemethod->check_signature($request, $consumer, $token, $signature);

        if (!$validsig) {
            $extext = "Invalid signature";
            if ($oauthlastcomputedsignature) {
                $extext = $extext . " ours= $oauthlastcomputedsignature yours=$signature";
            }
            throw new OAuthException($extext);
        }
    }

    /**
     * check that the timestamp is new enough
     */
    private function check_timestamp($timestamp) {
        // Verify that timestamp is recentish.
        $now = time();
        if ($now - $timestamp > $this->timestampthreshold) {
            throw new OAuthException("Expired timestamp, yours $timestamp, ours $now");
        }
    }

    /**
     * check that the nonce is not repeated
     */
    private function check_nonce($consumer, $token, $nonce, $timestamp) {
        // Verify that the nonce is uniqueish.
        $found = $this->datastore->lookup_nonce($consumer, $token, $nonce, $timestamp);
        if ($found) {
            throw new OAuthException("Nonce already used: $nonce");
        }
    }

}

class OAuthDataStore {
    public function lookup_consumer($consumerkey) {
        // Implement me.
    }

    public function lookup_token($consumer, $tokentype, $token) {
        // Implement me.
    }

    public function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        // Implement me.
    }

    public function new_request_token($consumer) {
        // Return a new token attached to this consumer.
    }

    public function new_access_token($token, $consumer) {
        // Return a new access token attached to this consumer.
        // For the user associated with this token if the request token.
        // Is authorized.
        // Should also invalidate the request token.
    }

}

class OAuthUtil {
    public static function urlencode_rfc3986($input) {
        if (is_array($input)) {
            return array_map(array(
                'moodle\mod\orcalti\OAuthUtil',
                'urlencode_rfc3986'
            ), $input);
        } else {
            if (is_scalar($input)) {
                return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
            } else {
                return '';
            }
        }
    }

    // This decode function isn't taking into consideration the above.
    // Modifications to the encoding process. However, this method doesn't.
    // Seem to be used anywhere so leaving it as is.
    public static function urldecode_rfc3986($string) {
        return urldecode($string);
    }

    // Utility function for turning the Authorization: header into.
    // Parameters, has to do some unescaping.
    // Can filter out any non-oauth parameters if needed (default behaviour).
    public static function split_header($header, $onlyallowoauthparameters = true) {
        $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
        $offset = 0;
        $params = array();
        while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
            $match = $matches[0];
            $headername = $matches[2][0];
            $headercontent = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
            if (preg_match('/^oauth_/', $headername) || !$onlyallowoauthparameters) {
                $params[$headername] = self::urldecode_rfc3986($headercontent);
            }
            $offset = $match[1] + strlen($match[0]);
        }

        if (isset($params['realm'])) {
            unset($params['realm']);
        }

        return $params;
    }

    // Helper to try to sort out headers for people who aren't running apache.
    public static function get_headers() {
        if (function_exists('apache_requestheaders')) {
            // We need this to get the actual Authorization: header.
            // Because apache tends to tell us it doesn't exist.
            $in = apache_requestheaders();
            $out = array();
            foreach ($in as $key => $value) {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
                $out[$key] = $value;
            }
            return $out;
        }
        // Otherwise we don't have apache and are just going to have to hope.
        // That $_SERVER actually contains what we need.
        $out = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                // This is chaos, basically it is just there to capitalize the first.
                // Letter of every word that is not an initial HTTP and strip HTTP.
                // Code from przemek.
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $out[$key] = $value;
            }
        }
        return $out;
    }

    // This function takes a input like a=b&a=c&d=e and returns the parsed.
    public static function parse_parameters($input) {
        if (!isset($input) || !$input) {
            return array();
        }

        $pairs = explode('&', $input);

        $parsedparameters = array();
        foreach ($pairs as $pair) {
            $split = explode('=', $pair, 2);
            $parameter = self::urldecode_rfc3986($split[0]);
            $value = isset($split[1]) ? self::urldecode_rfc3986($split[1]) : '';

            if (isset($parsedparameters[$parameter])) {
                // We have already recieved parameter(s) with this name, so add to the list.
                // Of parameters with this name.

                if (is_scalar($parsedparameters[$parameter])) {
                    // This is the first duplicate, so transform scalar (string) into an array.
                    // So we can add the duplicates.
                    $parsedparameters[$parameter] = array(
                        $parsedparameters[$parameter]
                    );
                }

                $parsedparameters[$parameter][] = $value;
            } else {
                $parsedparameters[$parameter] = $value;
            }
        }
        return $parsedparameters;
    }

    public static function build_http_query($params) {
        if (!$params) {
            return '';
        }

        // Urlencode both keys and values.
        $keys = self::urlencode_rfc3986(array_keys($params));
        $values = self::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1).
        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1).
                natsort($value);
                foreach ($value as $duplicatevalue) {
                    $pairs[] = $parameter . '=' . $duplicatevalue;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38).
        return implode('&', $pairs);
    }
}
