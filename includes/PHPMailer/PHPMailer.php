<?php
namespace PHPMailer\PHPMailer;

/**
 * PHPMailer - PHP email transport class
 * NOTE: Requires PHP version 5.5 or later
 * 
 * This is a simplified version of the PHPMailer class for the EazyHaven project
 */
class PHPMailer
{
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';

    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';

    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    /**
     * Email priority.
     * Options: null (default), 1 = High, 3 = Normal, 5 = low.
     *
     * @var int|null
     */
    public $Priority;

    /**
     * The character set of the message.
     *
     * @var string
     */
    public $CharSet = self::CHARSET_UTF8;

    /**
     * The MIME Content-type of the message.
     *
     * @var string
     */
    public $ContentType = self::CONTENT_TYPE_TEXT_HTML;

    /**
     * The message encoding.
     * Options: "8bit", "7bit", "binary", "base64", and "quoted-printable".
     *
     * @var string
     */
    public $Encoding = self::ENCODING_QUOTED_PRINTABLE;

    /**
     * Holds the most recent mailer error message.
     *
     * @var string
     */
    public $ErrorInfo = '';

    /**
     * The From email address for the message.
     *
     * @var string
     */
    public $From = '';

    /**
     * The From name of the message.
     *
     * @var string
     */
    public $FromName = '';

    /**
     * The envelope sender of the message.
     * This will usually be turned into a Return-Path header by the receiver,
     * and is the address that bounces will be sent to.
     *
     * @var string
     */
    public $Sender = '';

    /**
     * The Subject of the message.
     *
     * @var string
     */
    public $Subject = '';

    /**
     * The HTML body of the message.
     *
     * @var string
     */
    public $Body = '';

    /**
     * The plain-text body of the message.
     * This is only used if HTML is not set or a plain-text message is forced
     *
     * @var string
     */
    public $AltBody = '';

    /**
     * An array of all recipients (to, cc, bcc).
     *
     * @var array
     */
    protected $all_recipients = [];

    /**
     * An array of names and addresses to send the message to.
     *
     * @var array
     */
    protected $to = [];

    /**
     * An array of names and addresses to send the message CC to.
     *
     * @var array
     */
    protected $cc = [];

    /**
     * An array of names and addresses to send the message BCC to.
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * An array of reply-to names and addresses.
     *
     * @var array
     */
    protected $ReplyTo = [];

    /**
     * An array of all custom headers.
     *
     * @var array
     */
    protected $CustomHeader = [];

    /**
     * The complete MIME message body.
     *
     * @var string
     */
    protected $MIMEBody = '';

    /**
     * The complete MIME message headers.
     *
     * @var string
     */
    protected $MIMEHeader = '';

    /**
     * Word-wrap the message body to this number of chars.
     * Set to 0 to not wrap. A useful value here is 78, for RFC2822 section 2.1.1 compliance.
     *
     * @var int
     */
    public $WordWrap = 0;

    /**
     * Which method to use to send mail.
     * Options: "mail", "sendmail", or "smtp".
     *
     * @var string
     */
    public $Mailer = 'smtp';

    /**
     * The path to the sendmail program.
     *
     * @var string
     */
    public $Sendmail = '/usr/sbin/sendmail';

    /**
     * Whether to send message using SMTP.
     *
     * @var bool
     */
    public $SMTPAuth = true;

    /**
     * SMTP username.
     *
     * @var string
     */
    public $Username = '';

    /**
     * SMTP password.
     *
     * @var string
     */
    public $Password = '';

    /**
     * SMTP server host.
     *
     * @var string
     */
    public $Host = '';

    /**
     * The default SMTP server port.
     *
     * @var int
     */
    public $Port = 587;

    /**
     * The SMTP server timeout in seconds.
     *
     * @var int
     */
    public $Timeout = 300;

    /**
     * SMTP class debug output mode.
     * Debug output level.
     * Options:
     * * 0: No output
     * * 1: Commands
     * * 2: Data and commands
     * * 3: As 2 plus connection status
     * * 4: Low-level data output
     *
     * @var int
     */
    public $SMTPDebug = 0;

    /**
     * How to handle debug output.
     * Options:
     * * 'echo': Output plain-text as-is, appropriate for CLI
     * * 'html': Output escaped, line breaks converted to <br>, appropriate for browser output
     * * 'error_log': Output to error log as configured in php.ini
     * * callable: Call a custom function with the debug message as parameter
     *
     * @var string|callable
     */
    public $Debugoutput = 'echo';

    /**
     * SMTP connection object.
     *
     * @var SMTP
     */
    protected $smtp;

    /**
     * The array of 'to' names and addresses.
     *
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool true on success
     */
    public function addAddress($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('to', $address, $name);
    }

    /**
     * Add an address to one of the recipient arrays or to the ReplyTo array.
     * Addresses that have been added already return false, but do not throw exceptions.
     *
     * @param string $kind    One of 'to', 'cc', 'bcc', or 'ReplyTo'
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool true on success
     */
    protected function addOrEnqueueAnAddress($kind, $address, $name)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        
        if (($pos = strrpos($address, '@')) === false) {
            $this->ErrorInfo = 'Invalid address: ' . $address;
            return false;
        }
        
        $params = [$kind, $address, $name];
        
        // Enqueue addresses with IDN until we know the host has been processed
        if ($this->has8bitChars(substr($address, ++$pos))) {
            if ($kind != 'Reply-To') {
                $this->$kind[] = [$address, $name];
            } else {
                $this->ReplyTo[] = [$address, $name];
            }
            return true;
        }
        
        return call_user_func_array([$this, 'addAnAddress'], $params);
    }

    /**
     * Add an address to one of the recipient arrays or to the ReplyTo array.
     * Addresses that have been added already return false, but do not throw exceptions.
     *
     * @param string $kind    One of 'to', 'cc', 'bcc', or 'ReplyTo'
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool true on success
     */
    protected function addAnAddress($kind, $address, $name = '')
    {
        if (!in_array($kind, ['to', 'cc', 'bcc', 'Reply-To'])) {
            $this->ErrorInfo = "Invalid recipient kind: $kind";
            return false;
        }
        
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->ErrorInfo = "Invalid email address: $address";
            return false;
        }
        
        if ($kind != 'Reply-To') {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                $this->{$kind}[] = [$address, $name];
                $this->all_recipients[strtolower($address)] = true;
                return true;
            }
        } elseif (!array_key_exists(strtolower($address), $this->ReplyTo)) {
            $this->ReplyTo[strtolower($address)] = [$address, $name];
            return true;
        }
        
        return false;
    }

    /**
     * Check if a string contains 8-bit characters.
     *
     * @param string $text
     *
     * @return bool
     */
    public function has8bitChars($text)
    {
        return (bool) preg_match('/[\x80-\xFF]/', $text);
    }

    /**
     * Add a "CC" address.
     *
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool true on success
     */
    public function addCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('cc', $address, $name);
    }

    /**
     * Add a "BCC" address.
     *
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool true on success
     */
    public function addBCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('bcc', $address, $name);
    }

    /**
     * Add a "Reply-To" address.
     *
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool true on success
     */
    public function addReplyTo($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('Reply-To', $address, $name);
    }

    /**
     * Set the From name and email address.
     *
     * @param string $address The email address
     * @param string $name    The name
     *
     * @return bool
     */
    public function setFrom($address, $name = '')
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->ErrorInfo = "Invalid From email address: $address";
            return false;
        }
        
        $this->From = $address;
        $this->FromName = $name;
        
        return true;
    }

    /**
     * Send the email using SMTP.
     *
     * @return bool
     */
    public function send()
    {
        try {
            if (!$this->smtp) {
                $this->smtp = new SMTP();
                
                if ($this->SMTPDebug) {
                    $this->smtp->do_debug = $this->SMTPDebug;
                }
            }
            
            // Connect to the SMTP server
            if (!$this->smtp->connect($this->Host, $this->Port)) {
                throw new Exception('Failed to connect to SMTP server: ' . $this->Host);
            }
            
            // Say hello
            if (!$this->smtp->hello(gethostname())) {
                throw new Exception('SMTP EHLO failed: ' . $this->smtp->getError()['error']);
            }
            
            // Authenticate if needed
            if ($this->SMTPAuth) {
                if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                    throw new Exception('SMTP authentication failed: ' . $this->smtp->getError()['error']);
                }
            }
            
            // Send the message
            if (!$this->smtp->mail($this->From)) {
                throw new Exception('SMTP FROM command failed: ' . $this->smtp->getError()['error']);
            }
            
            // Recipients
            foreach ($this->to as $recipient) {
                if (!$this->smtp->recipient($recipient[0])) {
                    throw new Exception('SMTP TO command failed: ' . $this->smtp->getError()['error']);
                }
            }
            
            foreach ($this->cc as $recipient) {
                if (!$this->smtp->recipient($recipient[0])) {
                    throw new Exception('SMTP CC command failed: ' . $this->smtp->getError()['error']);
                }
            }
            
            foreach ($this->bcc as $recipient) {
                if (!$this->smtp->recipient($recipient[0])) {
                    throw new Exception('SMTP BCC command failed: ' . $this->smtp->getError()['error']);
                }
            }
            
            // Message data
            if (!$this->smtp->data($this->createHeader() . $this->createBody())) {
                throw new Exception('SMTP DATA command failed: ' . $this->smtp->getError()['error']);
            }
            
            // Close connection
            $this->smtp->quit();
            $this->smtp->close();
            
            return true;
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * Create email headers.
     *
     * @return string
     */
    protected function createHeader()
    {
        $header = 'Date: ' . date('r') . "\r\n";
        $header .= 'To: ';
        
        $toNames = [];
        foreach ($this->to as $toName) {
            $toNames[] = $this->addrFormat($toName);
        }
        $header .= implode(', ', $toNames) . "\r\n";
        
        $header .= 'From: ' . $this->addrFormat([$this->From, $this->FromName]) . "\r\n";
        $header .= 'Subject: ' . $this->encodeHeader($this->Subject) . "\r\n";
        
        if (count($this->cc) > 0) {
            $header .= 'Cc: ';
            $toNames = [];
            foreach ($this->cc as $toName) {
                $toNames[] = $this->addrFormat($toName);
            }
            $header .= implode(', ', $toNames) . "\r\n";
        }
        
        if (count($this->ReplyTo) > 0) {
            $header .= 'Reply-To: ';
            $toNames = [];
            foreach ($this->ReplyTo as $toName) {
                $toNames[] = $this->addrFormat($toName);
            }
            $header .= implode(', ', $toNames) . "\r\n";
        }
        
        $header .= 'MIME-Version: 1.0' . "\r\n";
        $header .= 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet . "\r\n";
        $header .= 'Content-Transfer-Encoding: ' . $this->Encoding . "\r\n";
        
        return $header;
    }

    /**
     * Create the message body.
     *
     * @return string
     */
    protected function createBody()
    {
        $body = $this->Body;
        
        if ($this->Encoding == self::ENCODING_QUOTED_PRINTABLE) {
            $body = quoted_printable_encode($body);
        } elseif ($this->Encoding == self::ENCODING_BASE64) {
            $body = base64_encode($body);
        }
        
        return $body;
    }

    /**
     * Format an address for a message header.
     *
     * @param array $addr A 2-element indexed array, element 0 containing an address, element 1 containing a name
     *
     * @return string
     */
    protected function addrFormat($addr)
    {
        if (empty($addr[1])) {
            return $addr[0];
        }
        
        return $this->encodeHeader($addr[1]) . ' <' . $addr[0] . '>';
    }

    /**
     * Encode a header value for proper display in a message header.
     *
     * @param string $str The text to encode
     *
     * @return string
     */
    protected function encodeHeader($str)
    {
        if ($this->has8bitChars($str)) {
            // Use Q encoding
            $encoded = $this->base64EncodeWrapMB($str);
            if (strlen($encoded) > 74) {
                return $encoded;
            }
            return '=?' . $this->CharSet . '?Q?' . $encoded . '?=';
        }
        
        return $str;
    }

    /**
     * Base64 encode a string ensuring it doesn't exceed 76 chars per line.
     *
     * @param string $str The string to encode
     *
     * @return string
     */
    protected function base64EncodeWrapMB($str)
    {
        return base64_encode($str);
    }
}