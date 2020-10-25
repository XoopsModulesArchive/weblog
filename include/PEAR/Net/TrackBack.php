<?php
//
// Copyright (c) 2004 KOYAMA, Tetsuji All Right Reserved.
// $Id: TrackBack.php,v 1.1 2006/03/29 05:57:10 mikhail Exp $
//
// Net_TrackBack Class
//
// Simple example:
//
// $tb = new Net_trackBack;
// $id = $tb->getId();
// // you must validate TrackBack ID
//
// if ($tb->isPing()) {
//     $data = $tb->analyzePing($_POST);
//     // store data in your storage
//     $tb->displayResult($tb->getPingXML(true));
// } else {
//     // listup
//     // load TrackBack data from your storage
//     $items = array();
//     $tb->displayResult($tb->toRSSXML($items));
// }
//

require_once sprintf('%s/modules/%s/include/PEAR/PEAR.php', XOOPS_ROOT_PATH, $xoopsModule->dirname());
require_once sprintf('%s/modules/%s/include/PEAR/HTTP/Request.php', XOOPS_ROOT_PATH, $xoopsModule->dirname());

/**
 * User-Agent name when pinging TrackBack
 */
define('NET_TRACKBACK_DEFAULT_USER_AGENT', 'Net_TrackBack');
/**
 * Error code
 */
define('NET_TRACKBACK_ERROR_INVALID_PATHINFO', -1);
define('NET_TRACKBACK_ERROR_INVALID_PARAM', -2);
define('NET_TRACKBACK_ERROR_BAD_RESPONSE', -3);
define('NET_TRACKBACK_ERROR_REMOTE_ERROR', -4);

class Net_TrackBack
{
    /**
     * key names of TrackBack
     * @var array
     */

    public $ping_keys = ['title', 'url', 'excerpt', 'blog_name'];

    /**
     * Send a TrackBack ping.
     * (used by TrackBack client.)
     *
     * @param string $url        TrackBack URL
     * @param array  $data       data of TrackBack
     * @param string $user_agent User-Agent (Optional)
     * @param null   $charset    Character set of data (Optional)
     *
     * @return mixed  true on success. PEAR_Error on failure.
     */
    public function sendPing(
        $url,
        $data,
        $user_agent = NET_TRACKBACK_DEFAULT_USER_AGENT,
        $charset = null
    ) {
        if (!is_array($data)) {
            return PEAR::raiseError(
                'Invalid Ping Data',
                NET_TRACKBACK_ERROR_INVALID_PARAM
            );
        }

        $params = ['method' => HTTP_REQUEST_METHOD_POST];

        $req = new HTTP_Request($url, $params);

        foreach ($data as $key => $val) {
            $req->addPostData($key, $val);
        }

        $req->addHeader('User-Agent', $user_agent);

        if (!empty($charset)) {
            $req->addHeader(
                'Content-Type',
                'application/x-www-form-urlencoded; charset=' . $charset
            );
        }

        $result = $req->sendRequest();

        if (PEAR::isError($result)) {
            return $result;
        }

        $response = $req->getResponseBody();

        $result = preg_match('!<error>(\d+)</error>!', $response, $matches);

        if (0 == $result) {
            return PEAR::raiseError(
                "Bad Response format from $url",
                NET_TRACKBACK_ERROR_BAD_RESPONSE
            );
        }

        $errnum = $matches[1];

        if (0 != $errnum) {
            $result = preg_match(
                '!<message>(.+?)</message>!ms',
                $response,
                $matches
            );

            if (1 == $result) {
                $message = $matches[1];
            } else {
                $message = '(unrecognized message)';
            }

            return PEAR::raiseError(
                'Server returns error: ' . $message,
                NET_TRACKBACK_ERROR_REMOTE_ERROR
            );
        }

        return true;
    }

    /**
     * Discover TrackBack URL from given URL
     * (used by TrackBack client.)
     *
     * @param string $url The URL to discover
     *
     * @return mixed TrackBack URL on success.
     *               false on no TrackBack URL.
     *               PEAR_Error on failure.
     */
    public function discover($url)
    {
        if (empty($url)) {
            return PEAR::raiseError('URL is empty');
        }

        $params = [
            'method' => HTTP_REQUEST_METHOD_GET,
'timeout' => 15,
        ];

        $req = new HTTP_Request($url, $params);

        $result = $req->sendRequest();

        if (PEAR::isError($result)) {
            return $result;
        }

        $body = $req->getResponseBody();

        $nmatch = preg_match('!(<rdf:RDF.*?</rdf:RDF>)!ms', $body, $matches);

        if (0 == $nmatch) {
            return false;
        }

        $result = [];

        for ($i = 0; $i < $nmatch; ++$i) {
            $url_no_anchor = $url;

            $pos = mb_strrpos($url, '#');

            if ($pos) {
                $url_no_anchor = mb_substr($url, 0, $pos);
            }

            $rdf = &$matches[$i + 1];

            if (0 == preg_match('!dc:identifier="([^"]+)"!m', $rdf, $perm_urls)) {
                continue;
            }

            if ($perm_urls[1] != $url && $perm_urls[1] != $url_no_anchor) {
                continue;
            }

            if (preg_match('!trackback:ping="([^"]+)"!m', $rdf, $pings)) {
                $result[] = $pings[1];
            } elseif (preg_match('!about="([^"]+)"!m', $rdf, $abouts)) {
                $result[] = $abouts[1];
            }
        }

        return $result;
    }

    /**
     * Return ping received or not
     * (used by TrackBack server.)
     *
     * @return bool   true when ping requested
     *
     * @see    Net_TrackBack::isListup()
     */
    public function isPing()
    {
        return ('POST' == $_SERVER['REQUEST_METHOD']);
    }

    /**
     * Return listup requested or not
     * (used by TrackBack server.)
     *
     * @return bool   true when listup requested
     *
     * @see    Net_TrackBack::isPing()
     */
    public function isListup()
    {
        return ('GET' == $_SERVER['REQUEST_METHOD']);
    }

    /**
     * Pick up TrackBack related valiable from original data
     * (used by TrackBack server.)
     *
     * @param array $data Received ping data (may be $_POST)
     *
     * @return array  the data of TrackBack ping
     */
    public function analyzePing($data)
    {
        $result = [];

        foreach ($this->ping_keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }

    /**
     * Get TrackBack ID from PATH_INFO
     * (used by TrackBack server.)
     *
     * @return mixed TrackBack ID or PEAR_Error on failure
     */
    public function getId()
    {
        if (array_key_exists('PATH_INFO', $_SERVER)) {
            $pathinfo = $_SERVER['PATH_INFO'];
        } else {
            $pathinfo = '';
        }

        if ('/' == mb_substr($pathinfo, 0, 1)) {
            $id = mb_substr($pathinfo, 1);
        } else {
            return PEAR::raiseError(
                'Invalid PATH_INFO',
                NET_TRACKBACK_ERROR_INVALID_PATHINFO
            );
        }

        $pos = mb_strpos($id, '/');

        if (false !== $pos) {
            $id = mb_substr($id, 0, $pos);
        }

        return $id;
    }

    /**
     * Display result of TrackBack ping/listup
     * (used by TrackBack server.)
     *
     * @param string $message output message
     *
     * @return bool   true only
     */
    public function displayResult($message)
    {
        header('Content-Type: text/xml');

        echo $message;

        return true;
    }

    /**
     * Format result for TrackBack ping
     * (used by TrackBack server.)
     *
     * @param bool $success Result of ping
     * @param null $message Message when error occured
     *
     * @return string generated XML to reply
     */
    public function getPingXML($success, $message = null)
    {
        $head = '<?xml version="1.0" encoding="iso-8859-1" ?>' . "\n" . '<response>' . "\n";

        $foot = '</response>';

        if ($success) {
            $result = $head . '<error>0</error>' . "\n" . $foot;
        } else {
            if (empty($message)) {
                $message = 'Unknown Error';
            }

            $result = $head . '<error>1</error>' . "\n" . "<message>$message</message>\n" . $foot;
        }

        return $result;
    }

    /**
     * Generates RSS to listup TrackBack datas
     * (used by TrackBack server.)
     *
     * @param array  $items
     * @param string $title
     * @param null   $link
     * @param string $description
     * @param string $lang
     *
     * @return string generated XML to reply
     */
    public function toRSSXML(
        $items,
        $title = 'TrackBacks list',
        $link = null,
        $description = 'TrackBack items',
        $lang = 'en-us'
    ) {
        $header = '<?xml version="1.0" encoding="utf-8" ?>' . "\n" . "<response>\n" . "<error>0</error>\n" . '<rss version="0.91"><channel>' . "\n" . "<title>%s</title>\n" . "<link>%s</link>\n" . "<description>%s</description>\n" . "<language>%s</language>\n";

        $footer = "</channel>\n</rss></response>\n";

        if (empty($link)) {
            $link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        }

        $rss = sprintf($header, $title, $link, $description, $lang);

        foreach ($items as $item) {
            $keys = [
                'title' => 'title',
'url' => 'link',
'excerpt' => 'description',
            ];

            $part = '';

            foreach ($keys as $key => $itemname) {
                if (isset($item[$key])) {
                    $part .= sprintf(
                        "<%s>%s</%s>\n",
                        $itemname,
                        htmlspecialchars($item[$key], ENT_QUOTES | ENT_HTML5),
                        $itemname
                    );
                }
            }

            if (!empty($part)) {
                $rss .= "<item>\n" . $part . "</item>\n";
            }
        }

        return $rss . $footer;
    }

    /**
     * Generate RDF to embed HTML content.
     * (used by TrackBack server.)
     *
     * @param array $data
     * @return mixed generated RDF to embeded on success,
     *               or PEAR_Error object on failure
     */
    public function toEmbededRDF($data)
    {
        $rdfspec = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"' . "\n\t" . 'xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"' . "\n\t" . 'xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";

        $rdffmt = '<rdf:Description' . "\n" . '    rdf:about="%s"' . "\n" . '    trackback:ping="%s"' . "\n" . '    dc:title="%s"' . "\n" . '    dc:identifier="%s"' . "\n" . '    dc:description="%s"' . "\n" . '    dc:creator="%s"' . "\n" . '    dc:date="%s">' . "\n" . '</rdf:RDF>' . "\n";

        $keys = [
            'about',
            'ping',
            'title',
            'identifier',
            'description',
            'creator',
            'date',
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                PEAR::raiseError(
                    "$key is required key in data",
                    NET_TRACKBACK_ERROR_INVALID_PARAM
                );
            }
        }

        return $rdfspec . sprintf(
            $rdffmt,
            $data['about'],
            $data['ping'],
            $data['title'],
            $data['identifier'],
            $data['description'],
            $data['creator'],
            $data['date']
        );
    }
}
