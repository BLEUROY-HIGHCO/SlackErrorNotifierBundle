<?php

namespace Highco\SlackErrorNotifierBundle;

/**
 * Highco error handler.
 */
class ErrorHandler
{
    private $notifier;

    /**
     * Report silent errors.
     *
     * @var bool
     */
    private $reportSilent = false;

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
     * Ignored php errors.
     *
     * @var array
     */
    private $ignoredPhpErrors;

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
        }
    }

    /**
     * Convert the error code to a readable format
     *
     * @param int $errorNo
     *
     * @return string
     */
    protected function getErrorString($errorNo)
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

        return array_key_exists($errorNo, $errorStrings) ? $errorStrings[$errorNo] : 'Unknown PHP error';
    }
}
