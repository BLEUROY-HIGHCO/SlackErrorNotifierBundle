<?php

namespace Highco\SlackErrorNotifierBundle\Listener;

use Highco\SlackErrorNotifierBundle\Formatter\SlackExceptionFormatterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Notifier
 */
class Notifier
{
    //<editor-fold desc="Members">
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SlackExceptionFormatterInterface
     */
    private $formatter;

    /**
     * @var string
     */
    private $errorsDir;

    /**
     * HTTP request.
     *
     * @var Request
     */
    private $request;

    /**
     * Slack webhook token.
     *
     * @var string
     */
    private $webhookToken;

    /**
     * Handling 404.
     *
     * @var bool
     */
    private $handle404;

    /**
     * Handling HTTP codes others than 400 and 500.
     *
     * @var array
     */
    private $handleHTTPcodes;

    /**
     * Ignored classes.
     *
     * @var array
     */
    private $ignoredClasses;

    /**
     * Ignored php errors.
     *
     * @var array
     */
    private $ignoredPhpErrors;

    /**
     * Report warnings.
     *
     * @var bool
     */
    private $reportWarnings = false;

    /**
     * Report warning.
     *
     * @var bool
     */
    private $reportErrors = false;

    /**
     * Report silent errors.
     *
     * @var bool
     */
    private $reportSilent = false;

    /**
     * Repeat ignore timeout.
     *
     * @var bool
     */
    private $repeatTimeout = false;

    /**
     * Ignored ips.
     *
     * @var array
     */
    private $ignoredIPs;

    /**
     * Ignored agents pattern.
     *
     * @var string
     */
    private $ignoredAgentsPattern;

    /**
     * Ignored url pattern
     *
     * @var string
     */
    private $ignoredUrlsPattern;

    /**
     * Executed command.
     *
     * @var Command
     */
    private $command;

    /**
     * Executed command input.
     *
     * @var InputInterface
     */
    private $commandInput;

    /**
     * Memory temp buffer.
     *
     * @var string
     */
    private static $tmpBuffer;
    //</editor-fold>

    /**
     * The constructor
     *
     * @param LoggerInterface                  $logger    Logger.
     * @param SlackExceptionFormatterInterface $formatter Exception formater.
     * @param string                           $cacheDir  App cache dir.
     * @param array                            $config    Bundle config.
     *
     * @internal param string $cacheDir cacheDir
     */
    public function __construct(LoggerInterface $logger, SlackExceptionFormatterInterface $formatter, $cacheDir, $config)
    {
        $this->logger    = $logger;
        $this->formatter = $formatter;

        //Get config parameters.
        $this->webhookToken         = $config['webhookToken'];
        $this->handle404            = $config['handle404'];
        $this->handleHTTPcodes      = $config['handleHTTPcodes'];
        $this->reportErrors         = $config['handlePHPErrors'];
        $this->reportWarnings       = $config['handlePHPWarnings'];
        $this->reportSilent         = $config['handleSilentErrors'];
        $this->ignoredClasses       = $config['ignoredClasses'];
        $this->ignoredPhpErrors     = $config['ignoredPhpErrors'];
        $this->repeatTimeout        = $config['repeatTimeout'];
        $this->ignoredIPs           = $config['ignoredIPs'];
        $this->ignoredAgentsPattern = $config['ignoredAgentsPattern'];
        $this->ignoredUrlsPattern   = $config['ignoredUrlsPattern'];

        $this->errorsDir = $cacheDir . '/errors';
        if (!is_dir($this->errorsDir)) {
            /** @noinspection MkdirRaceConditionInspection */
            @mkdir($this->errorsDir);
        }
    }

    //<editor-fold desc="Framework events">

    /**
     * Handle the event
     *
     * @param GetResponseForExceptionEvent $event event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $exception = $event->getException();
        $this->formatter->setRequest($event->getRequest());
        if ($exception instanceof HttpException) {
            if (in_array($event->getRequest()->getClientIp(), $this->ignoredIPs, true)) {
                return;
            }

            if (!empty($this->ignoredAgentsPattern) && preg_match('#' . $this->ignoredAgentsPattern . '#', $event->getRequest()->headers->get('User-Agent'))) {
                return;
            }

            if (!empty($this->ignoredUrlsPattern) && preg_match('#' . $this->ignoredUrlsPattern . '#', $event->getRequest()->getUri())) {
                return;
            }

            if (500 === $exception->getStatusCode()
                || (404 === $exception->getStatusCode() && true === $this->handle404)
                || in_array($exception->getStatusCode(), $this->handleHTTPcodes, true)
            ) {
                $this->createMessageAndLog($exception);
            }
        } else {
            if (in_array(get_class($exception), $this->ignoredClasses, false) === false) {
                $this->createMessageAndLog($exception);
            }
        }
    }

    /**
     * Handle the event
     *
     * @param ConsoleExceptionEvent $event event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $exception = $event->getException();

        if (in_array(get_class($exception), $this->ignoredClasses, false) === false) {
            $this->formatter->setCommand($this->command);
            $this->createMessageAndLog($exception);
        }
    }

    /**
     * Once we have the request we can use it to show debug details in the email
     *
     * Ideally the handlers would be registered earlier on in the boot process
     * so that compilation errors (like missing config files) could be caught
     * but that would mean that the DI Container wouldn't be completed so we'd
     * have to mess around with instantiating the mailer and twig etc
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->reportErrors || $this->reportWarnings) {
            self::reserveMemory();

            $this->formatter->setRequest($event->getRequest());
            $this->request = $event->getRequest();

            $this->setErrorHandlers();
        }
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $this->formatter->setRequest(null);

        $this->command      = $event->getCommand();
        $this->commandInput = $event->getInput();

        if ($this->reportErrors || $this->reportWarnings) {
            self::reserveMemory();

            $this->setErrorHandlers();
        }
    }
    //</editor-fold>

    //<editor-fold desc="Error handlers">
    /**
     * Set error handlers
     */
    protected function setErrorHandlers()
    {
        // set_error_handler and register_shutdown_function can be triggered on
        // both warnings and errors
        set_error_handler(array($this, 'handlePhpError'), E_ALL);

        // From PHP Documentation: the following error types cannot be handled with
        // a user defined function using set_error_handler: *E_ERROR*, *E_PARSE*, *E_CORE_ERROR*, *E_CORE_WARNING*,
        // *E_COMPILE_ERROR*, *E_COMPILE_WARNING*
        // That is we need to use also register_shutdown_function()
        register_shutdown_function(array($this, 'handlePhpFatalErrorAndWarnings'));
    }

    /**
     * @see http://php.net/set_error_handler
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     * @param array  $errorContext
     *
     * @return bool
     */
    public function handlePhpError($level, $message, $file, $line, $errorContext)
    {
        // don't catch error with error_repoting is 0
        if (false === $this->reportSilent && 0 === error_reporting()) {
            return false;
        }

        // there would be more warning codes but they are not caught by set_error_handler
        // but by register_shutdown_function
        $warningsCodes = array(E_NOTICE, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED);

        if (!$this->reportWarnings && in_array($level, $warningsCodes, false)) {
            return false;
        }

        if (in_array($message, $this->ignoredPhpErrors, false)) {
            return false;
        }

        $exception = new \ErrorException(sprintf('%s: %s in %s line %d', $this->getErrorString($level), $message, $file, $line), 0, $level, $file, $line);
        $this->formatter->setContext($errorContext);

        $this->createMessageAndLog($exception);

        // in order not to bypass the standard PHP error handler
        return false;
    }

    /**
     * @see http://php.net/register_shutdown_function
     * Use this shutdown function to see if there were any errors
     */
    public function handlePhpFatalErrorAndWarnings()
    {
        self::freeMemory();

        $lastError = error_get_last();

        if (null === $lastError) {
            return;
        }

        $errors = array();

        if ($this->reportErrors) {
            $errors = array_merge($errors, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR));
        }

        if ($this->reportWarnings) {
            $errors = array_merge($errors, array(E_CORE_WARNING, E_COMPILE_WARNING, E_STRICT));
        }

        if (in_array($lastError['type'], $errors, false) && !in_array(@$lastError['message'], $this->ignoredPhpErrors, false)) {
            $exception = new \ErrorException(sprintf('%s: %s in %s line %d', @$this->getErrorString(@$lastError['type']), @$lastError['message'], @$lastError['file'], @$lastError['line']), @$lastError['type'], @$lastError['type'], @$lastError['file'], @$lastError['line']);
            $this->createMessageAndLog($exception);
        }
    }

    /**
     * Convert the error code to a readable format
     *
     * @param int $errorNo
     *
     * @return string
     */
    public function getErrorString($errorNo)
    {
        // may be exhaustive, but not sure
        $errorStrings = array(
            E_WARNING           => 'Warning',
            E_NOTICE            => 'Notice',
            E_USER_ERROR        => 'User Error',
            E_USER_WARNING      => 'User Warning',
            E_USER_NOTICE       => 'User Notice',
            E_STRICT            => 'Runtime Notice (E_STRICT)',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
            E_ERROR             => 'Error',
            E_PARSE             => 'Parse Error',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        );

        return array_key_exists($errorNo, $errorStrings) ? $errorStrings[$errorNo] : 'UNKNOWN';
    }
    //</editor-fold>

    /**
     * Create message and log it.
     *
     * @param \Exception $exception Exception throwed.
     */
    public function createMessageAndLog(\Exception $exception)
    {
        if (!$exception instanceof FlattenException) {
            $exception = FlattenException::create($exception);
        }
        if ($this->repeatTimeout && $this->checkRepeat($exception)) {
            return;
        }

        $this->postMessage(json_encode($this->formatter->formatException($exception)));
    }

    /**
     * Post message to slack
     *
     * @param string $message Json formatted message.
     *
     * @return bool
     */
    private function postMessage($message)
    {
        $url = sprintf('https://hooks.slack.com/services/%s', $this->webhookToken);
        if (empty($message)) {
            return false;
        }
        $ch = curl_init();
        if (!$ch) {
            $this->logger->error('Failed to create curl handle');

            return false;
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($message),
            )
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        $response       = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpStatusCode !== 200) {
            $this->logger->error('Failed to post to slack: status ' . $httpStatusCode . ' | response : ' . $response);

            return false;
        }
        if ($response !== 'ok') {
            $this->logger->error('Didn\'t get an "ok" back from slack, got: ' . $response);

            return false;
        }

        return true;
    }

    /**
     * Check last send time
     *
     * @param FlattenException $exception
     *
     * @return bool
     */
    private function checkRepeat(FlattenException $exception)
    {
        $key  = md5($exception->getMessage() . ':' . $exception->getLine() . ':' . $exception->getFile());
        $file = $this->errorsDir . '/' . $key;
        $time = is_file($file) ? file_get_contents($file) : 0;
        if ($time < time()) {
            file_put_contents($file, time() + $this->repeatTimeout);

            return false;
        }

        return true;
    }

    /**
     * This allows to catch memory limit fatal errors.
     */
    protected static function reserveMemory()
    {
        self::$tmpBuffer = str_repeat('x', 1024 * 500);
    }

    protected static function freeMemory()
    {
        self::$tmpBuffer = '';
    }
}
