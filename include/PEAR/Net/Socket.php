<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stig Bakken <ssb@php.net>                                   |
// |          Chuck Hagenbuch <chuck@horde.org>                           |
// +----------------------------------------------------------------------+
//
// $Id: Socket.php,v 1.1 2006/03/29 05:57:10 mikhail Exp $

require_once sprintf('%s/modules/%s/include/PEAR/PEAR.php', XOOPS_ROOT_PATH, $xoopsModule->dirname());

define('NET_SOCKET_READ', 1);
define('NET_SOCKET_WRITE', 2);
define('NET_SOCKET_ERROR', 3);

/**
 * Generalized Socket class.
 *
 * @version 1.1
 * @author  Stig Bakken <ssb@php.net>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 */
class Net_Socket extends PEAR
{
    /**
     * Socket file pointer.
     * @var resource
     */

    public $fp = null;

    /**
     * Whether the socket is blocking. Defaults to true.
     * @var bool
     */

    public $blocking = true;

    /**
     * Whether the socket is persistent. Defaults to false.
     * @var bool
     */

    public $persistent = false;

    /**
     * The IP address to connect to.
     * @var string
     */

    public $addr = '';

    /**
     * The port number to connect to.
     * @var int
     */

    public $port = 0;

    /**
     * Number of seconds to wait on socket connections before assuming
     * there's no more data. Defaults to no timeout.
     * @var int
     */

    public $timeout = false;

    /**
     * Number of bytes to read at a time in readLine() and
     * readAll(). Defaults to 2048.
     * @var int
     */

    public $lineLength = 2048;

    /**
     * Connect to the specified port. If called when the socket is
     * already connected, it disconnects and connects again.
     *
     * @param string $addr         IP address or host name.
     * @param int    $port         TCP port number.
     * @param null   $persistent   (optional) Whether the connection is
     *                             persistent (kept open between requests
     *                             by the web server).
     * @param null   $timeout      (optional) How long to wait for data.
     * @param null   $options      See options for stream_context_create.
     *
     *
     * @return bool | PEAR_Error  True on success or a PEAR_Error on failure.
     */
    public function connect($addr, $port = 0, $persistent = null, $timeout = null, $options = null)
    {
        if (is_resource($this->fp)) {
            @fclose($this->fp);

            $this->fp = null;
        }

        if (!$addr) {
            return $this->raiseError('$addr cannot be empty');
        } elseif (strspn($addr, '.0123456789') == mb_strlen($addr)
                  || false !== mb_strstr($addr, '/')) {
            $this->addr = $addr;
        } else {
            $this->addr = @gethostbyname($addr);
        }

        $this->port = $port % 65536;

        if (null !== $persistent) {
            $this->persistent = $persistent;
        }

        if (null !== $timeout) {
            $this->timeout = $timeout;
        }

        $openfunc = $this->persistent ? 'pfsockopen' : 'fsockopen';

        $errno = 0;

        $errstr = '';

        if ($options && function_exists('stream_context_create')) {
            if ($this->timeout) {
                $timeout = $this->timeout;
            } else {
                $timeout = 0;
            }

            $context = stream_context_create($options);

            $fp = @$openfunc($this->addr, $this->port, $errno, $errstr, $timeout, $context);
        } else {
            if ($this->timeout) {
                $fp = @$openfunc($this->addr, $this->port, $errno, $errstr, $this->timeout);
            } else {
                $fp = @$openfunc($this->addr, $this->port, $errno, $errstr);
            }
        }

        if (!$fp) {
            return $this->raiseError($errstr, $errno);
        }

        $this->fp = $fp;

        return $this->setBlocking($this->blocking);
    }

    /**
     * Disconnects from the peer, closes the socket.
     *
     * @return mixed true on success or an error object otherwise
     */
    public function disconnect()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        @fclose($this->fp);

        $this->fp = null;

        return true;
    }

    /**
     * Find out if the socket is in blocking mode.
     *
     * @return bool  The current blocking mode.
     */
    public function isBlocking()
    {
        return $this->blocking;
    }

    /**
     * Sets whether the socket connection should be blocking or
     * not. A read call to a non-blocking socket will return immediately
     * if there is no data available, whereas it will block until there
     * is data for blocking sockets.
     *
     * @param bool $mode True for blocking sockets, false for nonblocking.
     * @return mixed true on success or an error object otherwise
     */
    public function setBlocking($mode)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $this->blocking = $mode;

        stream_set_blocking($this->fp, $this->blocking);

        return true;
    }

    /**
     * Sets the timeout value on socket descriptor,
     * expressed in the sum of seconds and microseconds
     *
     * @param int $seconds      Seconds.
     * @param int $microseconds Microseconds.
     * @return mixed true on success or an error object otherwise
     */
    public function setTimeout($seconds, $microseconds)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return stream_set_timeout($this->fp, $seconds, $microseconds);
    }

    /**
     * Returns information about an existing socket resource.
     * Currently returns four entries in the result array:
     *
     * <p>
     * timed_out (bool) - The socket timed out waiting for data<br>
     * blocked (bool) - The socket was blocked<br>
     * eof (bool) - Indicates EOF event<br>
     * unread_bytes (int) - Number of bytes left in the socket buffer<br>
     * </p>
     *
     * @return mixed Array containing information about existing socket resource or an error object otherwise
     */
    public function getStatus()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return stream_get_meta_data($this->fp);
    }

    /**
     * Get a specified line of data
     *
     * @param mixed $size
     * @return bytes of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    public function gets($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return @fgets($this->fp, $size);
    }

    /**
     * Read a specified amount of data. This is guaranteed to return,
     * and has the added benefit of getting everything in one fread()
     * chunk; if you know the size of the data you're getting
     * beforehand, this is definitely the way to go.
     *
     * @param int $size The number of bytes to read from the socket.
     * @return bytes of data from the socket, or a PEAR_Error if
     *                      not connected.
     */
    public function read($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return @fread($this->fp, $size);
    }

    /**
     * Write a specified amount of data.
     *
     * @param string $data        Data to write.
     * @param null   $blocksize   Amount of data to write at once.
     *                            NULL means all at once.
     *
     * @return mixed true on success or an error object otherwise
     */
    public function write($data, $blocksize = null)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        if (null === $blocksize && !OS_WINDOWS) {
            return fwrite($this->fp, $data);
        }  

        if (null === $blocksize) {
            $blocksize = 1024;
        }

        $pos = 0;

        $size = mb_strlen($data);

        while ($pos < $size) {
            $written = @fwrite($this->fp, mb_substr($data, $pos, $blocksize));

            if (false === $written) {
                return false;
            }

            $pos += $written;
        }

        return $pos;
    }

    /**
     * Write a line of data to the socket, followed by a trailing "\r\n".
     *
     * @param mixed $data
     * @return mixed fputs result, or an error
     */
    public function writeLine($data)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return fwrite($this->fp, $data . "\r\n");
    }

    /**
     * Tests for end-of-file on a socket descriptor.
     *
     * @return bool
     */
    public function eof()
    {
        return (is_resource($this->fp) && feof($this->fp));
    }

    /**
     * Reads a byte of data
     *
     * @return int|object|\PEAR_Error 1 byte of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    public function readByte()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return ord(@fread($this->fp, 1));
    }

    /**
     * Reads a word of data
     *
     * @return int|object|\PEAR_Error 1 word of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    public function readWord()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 2);

        return (ord($buf[0]) + (ord($buf[1]) << 8));
    }

    /**
     * Reads an int of data
     *
     * @return int  1 int of data from the socket, or a PEAR_Error if
     *                  not connected.
     */
    public function readInt()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 4);

        return (ord($buf[0]) + (ord($buf[1]) << 8) + (ord($buf[2]) << 16) + (ord($buf[3]) << 24));
    }

    /**
     * Reads a zero-terminated string of data
     *
     * @return string, or a PEAR_Error if
     *         not connected.
     */
    public function readString()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $string = '';

        while ("\x00" != ($char = @fread($this->fp, 1))) {
            $string .= $char;
        }

        return $string;
    }

    /**
     * Reads an IP Address and returns it in a dot formated string
     *
     * @return Dot formated string, or a PEAR_Error if
     *         not connected.
     */
    public function readIPAddress()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 4);

        return sprintf(
            '%s.%s.%s.%s',
            ord($buf[0]),
            ord($buf[1]),
            ord($buf[2]),
            ord($buf[3])
        );
    }

    /**
     * Read until either the end of the socket or a newline, whichever
     * comes first. Strips the trailing newline from the returned data.
     *
     * @return All available data up to a newline, without that
     *         newline, or until the end of the socket, or a PEAR_Error if
     *         not connected.
     */
    public function readLine()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $line = '';

        $timeout = time() + $this->timeout;

        while (!feof($this->fp) && (!$this->timeout || time() < $timeout)) {
            $line .= @fgets($this->fp, $this->lineLength);

            if ("\n" == mb_substr($line, -1)) {
                return rtrim($line, "\r\n");
            }
        }

        return $line;
    }

    /**
     * Read until the socket closes, or until there is no more data in
     * the inner PHP buffer. If the inner buffer is empty, in blocking
     * mode we wait for at least 1 byte of data. Therefore, in
     * blocking mode, if there is no data at all to be read, this
     * function will never exit (unless the socket is closed on the
     * remote end).
     *
     *
     * @return string  All data until the socket closes, or a PEAR_Error if
     *                 not connected.
     */
    public function readAll()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $data = '';

        while (!feof($this->fp)) {
            $data .= @fread($this->fp, $this->lineLength);
        }

        return $data;
    }

    /**
     * Runs the equivalent of the select() system call on the socket
     * with a timeout specified by tv_sec and tv_usec.
     *
     * @param int $state   Which of read/write/error to check for.
     * @param int $tv_sec  Number of seconds for timeout.
     * @param int $tv_usec Number of microseconds for timeout.
     *
     * @return false if select fails, integer describing which of read/write/error
     *         are ready, or PEAR_Error if not connected.
     */
    public function select($state, $tv_sec, $tv_usec = 0)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $read = null;

        $write = null;

        $except = null;

        if ($state & NET_SOCKET_READ) {
            $read[] = $this->fp;
        }

        if ($state & NET_SOCKET_WRITE) {
            $write[] = $this->fp;
        }

        if ($state & NET_SOCKET_ERROR) {
            $except[] = $this->fp;
        }

        if (false === ($sr = stream_select($read, $write, $except, $tv_sec, $tv_usec))) {
            return false;
        }

        $result = 0;

        if (count($read)) {
            $result |= NET_SOCKET_READ;
        }

        if (count($write)) {
            $result |= NET_SOCKET_WRITE;
        }

        if (count($except)) {
            $result |= NET_SOCKET_ERROR;
        }

        return $result;
    }
}
