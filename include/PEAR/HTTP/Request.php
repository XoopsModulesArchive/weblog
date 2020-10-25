<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2003, Richard Heyes                                |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+
//
// $Id: Request.php,v 1.1 2006/03/29 05:57:09 mikhail Exp $
//
// HTTP_Request Class
//
// Simple example, (Fetches yahoo.com and displays it):
//
// $a = new HTTP_Request('http://www.yahoo.com/');
// $a->sendRequest();
// echo $a->getResponseBody();
//

require_once sprintf('%s/modules/%s/include/PEAR/PEAR.php', XOOPS_ROOT_PATH, $xoopsModule->dirname());
require_once sprintf('%s/modules/%s/include/PEAR/Net/Socket.php', XOOPS_ROOT_PATH, $xoopsModule->dirname());
require_once sprintf('%s/modules/%s/include/PEAR/Net/URL.php', XOOPS_ROOT_PATH, $xoopsModule->dirname());

define('HTTP_REQUEST_METHOD_GET', 'GET', true);
define('HTTP_REQUEST_METHOD_HEAD', 'HEAD', true);
define('HTTP_REQUEST_METHOD_POST', 'POST', true);
define('HTTP_REQUEST_METHOD_PUT', 'PUT', true);
define('HTTP_REQUEST_METHOD_DELETE', 'DELETE', true);
define('HTTP_REQUEST_METHOD_OPTIONS', 'OPTIONS', true);
define('HTTP_REQUEST_METHOD_TRACE', 'TRACE', true);

define('HTTP_REQUEST_HTTP_VER_1_0', '1.0', true);
define('HTTP_REQUEST_HTTP_VER_1_1', '1.1', true);

class HTTP_Request
{
    /**
     * Instance of Net_URL
     * @var object Net_URL
     */

    public $_url;

    /**
     * Type of request
     * @var string
     */

    public $_method;

    /**
     * HTTP Version
     * @var string
     */

    public $_http;

    /**
     * Request headers
     * @var array
     */

    public $_requestHeaders;

    /**
     * Basic Auth Username
     * @var string
     */

    public $_user;

    /**
     * Basic Auth Password
     * @var string
     */

    public $_pass;

    /**
     * Socket object
     * @var object Net_Socket
     */

    public $_sock;

    /**
     * Proxy server
     * @var string
     */

    public $_proxy_host;

    /**
     * Proxy port
     * @var int
     */

    public $_proxy_port;

    /**
     * Proxy username
     * @var string
     */

    public $_proxy_user;

    /**
     * Proxy password
     * @var string
     */

    public $_proxy_pass;

    /**
     * Post data
     * @var mixed
     */

    public $_postData;

    /**
     * Files to post
     * @var array
     */

    public $_postFiles = [];

    /**
     * Connection timeout.
     * @var float
     */

    public $_timeout;

    /**
     * HTTP_Response object
     * @var object HTTP_Response
     */

    public $_response;

    /**
     * Whether to allow redirects
     * @var bool
     */

    public $_allowRedirects;

    /**
     * Maximum redirects allowed
     * @var int
     */

    public $_maxRedirects;

    /**
     * Current number of redirects
     * @var int
     */

    public $_redirects;

    /**
     * Whether to append brackets [] to array variables
     * @var bool
     */

    public $_useBrackets = true;

    /**
     * Attached listeners
     * @var array
     */

    public $_listeners = [];

    /**
     * Whether to save response body in response object property
     * @var bool
     */

    public $_saveBody = true;

    /**
     * Timeout for reading from socket (array(seconds, microseconds))
     * @var array
     */

    public $_readTimeout = null;

    /**
     * Options to pass to Net_Socket::connect. See stream_context_create
     * @var array
     */

    public $_socketOptions = null;

    /**
     * Constructor
     *
     * Sets up the object
     * @param mixed $url
     * @param mixed $params
     */
    public function __construct($url = '', $params = [])
    {
        $this->_sock = new Net_Socket();

        $this->_method = HTTP_REQUEST_METHOD_GET;

        $this->_http = HTTP_REQUEST_HTTP_VER_1_1;

        $this->_requestHeaders = [];

        $this->_postData = null;

        $this->_user = null;

        $this->_pass = null;

        $this->_proxy_host = null;

        $this->_proxy_port = null;

        $this->_proxy_user = null;

        $this->_proxy_pass = null;

        $this->_allowRedirects = false;

        $this->_maxRedirects = 3;

        $this->_redirects = 0;

        $this->_timeout = null;

        $this->_response = null;

        foreach ($params as $key => $value) {
            $this->{'_' . $key} = $value;
        }

        if (!empty($url)) {
            $this->setURL($url);
        }

        // Default useragent

        $this->addHeader('User-Agent', 'PEAR HTTP_Request class ( http://pear.php.net/ )');

        // Make sure keepalives dont knobble us

        $this->addHeader('Connection', 'close');

        // Basic authentication

        if (!empty($this->_user)) {
            $this->_requestHeaders['Authorization'] = 'Basic ' . base64_encode($this->_user . ':' . $this->_pass);
        }

        // Use gzip encoding if possible

        // Avoid gzip encoding if using multibyte functions (see #1781)

        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && extension_loaded('zlib')
            && 0 == (2 & ini_get('mbstring.func_overload'))) {
            $this->addHeader('Accept-Encoding', 'gzip');
        }
    }

    /**
     * Generates a Host header for HTTP/1.1 requests
     *
     * @return string
     */
    public function _generateHostHeader()
    {
        if (80 != $this->_url->port and 0 == strcasecmp($this->_url->protocol, 'http')) {
            $host = $this->_url->host . ':' . $this->_url->port;
        } elseif (443 != $this->_url->port and 0 == strcasecmp($this->_url->protocol, 'https')) {
            $host = $this->_url->host . ':' . $this->_url->port;
        } elseif (443 == $this->_url->port and 0 == strcasecmp($this->_url->protocol, 'https') and false !== mb_strpos($this->_url->url, ':443')) {
            $host = $this->_url->host . ':' . $this->_url->port;
        } else {
            $host = $this->_url->host;
        }

        return $host;
    }

    /**
     * Resets the object to its initial state (DEPRECATED).
     * Takes the same parameters as the constructor.
     *
     * @param string $url     The url to be requested
     * @param array  $params  Associative array of parameters
     *                        (see constructor for details)
     * @deprecated deprecated since 1.2, call the constructor if this is necessary
     */
    public function reset($url, $params = [])
    {
        self::__construct($url, $params);
    }

    /**
     * Sets the URL to be requested
     *
     * @param mixed $url
     */
    public function setURL($url)
    {
        $this->_url = new Net_URL($url, $this->_useBrackets);

        if (!empty($this->_url->user) || !empty($this->_url->pass)) {
            $this->setBasicAuth($this->_url->user, $this->_url->pass);
        }

        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http) {
            $this->addHeader('Host', $this->_generateHostHeader());
        }
    }

    /**
     * Sets a proxy to be used
     *
     * @param mixed      $host
     * @param mixed      $port
     * @param null|mixed $user
     * @param null|mixed $pass
     */
    public function setProxy($host, $port = 8080, $user = null, $pass = null)
    {
        $this->_proxy_host = $host;

        $this->_proxy_port = $port;

        $this->_proxy_user = $user;

        $this->_proxy_pass = $pass;

        if (!empty($user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
        }
    }

    /**
     * Sets basic authentication parameters
     *
     * @param mixed $user
     * @param mixed $pass
     */
    public function setBasicAuth($user, $pass)
    {
        $this->_user = $user;

        $this->_pass = $pass;

        $this->addHeader('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
    }

    /**
     * Sets the method to be used, GET, POST etc.
     *
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * Sets the HTTP version to use, 1.0 or 1.1
     *
     * @param mixed $http
     */
    public function setHttpVer($http)
    {
        $this->_http = $http;
    }

    /**
     * Adds a request header
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function addHeader($name, $value)
    {
        $this->_requestHeaders[$name] = $value;
    }

    /**
     * Removes a request header
     *
     * @param mixed $name
     */
    public function removeHeader($name)
    {
        if (isset($this->_requestHeaders[$name])) {
            unset($this->_requestHeaders[$name]);
        }
    }

    /**
     * Adds a querystring parameter
     *
     * @param mixed $name
     * @param mixed $value
     * @param mixed $preencoded
     */
    public function addQueryString($name, $value, $preencoded = false)
    {
        $this->_url->addQueryString($name, $value, $preencoded);
    }

    /**
     * Sets the querystring to literally what you supply
     *
     * @param mixed $querystring
     * @param mixed $preencoded
     */
    public function addRawQueryString($querystring, $preencoded = true)
    {
        $this->_url->addRawQueryString($querystring, $preencoded);
    }

    /**
     * Adds postdata items
     *
     * @param mixed $name
     * @param mixed $value
     * @param mixed $preencoded
     */
    public function addPostData($name, $value, $preencoded = false)
    {
        if ($preencoded) {
            $this->_postData[$name] = $value;
        } else {
            $this->_postData[$name] = $this->_arrayMapRecursive('urlencode', $value);
        }
    }

    /**
     * Recursively applies the callback function to the value
     *
     * @param mixed $callback
     * @param mixed $value
     * @return   mixed   Processed value
     */
    public function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value)) {
            return call_user_func($callback, $value);
        }  

        $map = [];

        foreach ($value as $k => $v) {
            $map[$k] = $this->_arrayMapRecursive($callback, $v);
        }

        return $map;
    }

    /**
     * Adds a file to upload
     *
     * This also changes content-type to 'multipart/form-data' for proper upload
     *
     * @param mixed $inputName
     * @param mixed $fileName
     * @param mixed $contentType
     * @return bool      true on success
     */
    public function addFile($inputName, $fileName, $contentType = 'application/octet-stream')
    {
        if (!is_array($fileName) && !is_readable($fileName)) {
            return PEAR::raiseError("File '{$fileName}' is not readable");
        } elseif (is_array($fileName)) {
            foreach ($fileName as $name) {
                if (!is_readable($name)) {
                    return PEAR::raiseError("File '{$name}' is not readable");
                }
            }
        }

        $this->addHeader('Content-Type', 'multipart/form-data');

        $this->_postFiles[$inputName] = [
            'name' => $fileName,
'type' => $contentType,
        ];

        return true;
    }

    /**
     * Adds raw postdata
     *
     * @param mixed $postdata
     * @param mixed $preencoded
     */
    public function addRawPostData($postdata, $preencoded = true)
    {
        $this->_postData = $preencoded ? $postdata : urlencode($postdata);
    }

    /**
     * Clears any postdata that has been added (DEPRECATED).
     *
     * Useful for multiple request scenarios.
     *
     * @deprecated deprecated since 1.2
     */
    public function clearPostData()
    {
        $this->_postData = null;
    }

    /**
     * Appends a cookie to "Cookie:" header
     *
     * @param string $name  cookie name
     * @param string $value cookie value
     */
    public function addCookie($name, $value)
    {
        $cookies = isset($this->_requestHeaders['Cookie']) ? $this->_requestHeaders['Cookie'] . '; ' : '';

        $this->addHeader('Cookie', $cookies . $name . '=' . $value);
    }

    /**
     * Clears any cookies that have been added (DEPRECATED).
     *
     * Useful for multiple request scenarios
     *
     * @deprecated deprecated since 1.2
     */
    public function clearCookies()
    {
        $this->removeHeader('Cookie');
    }

    /**
     * Sends the request
     *
     * @param mixed $saveBody
     * @return mixed  PEAR error on error, true otherwise
     */
    public function sendRequest($saveBody = true)
    {
        if (function_exists('is_a') && !is_a($this->_url, 'Net_URL')) {
            return PEAR::raiseError('No URL given.');
        }

        $host = $this->_proxy_host ?? $this->_url->host;

        $port = $this->_proxy_port ?? $this->_url->port;

        // 4.3.0 supports SSL connections using OpenSSL. The function test determines

        // we running on at least 4.3.0

        if (0 == strcasecmp($this->_url->protocol, 'https') and function_exists('file_get_contents') and extension_loaded('openssl')) {
            if (isset($this->_proxy_host)) {
                return PEAR::raiseError('HTTPS proxies are not supported.');
            }

            $host = 'ssl://' . $host;
        }

        // If this is a second request, we may get away without

        // re-connecting if they're on the same server

        if (PEAR::isError($err = $this->_sock->connect($host, $port, null, $this->_timeout, $this->_socketOptions))
            || PEAR::isError($err = $this->_sock->write($this->_buildRequest()))) {
            return $err;
        }

        if (!empty($this->_readTimeout)) {
            $this->_sock->setTimeout($this->_readTimeout[0], $this->_readTimeout[1]);
        }

        $this->_notify('sentRequest');

        // Read the response

        $this->_response = new HTTP_Response($this->_sock, $this->_listeners);

        if (PEAR::isError($err = $this->_response->process($this->_saveBody && $saveBody))) {
            return $err;
        }

        // Check for redirection

        // Bugfix (PEAR) bug #18, 6 oct 2003 by Dave Mertens (headers are also stored lowercase, so we're gonna use them here)

        // some non RFC2616 compliant servers (scripts) are returning lowercase headers ('location: xxx')

        if ($this->_allowRedirects and $this->_redirects <= $this->_maxRedirects and $this->getResponseCode() > 300 and $this->getResponseCode() < 399 and !empty($this->_response->_headers['location'])) {
            $redirect = $this->_response->_headers['location'];

            // Absolute URL

            if (preg_match('/^https?:\/\//i', $redirect)) {
                $this->_url = new Net_URL($redirect);

                $this->addHeader('Host', $this->_generateHostHeader());

            // Absolute path
            } elseif ('/' == $redirect[0]) {
                $this->_url->path = $redirect;

            // Relative path
            } elseif ('../' == mb_substr($redirect, 0, 3) or './' == mb_substr($redirect, 0, 2)) {
                if ('/' == mb_substr($this->_url->path, -1)) {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }

                $redirect = Net_URL::resolvePath($redirect);

                $this->_url->path = $redirect;

            // Filename, no path
            } else {
                if ('/' == mb_substr($this->_url->path, -1)) {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }

                $this->_url->path = $redirect;
            }

            $this->_redirects++;

            return $this->sendRequest($saveBody);
        // Too many redirects
        } elseif ($this->_allowRedirects and $this->_redirects > $this->_maxRedirects) {
            return PEAR::raiseError('Too many redirects');
        }

        $this->_sock->disconnect();

        return true;
    }

    /**
     * Returns the response code
     *
     * @return mixed     Response code, false if not set
     */
    public function getResponseCode()
    {
        return $this->_response->_code ?? false;
    }

    /**
     * Returns either the named header or all if no name given
     *
     * @param null|mixed $headername
     * @return mixed     either the value of $headername (false if header is not present)
     *                   or an array of all headers
     */
    public function getResponseHeader($headername = null)
    {
        if (!isset($headername)) {
            return $this->_response->_headers ?? [];
        }

        return $this->_response->_headers[$headername] ?? false;
    }

    /**
     * Returns the body of the response
     *
     * @return mixed     response body, false if not set
     */
    public function getResponseBody()
    {
        return $this->_response->_body ?? false;
    }

    /**
     * Returns cookies set in response
     *
     * @return mixed     array of response cookies, false if none are present
     */
    public function getResponseCookies()
    {
        return $this->_response->_cookies ?? false;
    }

    /**
     * Builds the request string
     *
     * @return string The request string
     */
    public function _buildRequest()
    {
        $separator = ini_get('arg_separator.output');

        ini_set('arg_separator.output', '&');

        $querystring = ($querystring = $this->_url->getQueryString()) ? '?' . $querystring : '';

        ini_set('arg_separator.output', $separator);

        $host = isset($this->_proxy_host) ? $this->_url->protocol . '://' . $this->_url->host : '';

        $port = (isset($this->_proxy_host) and 80 != $this->_url->port) ? ':' . $this->_url->port : '';

        $path = (empty($this->_url->path) ? '/' : $this->_url->path) . $querystring;

        $url = $host . $port . $path;

        $request = $this->_method . ' ' . $url . ' HTTP/' . $this->_http . "\r\n";

        if (HTTP_REQUEST_METHOD_POST != $this->_method && HTTP_REQUEST_METHOD_PUT != $this->_method) {
            $this->removeHeader('Content-Type');
        } else {
            if (empty($this->_requestHeaders['Content-Type'])) {
                // Add default content-type

                $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            } elseif ('multipart/form-data' == $this->_requestHeaders['Content-Type']) {
                $boundary = 'HTTP_Request_' . md5(uniqid('request') . microtime());

                $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
            }
        }

        // Request Headers

        if (!empty($this->_requestHeaders)) {
            foreach ($this->_requestHeaders as $name => $value) {
                $request .= $name . ': ' . $value . "\r\n";
            }
        }

        // No post data or wrong method, so simply add a final CRLF

        if ((HTTP_REQUEST_METHOD_POST != $this->_method && HTTP_REQUEST_METHOD_PUT != $this->_method)
            || (empty($this->_postData) && empty($this->_postFiles))) {
            $request .= "\r\n";

        // Post data if it's an array
        } elseif ((!empty($this->_postData) && is_array($this->_postData)) || !empty($this->_postFiles)) {
            // "normal" POST request

            if (!isset($boundary)) {
                $postdata = implode(
                    '&',
                    array_map(
                        create_function('$a', 'return $a[0] . \'=\' . $a[1];'),
                        $this->_flattenArray('', $this->_postData)
                    )
                );

            // multipart request, probably with file uploads
            } else {
                $postdata = '';

                if (!empty($this->_postData)) {
                    $flatData = $this->_flattenArray('', $this->_postData);

                    foreach ($flatData as $item) {
                        $postdata .= '--' . $boundary . "\r\n";

                        $postdata .= 'Content-Disposition: form-data; name="' . $item[0] . '"';

                        $postdata .= "\r\n\r\n" . urldecode($item[1]) . "\r\n";
                    }
                }

                foreach ($this->_postFiles as $name => $value) {
                    if (is_array($value['name'])) {
                        $varname = $name . ($this->_useBrackets ? '[]' : '');
                    } else {
                        $varname = $name;

                        $value['name'] = [$value['name']];
                    }

                    foreach ($value['name'] as $key => $filename) {
                        $fp = fopen($filename, 'rb');

                        $data = fread($fp, filesize($filename));

                        fclose($fp);

                        $basename = basename($filename);

                        $type = is_array($value['type']) ? @$value['type'][$key] : $value['type'];

                        $postdata .= '--' . $boundary . "\r\n";

                        $postdata .= 'Content-Disposition: form-data; name="' . $varname . '"; filename="' . $basename . '"';

                        $postdata .= "\r\nContent-Type: " . $type;

                        $postdata .= "\r\n\r\n" . $data . "\r\n";
                    }
                }

                $postdata .= '--' . $boundary . "\r\n";
            }

            $request .= 'Content-Length: ' . mb_strlen($postdata) . "\r\n\r\n";

            $request .= $postdata;

        // Post data if it's raw
        } elseif (!empty($this->_postData)) {
            $request .= 'Content-Length: ' . mb_strlen($this->_postData) . "\r\n\r\n";

            $request .= $this->_postData;
        }

        return $request;
    }

    /**
     * Helper function to change the (probably multidimensional) associative array
     * into the simple one.
     *
     * @param mixed $name
     * @param mixed $values
     * @return   array   array with the following items: array('item name', 'item value');
     */
    public function _flattenArray($name, $values)
    {
        if (!is_array($values)) {
            return [[$name, $values]];
        }  

        $ret = [];

        foreach ($values as $k => $v) {
            if (empty($name)) {
                $newName = $k;
            } elseif ($this->_useBrackets) {
                $newName = $name . '[' . $k . ']';
            } else {
                $newName = $name;
            }

            $ret = array_merge($ret, $this->_flattenArray($newName, $v));
        }

        return $ret;
    }

    /**
     * Adds a Listener to the list of listeners that are notified of
     * the object's events
     *
     * @param mixed $listener
     * @return   bool  whether the listener was successfully attached
     */
    public function attach(&$listener)
    {
        if (function_exists('is_a') && !is_a($listener, 'HTTP_Request_Listener')) {
            return false;
        }

        $this->_listeners[$listener->getId()] = &$listener;

        return true;
    }

    /**
     * Removes a Listener from the list of listeners
     *
     * @param mixed $listener
     * @return   bool  whether the listener was successfully detached
     */
    public function detach($listener)
    {
        if (function_exists('is_a') && !is_a($listener, 'HTTP_Request_Listener')
            || !isset($this->_listeners[$listener->getId()])) {
            return false;
        }

        unset($this->_listeners[$listener->getId()]);

        return true;
    }

    /**
     * Notifies all registered listeners of an event.
     *
     * Events sent by HTTP_Request object
     * 'sentRequest': after the request was sent
     * Events sent by HTTP_Response object
     * 'gotHeaders': after receiving response headers (headers are passed in $data)
     * 'tick': on receiving a part of response body (the part is passed in $data)
     * 'gzTick': on receiving a gzip-encoded part of response body (ditto)
     * 'gotBody': after receiving the response body (passes the decoded body in $data if it was gzipped)
     *
     * @param mixed      $event
     * @param null|mixed $data
     */
    public function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
}

/**
 * Response class to complement the Request class
 */
class HTTP_Response
{
    /**
     * Socket object
     * @var object
     */

    public $_sock;

    /**
     * Protocol
     * @var string
     */

    public $_protocol;

    /**
     * Return code
     * @var string
     */

    public $_code;

    /**
     * Response headers
     * @var array
     */

    public $_headers;

    /**
     * Cookies set in response
     * @var array
     */

    public $_cookies;

    /**
     * Response body
     * @var string
     */

    public $_body = '';

    /**
     * Used by _readChunked(): remaining length of the current chunk
     * @var string
     */

    public $_chunkLength = 0;

    /**
     * Attached listeners
     * @var array
     */

    public $_listeners = [];

    /**
     * Constructor
     *
     * @param mixed $sock
     * @param mixed $listeners
     */
    public function __construct(&$sock, &$listeners)
    {
        $this->_sock = &$sock;

        $this->_listeners = &$listeners;
    }

    /**
     * Processes a HTTP response
     *
     * This extracts response code, headers, cookies and decodes body if it
     * was encoded in some way
     *
     * @param mixed $saveBody
     * @return mixed     true on success, PEAR_Error in case of malformed response
     */
    public function process($saveBody = true)
    {
        do {
            $line = $this->_sock->readLine();

            if (2 != sscanf($line, 'HTTP/%s %s', $http_version, $returncode)) {
                return PEAR::raiseError('Malformed response.');
            }  

            $this->_protocol = 'HTTP/' . $http_version;

            $this->_code = (int)$returncode;

            while ('' !== ($header = $this->_sock->readLine())) {
                $this->_processHeader($header);
            }
        } while (100 == $this->_code);

        $this->_notify('gotHeaders', $this->_headers);

        // If response body is present, read it and decode

        $chunked = isset($this->_headers['transfer-encoding']) && ('chunked' == $this->_headers['transfer-encoding']);

        $gzipped = isset($this->_headers['content-encoding']) && ('gzip' == $this->_headers['content-encoding']);

        $hasBody = false;

        while (!$this->_sock->eof()) {
            if ($chunked) {
                $data = $this->_readChunked();
            } else {
                $data = $this->_sock->read(4096);
            }

            if ('' != $data) {
                $hasBody = true;

                if ($saveBody || $gzipped) {
                    $this->_body .= $data;
                }

                $this->_notify($gzipped ? 'gzTick' : 'tick', $data);
            }
        }

        if ($hasBody) {
            // Uncompress the body if needed

            if ($gzipped) {
                $this->_body = gzinflate(mb_substr($this->_body, 10));

                $this->_notify('gotBody', $this->_body);
            } else {
                $this->_notify('gotBody');
            }
        }

        return true;
    }

    /**
     * Processes the response header
     *
     * @param mixed $header
     */
    public function _processHeader($header)
    {
        [$headername, $headervalue] = explode(':', $header, 2);

        $headername_i = mb_strtolower($headername);

        $headervalue = ltrim($headervalue);

        if ('set-cookie' != $headername_i) {
            $this->_headers[$headername] = $headervalue;

            $this->_headers[$headername_i] = $headervalue;
        } else {
            $this->_parseCookie($headervalue);
        }
    }

    /**
     * Parse a Set-Cookie header to fill $_cookies array
     *
     * @param mixed $headervalue
     */
    public function _parseCookie($headervalue)
    {
        $cookie = [
            'expires' => null,
'domain' => null,
'path' => null,
'secure' => false,
        ];

        // Only a name=value pair

        if (!mb_strpos($headervalue, ';')) {
            $pos = mb_strpos($headervalue, '=');

            $cookie['name'] = trim(mb_substr($headervalue, 0, $pos));

            $cookie['value'] = trim(mb_substr($headervalue, $pos + 1));

        // Some optional parameters are supplied
        } else {
            $elements = explode(';', $headervalue);

            $pos = mb_strpos($elements[0], '=');

            $cookie['name'] = trim(mb_substr($elements[0], 0, $pos));

            $cookie['value'] = trim(mb_substr($elements[0], $pos + 1));

            for ($i = 1, $iMax = count($elements); $i < $iMax; $i++) {
                if (false === mb_strpos($elements[$i], '=')) {
                    $elName = trim($elements[$i]);

                    $elValue = null;
                } else {
                    [$elName, $elValue] = array_map('trim', explode('=', $elements[$i]));
                }

                $elName = mb_strtolower($elName);

                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName || 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }

        $this->_cookies[] = $cookie;
    }

    /**
     * Read a part of response body encoded with chunked Transfer-Encoding
     *
     * @return string
     */
    public function _readChunked()
    {
        // at start of the next chunk?

        if (0 == $this->_chunkLength) {
            $line = $this->_sock->readLine();

            if (preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                $this->_chunkLength = hexdec($matches[1]);

                // Chunk with zero length indicates the end

                if (0 == $this->_chunkLength) {
                    $this->_sock->readAll(); // make this an eof()

                    return '';
                }
            } elseif ($this->_sock->eof()) {
                return '';
            }
        }

        $data = $this->_sock->read($this->_chunkLength);

        $this->_chunkLength -= mb_strlen($data);

        if (0 == $this->_chunkLength) {
            $this->_sock->readLine(); // Trailing CRLF
        }

        return $data;
    }

    /**
     * Notifies all registered listeners of an event.
     *
     * @param mixed      $event
     * @param null|mixed $data
     * @see      HTTP_Request::_notify()
     */
    public function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
} // End class HTTP_Response
