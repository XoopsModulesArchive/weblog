<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Stig Bakken <ssb@fast.no>                                    |
// |         Tomas V.V.Cox <cox@idecnet.com>                              |
// |         Stephan Schmidt <schst@php-tools.net>                        |
// +----------------------------------------------------------------------+
//
// $Id: Parser.php,v 1.1 2006/03/29 05:57:11 mikhail Exp $

/**
 * XML Parser class.
 *
 * This is an XML parser based on PHP's "xml" extension,
 * based on the bundled expat library.
 *
 * @category XML
 * @author   Stig Bakken <ssb@fast.no>
 * @author   Tomas V.V.Cox <cox@idecnet.com>
 * @author   Stephan Schmidt <schst@php-tools.net>
 */

/**
 * uses PEAR's error handling
 */
require_once XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->dirname() . '/include/PEAR/PEAR.php';

/**
 * resource could not be created
 */
define('XML_PARSER_ERROR_NO_RESOURCE', 200);

/**
 * unsupported mode
 */
define('XML_PARSER_ERROR_UNSUPPORTED_MODE', 201);

/**
 * invalid encoding was given
 */
define('XML_PARSER_ERROR_INVALID_ENCODING', 202);

/**
 * specified file could not be read
 */
define('XML_PARSER_ERROR_FILE_NOT_READABLE', 203);

/**
 * invalid input
 */
define('XML_PARSER_ERROR_INVALID_INPUT', 204);

/**
 * remote file cannot be retrieved in safe mode
 */
define('XML_PARSER_ERROR_REMOTE', 205);

/**
 * XML Parser class.
 *
 * This is an XML parser based on PHP's "xml" extension,
 * based on the bundled expat library.
 *
 * Notes:
 * - It requires PHP 4.0.4pl1 or greater
 * - From revision 1.17, the function names used by the 'func' mode
 *   are in the format "xmltag_$elem", for example: use "xmltag_name"
 *   to handle the <name></name> tags of your xml file.
 *
 * @category XML
 * @author   Stig Bakken <ssb@fast.no>
 * @author   Tomas V.V.Cox <cox@idecnet.com>
 * @author   Stephan Schmidt <schst@php-tools.net>
 * @todo     create XML_Parser_Namespace to parse documents with namespaces
 * @todo     create XML_Parser_Pull
 * @todo     Tests that need to be made:
 *          - mixing character encodings
 *          - a test using all expat handlers
 *          - options (folding, output charset)
 *          - different parsing modes
 */
class XML_Parser extends PEAR
{
    // {{{ properties

    /**
     * XML parser handle
     *
     * @var  resource
     * @see  xml_parser_create()
     */

    public $parser;

    /**
     * File handle if parsing from a file
     *
     * @var  resource
     */

    public $fp;

    /**
     * Whether to do case folding
     *
     * If set to true, all tag and attribute names will
     * be converted to UPPER CASE.
     *
     * @var  bool
     */

    public $folding = true;

    /**
     * Mode of operation, one of "event" or "func"
     *
     * @var  string
     */

    public $mode;

    /**
     * Mapping from expat handler function to class method.
     *
     * @var  array
     */

    public $handler = [
        'character_dataHandler' => 'cdataHandler',
'defaultHandler' => 'defaultHandler',
'processing_instructionHandler' => 'piHandler',
'unparsed_entity_declHandler' => 'unparsedHandler',
'notation_declHandler' => 'notationHandler',
'external_entity_refHandler' => 'entityrefHandler',
    ];

    /**
     * source encoding
     *
     * @var string
     */

    public $srcenc;

    /**
     * target encoding
     *
     * @var string
     */

    public $tgtenc;

    /**
     * handler object
     *
     * @var object
     */

    public $HandlerObj;

    // }}}

    // {{{ constructor

    /**
     * Creates an XML parser.
     *
     * This is needed for PHP4 compatibility, it will
     * call the constructor, when a new instance is created.
     *
     * @param null       $srcenc source charset encoding, use NULL (default) to use
     *                           whatever the document specifies
     * @param string     $mode   how this parser object should work, "event" for
     *                           startelement/endelement-type events, "func"
     *                           to have it call functions named after elements
     * @param null|mixed $tgtenc
     */
    public function XML_Parser($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        self::__construct($srcenc, $mode, $tgtenc);
    }

    // }}}

    /**
     * PHP5 constructor
     *
     * @param null       $srcenc source charset encoding, use NULL (default) to use
     *                           whatever the document specifies
     * @param string     $mode   how this parser object should work, "event" for
     *                           startelement/endelement-type events, "func"
     *                           to have it call functions named after elements
     * @param null|mixed $tgtenc
     */
    public function __construct($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        parent::__construct('XML_Parser_Error');

        $this->mode = $mode;

        $this->srcenc = $srcenc;

        $this->tgtenc = $tgtenc;
    }

    // }}}

    /**
     * Sets the mode of the parser.
     *
     * Possible modes are:
     * - func
     * - event
     *
     * You can set the mode using the second parameter
     * in the constructor.
     *
     * This method is only needed, when switching to a new
     * mode at a later point.
     *
     * @param mixed $mode
     * @return  bool|object  true on success, PEAR_Error otherwise
     */
    public function setMode($mode)
    {
        if ('func' != $mode && 'event' != $mode) {
            $this->raiseError('Unsupported mode given', XML_PARSER_ERROR_UNSUPPORTED_MODE);
        }

        $this->mode = $mode;

        return true;
    }

    /**
     * Sets the object, that will handle the XML events
     *
     * This allows you to create a handler object independent of the
     * parser object that you are using and easily switch the underlying
     * parser.
     *
     * If no object will be set, XML_Parser assumes that you
     * extend this class and handle the events in $this.
     *
     * @param mixed $obj
     * @return  bool     will always return true
     * @since   v1.2.0beta3
     */
    public function setHandlerObj(&$obj)
    {
        $this->HandlerObj = &$obj;

        return true;
    }

    /**
     * Init the element handlers
     */
    public function _initHandlers()
    {
        if (!is_resource($this->parser)) {
            return false;
        }

        if (!is_object($this->HandlerObj)) {
            $this->HandlerObj = &$this;
        }

        switch ($this->mode) {
            case 'func':
                xml_set_object($this->parser, $this->HandlerObj);
                xml_set_elementHandler($this->parser, [&$this, 'funcStartHandler'], [&$this, 'funcEndHandler']);
                break;
            case 'event':
                xml_set_object($this->parser, $this->HandlerObj);
                xml_set_elementHandler($this->parser, 'startHandler', 'endHandler');
                break;
            default:
                return $this->raiseError('Unsupported mode given', XML_PARSER_ERROR_UNSUPPORTED_MODE);
                break;
        }

        /**
         * set additional handlers for character data, entities, etc.
         */

        foreach ($this->handler as $xml_func => $method) {
            if (method_exists($this->HandlerObj, $method)) {
                $xml_func = 'xml_set_' . $xml_func;

                $xml_func($this->parser, $method);
            }
        }
    }

    // {{{ _create()

    /**
     * create the XML parser resource
     *
     * Has been moved from the constructor to avoid
     * problems with object references.
     *
     * Furthermore it allows us returning an error
     * if something fails.
     *
     * @return   bool|object     true on success, PEAR_Error otherwise
     *
     * @see      xml_parser_create
     */
    public function _create()
    {
        if (null === $this->srcenc) {
            $xp = @xml_parser_create();
        } else {
            $xp = @xml_parser_create($this->srcenc);
        }

        if (is_resource($xp)) {
            if (null !== $this->tgtenc) {
                if (!@xml_parser_set_option(
                    $xp,
                    XML_OPTION_TARGET_ENCODING,
                    $this->tgtenc
                )) {
                    return $this->raiseError('invalid target encoding', XML_PARSER_ERROR_INVALID_ENCODING);
                }
            }

            $this->parser = $xp;

            $result = $this->_initHandlers($this->mode);

            if ($this->isError($result)) {
                return $result;
            }

            xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING, $this->folding);

            return true;
        }

        return $this->raiseError('Unable to create XML parser resource.', XML_PARSER_ERROR_NO_RESOURCE);
    }

    // }}}

    // {{{ reset()

    /**
     * Reset the parser.
     *
     * This allows you to use one parser instance
     * to parse multiple XML documents.
     *
     * @return   bool|object     true on success, PEAR_Error otherwise
     */
    public function reset()
    {
        $result = $this->_create();

        if ($this->isError($result)) {
            return $result;
        }

        return true;
    }

    // }}}

    // {{{ setInputFile()

    /**
     * Sets the input xml file to be parsed
     *
     * @param mixed $file
     * @return   resource    fopen handle of the given file
     * @see      setInput(), setInputString(), parse()
     */
    public function setInputFile($file)
    {
        /**
         * check, if file is a remote file
         */

        if (eregi('^(http|ftp)://', mb_substr($file, 0, 10))) {
            if (!ini_get('allow_url_fopen')) {
                return $this->raiseError('Remote files cannot be parsed, as safe mode is enabled.', XML_PARSER_ERROR_REMOTE);
            }
        }

        $fp = @fopen($file, 'rb');

        if (is_resource($fp)) {
            $this->fp = $fp;

            return $fp;
        }

        return $this->raiseError('File could not be opened.', XML_PARSER_ERROR_FILE_NOT_READABLE);
    }

    // }}}

    // {{{ setInputString()

    /**
     * XML_Parser::setInputString()
     *
     * Sets the xml input from a string
     *
     * @param string $data a string containing the XML document
     *
     * @return null
     * @return null
     */
    public function setInputString($data)
    {
        $this->fp = $data;

        return null;
    }

    // }}}

    // {{{ setInput()

    /**
     * Sets the file handle to use with parse().
     *
     * You should use setInputFile() or setInputString() if you
     * pass a string
     *
     * @param mixed $fp      Can be either a resource returned from fopen(),
     *                       a URL, a local filename or a string.
     * @return bool|resource|\XML_Parser_Error
     * @return bool|resource|\XML_Parser_Error
     * @uses     setInputString(), setInputFile()
     * @see      parse()
     */
    public function setInput($fp)
    {
        if (is_resource($fp)) {
            $this->fp = $fp;

            return true;
        } // see if it's an absolute URL (has a scheme at the beginning)

        elseif (eregi('^[a-z]+://', mb_substr($fp, 0, 10))) {
            return $this->setInputFile($fp);
        } // see if it's a local file

        elseif (file_exists($fp)) {
            return $this->setInputFile($fp);
        } // it must be a string

        $this->fp = $fp;

        return true;

        return $this->raiseError('Illegal input format', XML_PARSER_ERROR_INVALID_INPUT);
    }

    // }}}

    // {{{ parse()

    /**
     * Central parsing function.
     *
     * @return   true|object PEAR error     returns true on success, or a PEAR_Error otherwise
     */
    public function parse()
    {
        /**
         * reset the parser
         */

        $result = $this->reset();

        if ($this->isError($result)) {
            return $result;
        }

        // if $this->fp was fopened previously

        if (is_resource($this->fp)) {
            while ($data = fread($this->fp, 4096)) {
                if (!$this->_parseString($data, feof($this->fp))) {
                    $error = $this->raiseError();

                    $this->free();

                    return $error;
                }
            }

            // otherwise, $this->fp must be a string
        } else {
            if (!$this->_parseString($this->fp, true)) {
                $error = $this->raiseError();

                $this->free();

                return $error;
            }
        }

        $this->free();

        return true;
    }

    /**
     * XML_Parser::_parseString()
     *
     * @param string  $data
     * @param bool $eof
     * @return bool
     * @see    parseString()
     **/
    public function _parseString($data, $eof = false)
    {
        return xml_parse($this->parser, $data, $eof);
    }

    // }}}

    // {{{ parseString()

    /**
     * XML_Parser::parseString()
     *
     * Parses a string.
     *
     * @param string $data XML data
     * @param bool   $eof  If set and TRUE, data is the last piece of data sent in this parser
     * @return bool|\XML_Parser_Error Error|true   true on success or a PEAR Error
     * @see      _parseString()
     */
    public function parseString($data, $eof = false)
    {
        if (!isset($this->parser) || !is_resource($this->parser)) {
            $this->reset();
        }

        if (!$this->_parseString($data, $eof)) {
            $error = $this->raiseError();

            $this->free();

            return $error;
        }

        if (true === $eof) {
            $this->free();
        }

        return true;
    }

    /**
     * XML_Parser::free()
     *
     * Free the internal resources associated with the parser
     *
     **/
    public function free()
    {
        if (isset($this->parser) && is_resource($this->parser)) {
            xml_parser_free($this->parser);

            unset($this->parser);
        }

        if (isset($this->fp) && is_resource($this->fp)) {
            fclose($this->fp);
        }

        unset($this->fp);

        return null;
    }

    /**
     * XML_Parser::raiseError()
     *
     * Throws a XML_Parser_Error
     *
     * @param null $msg   the error message
     * @param int  $ecode the error message code
     * @return XML_Parser_Error
     */
    public function raiseError($msg = null, $ecode = 0)
    {
        $msg = $msg ?? $this->parser;

        $err = new XML_Parser_Error($msg, $ecode);

        return parent::raiseError($err);
    }

    // }}}

    // {{{ funcStartHandler()

    public function funcStartHandler($xp, $elem, $attribs)
    {
        $func = 'xmltag_' . $elem;

        if (mb_strstr($func, '.')) {
            $func = str_replace('.', '_', $func);
        }

        if (method_exists($this->HandlerObj, $func)) {
            call_user_func([&$this->HandlerObj, $func], $xp, $elem, $attribs);
        } elseif (method_exists($this->HandlerObj, 'xmltag')) {
            call_user_func([&$this->HandlerObj, 'xmltag'], $xp, $elem, $attribs);
        }
    }

    // }}}

    // {{{ funcEndHandler()

    public function funcEndHandler($xp, $elem)
    {
        $func = 'xmltag_' . $elem . '_';

        if (mb_strstr($func, '.')) {
            $func = str_replace('.', '_', $func);
        }

        if (method_exists($this->HandlerObj, $func)) {
            call_user_func([&$this->HandlerObj, $func], $xp, $elem);
        } elseif (method_exists($this->HandlerObj, 'xmltag_')) {
            call_user_func([&$this->HandlerObj, 'xmltag_'], $xp, $elem);
        }
    }

    // }}}

    // {{{ startHandler()

    /**
     * @abstract
     * @param mixed $xp
     * @param mixed $elem
     * @param mixed $attribs
     * @return null
     * @return null
     */
    public function startHandler($xp, $elem, &$attribs)
    {
        return null;
    }

    // }}}

    // {{{ endHandler()

    /**
     * @abstract
     * @param mixed $xp
     * @param mixed $elem
     * @return null
     * @return null
     */
    public function endHandler($xp, $elem)
    {
        return null;
    }

    // }}}me
}

/**
 * error class, replaces PEAR_Error
 *
 * An instance of this class will be returned
 * if an error occurs inside XML_Parser.
 *
 * There are three advantages over using the standard PEAR_Error:
 * - All messages will be prefixed
 * - check for XML_Parser error, using is_a( $error, 'XML_Parser_Error' )
 * - messages can be generated from the xml_parser resource
 *
 * @see     PEAR_Error
 */
class XML_Parser_Error extends PEAR_Error
{
    // {{{ properties

    /**
     * prefix for all messages
     *
     * @var      string
     */

    public $error_message_prefix = 'XML_Parser: ';

    // }}}

    // {{{ constructor()

    /**
     * construct a new error instance
     *
     * You may either pass a message or an xml_parser resource as first
     * parameter. If a resource has been passed, the last error that
     * happened will be retrieved and returned.
     *
     * @param mixed $msgorparser
     * @param mixed $code
     * @param mixed $mode
     * @param mixed $level
     */
    public function __construct($msgorparser = 'unknown error', $code = 0, $mode = PEAR_ERROR_RETURN, $level = E_USER_NOTICE)
    {
        if (is_resource($msgorparser)) {
            $code = xml_get_error_code($msgorparser);

            $msgorparser = sprintf(
                '%s at XML input line %d:%d',
                xml_error_string($code),
                xml_get_current_line_number($msgorparser),
                xml_get_current_column_number($msgorparser)
            );
        }

        parent::__construct($msgorparser, $code, $mode, $level);
    }

    // }}}
}
