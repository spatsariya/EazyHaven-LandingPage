<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer RFC821 SMTP email transport class.
 * Implements RFC 821 SMTP commands and provides some utility methods for sending mail to an SMTP server.
 *
 * This is a simplified version for the EazyHaven project.
 */
class SMTP
{
    /**
     * The PHPMailer SMTP version number.
     *
     * @var string
     */
    const VERSION = '6.0.0';

    /**
     * SMTP line break constant.
     *
     * @var string
     */
    const CRLF = "\r\n";

    /**
     * Debug level for no output.
     *
     * @var int
     */
    const DEBUG_OFF = 0;

    /**
     * Debug level to show client -> server messages.
     *
     * @var int
     */
    const DEBUG_CLIENT = 1;

    /**
     * Debug level to show client -> server and server -> client messages.
     *
     * @var int
     */
    const DEBUG_SERVER = 2;

    /**
     * Debug level to show connection status, client -> server and server -> client messages.
     *
     * @var int
     */
    const DEBUG_CONNECTION = 3;

    /**
     * Debug level to show all messages.
     *
     * @var int
     */
    const DEBUG_LOWLEVEL = 4;

    /**
     * Debug output level.
     * Options: 0 = no output, 1-4 increasing verbosity, 4 full output.
     *
     * @var int
     */
    public $do_debug = self::DEBUG_OFF;

    /**
     * How to handle debug output.
     * Options: `echo`, `html` or `error_log`.
     *
     * @var string
     */
    public $Debugoutput = 'echo';

    /**
     * The timeout value for connection, in seconds.
     *
     * @var int
     */
    public $Timeout = 300;

    /**
     * The SMTP server host.
     *
     * @var string
     */
    protected $host = '';

    /**
     * The SMTP server port.
     *
     * @var int
     */
    protected $port = 25;

    /**
     * The socket for the server connection.
     *
     * @var resource|null
     */
    protected $smtp_conn;

    /**
     * Error information, if any, for the last SMTP command.
     *
     * @var array
     */
    protected $error = [
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => '',
    ];

    /**
     * Connect to an SMTP server.
     *
     * @param string $host    SMTP server IP or host name
     * @param int    $port    The port number to connect to
     * @param int    $timeout How long to wait for the connection to open
     *
     * @return bool
     */
    public function connect($host, $port = null, $timeout = 30)
    {
        // Clear errors to avoid confusion
        $this->setError('');
        
        // Make sure we are not already connected
        if ($this->connected()) {
            $this->setError('Already connected to a server');
            return false;
        }
        
        if (empty($port)) {
            $port = $this->port;
        }
        
        // Connect to the SMTP server
        $this->edebug("Connection: opening to $host:$port", self::DEBUG_CONNECTION);
        $this->smtp_conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!is_resource($this->smtp_conn)) {
            $this->setError("Failed to connect to server: $errstr ($errno)");
            return false;
        }
        
        // Verify the connection is established
        $this->edebug('Connection: opened', self::DEBUG_CONNECTION);
        
        // SMTP server can take longer to respond, give longer timeout for first read
        stream_set_timeout($this->smtp_conn, $timeout, 0);
        
        // Get any announcement
        $announce = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: ' . $announce, self::DEBUG_SERVER);
        
        return true;
    }

    /**
     * Initiate a TLS (encrypted) session.
     *
     * @return bool
     */
    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }
        
        // Begin encrypted connection
        if (!stream_socket_enable_crypto(
            $this->smtp_conn,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        )) {
            return false;
        }
        
        return true;
    }

    /**
     * Perform SMTP authentication.
     * Must be run after hello().
     *
     * @param string $username The user name
     * @param string $password The password
     *
     * @return bool
     */
    public function authenticate($username, $password)
    {
        // Start authentication
        if (!$this->sendCommand('AUTH LOGIN', 'AUTH LOGIN', 334)) {
            return false;
        }
        
        // Send encoded username
        if (!$this->sendCommand('Username', base64_encode($username), 334)) {
            return false;
        }
        
        // Send encoded password
        if (!$this->sendCommand('Password', base64_encode($password), 235)) {
            return false;
        }
        
        return true;
    }

    /**
     * Sends the MAIL command to indicate the sender.
     *
     * @param string $from The sender email address
     *
     * @return bool
     */
    public function mail($from)
    {
        return $this->sendCommand('MAIL FROM', "MAIL FROM:<$from>", 250);
    }

    /**
     * Sends the RCPT command to indicate a recipient.
     *
     * @param string $to The recipient
     *
     * @return bool
     */
    public function recipient($to)
    {
        return $this->sendCommand('RCPT TO', "RCPT TO:<$to>", 250);
    }

    /**
     * Sends the DATA command and then the message content.
     *
     * @param string $msg Message content
     *
     * @return bool
     */
    public function data($msg)
    {
        // SMTP command to indicate message start
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }
        
        // Send the message content
        $msg = str_replace("\r\n.", "\r\n..", $msg);
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg));
        
        $field = substr($lines[0], 0, strpos($lines[0], ':'));
        $in_headers = false;
        if (!empty($field) && !strstr($field, ' ')) {
            $in_headers = true;
        }
        
        foreach ($lines as $line) {
            $lines_out = [];
            if ($in_headers && $line === '') {
                $in_headers = false;
            }
            
            // Normalize line breaks before adding to $lines_out
            $line_out = trim($line);
            
            // RFC 5321 section 4.5.2
            // max line length is 1000 chars including CRLF
            // actual limitation is likely to be 2,000 chars
            if (strlen($line_out) > 998) {
                $line_out = wordwrap($line_out, 998, ' ' . self::CRLF . '    ', true);
                $line_out = trim($line_out);
            }
            
            if (!empty($line_out)) {
                $lines_out[] = $line_out;
            }
            
            foreach ($lines_out as $line_out) {
                // RFC 5321 section 4.5.2
                if (!empty($line_out) && $line_out[0] === '.') {
                    $line_out = '.' . $line_out;
                }
                $this->client_send($line_out . self::CRLF);
            }
        }
        
        // End the message with a single dot
        return $this->sendCommand('DATA END', '.', 250);
    }

    /**
     * Says hello to the server with an SMTP HELO command.
     *
     * @param string $host The host name to say hello to
     *
     * @return bool
     */
    public function hello($host = '')
    {
        // Try EHLO first
        if (!$this->sendCommand('EHLO', "EHLO $host", 250)) {
            // If EHLO fails, try HELO
            if (!$this->sendCommand('HELO', "HELO $host", 250)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Send an SMTP QUIT command.
     * Closes the socket if there is no error or the $close_on_error argument is true.
     *
     * @param bool $close_on_error Should the connection close if an error occurs?
     *
     * @return bool
     */
    public function quit($close_on_error = true)
    {
        $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
        $err = $this->error; // Save any error
        
        if ($noerror || $close_on_error) {
            $this->close();
            $this->error = $err; // Restore any error from the quit command
        }
        
        return $noerror;
    }

    /**
     * Close the socket and clean up the state of the class.
     * Don't use this function without first trying to use QUIT.
     *
     * @return void
     */
    public function close()
    {
        $this->setError('');
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
            $this->edebug('Connection: closed', self::DEBUG_CONNECTION);
        }
    }

    /**
     * Check connection state.
     *
     * @return bool True if connected, false otherwise
     */
    public function connected()
    {
        return is_resource($this->smtp_conn);
    }

    /**
     * Send an SMTP command to the server.
     *
     * @param string $command      The SMTP command name, e.g. "EHLO"
     * @param string $commandvalue The SMTP command string, e.g. "hello.example.com"
     * @param int    $expect       The expected response code
     *
     * @return bool True if the command succeeded, false otherwise
     */
    protected function sendCommand($command, $commandvalue, $expect)
    {
        if (!$this->connected()) {
            $this->setError("Called $command without being connected");
            return false;
        }
        
        // Send the command
        $this->client_send($commandvalue . self::CRLF);
        
        $this->edebug('CLIENT -> SERVER: ' . $commandvalue, self::DEBUG_CLIENT);
        
        // Read the response
        $reply = $this->get_lines();
        
        $this->edebug('SERVER -> CLIENT: ' . $reply, self::DEBUG_SERVER);
        
        // Extract the code from the reply
        $code = substr($reply, 0, 3);
        
        if (!in_array($code, (array) $expect)) {
            $this->setError(
                "$command command failed",
                $reply,
                $code
            );
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ': ' . $reply, self::DEBUG_CLIENT);
            return false;
        }
        
        $this->setError('');
        return true;
    }

    /**
     * Send raw data to the server.
     *
     * @param string $data The data to send
     *
     * @return int|bool The number of bytes sent to the server or false on error
     */
    protected function client_send($data)
    {
        $this->edebug('CLIENT -> SERVER: ' . $data, self::DEBUG_CLIENT);
        return fwrite($this->smtp_conn, $data);
    }

    /**
     * Get the latest error.
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set error information.
     *
     * @param string $message  The error message
     * @param string $detail   Further detail on the error
     * @param string $code     An SMTP error code
     * @param string $code_ex  Extended SMTP code
     *
     * @return void
     */
    protected function setError($message, $detail = '', $code = '', $code_ex = '')
    {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
            'smtp_code' => $code,
            'smtp_code_ex' => $code_ex,
        ];
    }

    /**
     * Get a response from the server.
     *
     * @return string
     */
    protected function get_lines()
    {
        // If the connection is bad, give up
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        
        $data = '';
        $endtime = time() + $this->Timeout;
        
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, 515);
            $this->edebug("SMTP -> get_lines(): \$data is \"$data\"", self::DEBUG_LOWLEVEL);
            $this->edebug("SMTP -> get_lines(): \$str is \"$str\"", self::DEBUG_LOWLEVEL);
            $data .= $str;
            $this->edebug("SMTP -> get_lines(): \$data is \"$data\"", self::DEBUG_LOWLEVEL);
            
            // If response is only 3 chars (not valid, but RFC5321 S4.2 says it must be handled),
            // or 4th character is a space, we are done reading, break the loop.
            // String array access is a significant micro-optimization over strlen
            if (!isset($str[3]) || (isset($str[3]) && $str[3] === ' ')) {
                break;
            }
            
            // Timed-out? Log and break
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->edebug(
                    'SMTP -> get_lines(): timed-out (' . $this->Timeout . ' seconds)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }
            
            // Now check if reads took too long
            if (time() > $endtime) {
                $this->edebug(
                    'SMTP -> get_lines(): timelimit reached (' . $this->Timeout . ' seconds)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }
        }
        
        return $data;
    }

    /**
     * Output debug info via a user-defined method.
     *
     * @param string $str   Debug string to output
     * @param int    $level The debug level of this message; see DEBUG_* constants
     *
     * @return void
     */
    protected function edebug($str, $level = 0)
    {
        if ($level > $this->do_debug) {
            return;
        }
        
        // Don't output debug info for the low-level protocol
        if ($level === self::DEBUG_LOWLEVEL) {
            return;
        }

        // Map debug level to output method
        switch ($this->Debugoutput) {
            case 'error_log':
                // Don't output to error log if debugging level is DEBUG_LOWLEVEL
                error_log($str);
                break;
            case 'html':
                // Don't be tempted to add HTML breaks to output
                echo htmlentities(
                    preg_replace('/[\r\n]+/', '', $str),
                    ENT_QUOTES,
                    'UTF-8'
                ) . "<br>\n";
                break;
            case 'echo':
            default:
                // Most debugging output, just echo it
                echo $str . "\n";
        }
    }
}