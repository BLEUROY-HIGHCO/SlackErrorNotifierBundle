<?php

namespace Highco\SlackErrorNotifierBundle\Formatter;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class MessageToSlackExceptionFormatter extends AbstractExceptionFormatter
{
    use MessageTrait;

    /**
     * Format an exception.
     *
     * @param FlattenException $exception Exception to format.
     *
     * @return array
     */
    public function formatException(FlattenException $exception)
    {
        $now             = new \DateTime();
        $this->timestamp = $now->getTimestamp();
        $fullClassName   = $exception->getClass();
        $className       = preg_replace('/^.*\\\\([^\\\\]+)$/', '$1', $fullClassName);

        if ($this->request) {
            $subject = '[' . $this->request->headers->get('host') . '] Error ' . $exception->getStatusCode() . ': ' . $className;
        } elseif ($this->command) {
            $subject = '[' . $this->command->getName() . '] Error ' . $exception->getStatusCode() . ': ' . $className;
        } else {
            $subject = 'Error ' . $exception->getStatusCode() . ': ' . $className;
        }

        $message = array(
            'channel'     => '#' . $this->channel,
            'text'        => $subject,
            'attachments' => array(),
            'username'    => 'Sf2',
        );

        $this->addAttachment($message, $fullClassName, $className, 'danger', $this->formatMainInformation($exception));
        $this->addAttachment($message, 'Exception full trace', 'Full trace', 'warning', $this->formatFullTrace($exception));
        $this->addAttachment($message, 'Command options', null, '#439FE0', $this->formatCommandOptions());
        $this->addAttachment($message, 'Command arguments', null, '#439FE0', $this->formatCommandArguments());
        $this->addAttachment($message, 'Scope variables', null, '#D343E0', $this->formatContext());
        $this->formatRequest($message);

        return array(
            'message' => $message
        );
    }

    //<editor-fold desc="Sub-formatters">
    /**
     * Format main information.
     *
     * @param FlattenException $exception Exception to format.
     *
     * @return array Slack attachments fields.
     */
    private function formatMainInformation(FlattenException $exception)
    {
        $code = $exception->getCode();
        $text = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();

        $fields = array(
            array(
                'title' => 'Message',
                'value' => $text,
            ),
            array(
                'title' => 'Environment',
                'value' => $this->env,
                'short' => true,
            ),
            array(
                'title' => 'Code',
                'value' => $code,
                'short' => true,
            ),
            array(
                'title' => 'File',
                'value' => $file,
                'short' => true,
            ),
            array(
                'title' => 'Line',
                'value' => $line,
                'short' => true,
            ),
        );
        $uri    = $this->formatUri();
        if (null !== $uri) {
            $fields[] = $uri;
        }

        $commandName = $this->formatCommandName();
        if (null !== $commandName) {
            $fields[] = $commandName;
        }

        return $fields;
    }

    /**
     * Format exception full trace.
     *
     * @param FlattenException $exception Exception to format.
     *
     * @return array Slack attachments fields.
     */
    private function formatFullTrace(FlattenException $exception)
    {
        $fullTrace    = array();
        $paddingLines = $this->firstClassLinesBeforeAfter;
        foreach ($exception->toArray() as $exc) {
            if (is_array($exc['trace']) || $exc['trace'] instanceof \ArrayAccess) {
                foreach ($exc['trace'] as $position => $trace) {
                    $title = $position . '. ';
                    $value = '';
                    if (!empty($trace['class'])) {
                        $title .= ' at ' . $trace['class'] . ' ' . $trace['type'] . ' ' . $trace['function'] . "\n";
                        if (isset($trace['args'])) {
                            if (is_array($trace['args']) || $trace['args'] instanceof \Traversable) {
                                $value .= $this->formatArgs($trace['args']) . "\n";
                            } else {
                                $value .= $trace['args'] . "\n";
                            }
                        }
                    } else {
                        $title .= $trace['function'];
                    }
                    if (isset($trace['file'], $trace['line'])) {
                        $title .= ' in ' . $this->formatFile($trace['file'], $trace['line']) . "\n";
                        $value .= $this->fileExcerpt($trace['file'], $trace['line'], $paddingLines);
                        $paddingLines = $this->followingClassLinesBeforeAfter;
                    }
                    $fullTrace[] = array(
                        'title' => $title,
                        'value' => $value,
                    );
                }
            }
        }

        return $fullTrace;
    }

    /**
     * Format command options.
     *
     * @return array Slack attachments fields
     */
    private function formatCommandOptions()
    {
        $fields = array();
        if (null !== $this->commandInput) {
            foreach ($this->commandInput->getOptions() as $key => $value) {
                $fields[] = array(
                    'title' => 'Option ' . $key,
                    'value' => $value,
                    'short' => true,
                );
            }
        }

        return $fields;
    }

    /**
     * Format command arguments.
     *
     * @return array Slack attachments fields
     */
    private function formatCommandArguments()
    {
        $fields = array();
        if (null !== $this->commandInput) {
            foreach ($this->commandInput->getArguments() as $key => $value) {
                $fields[] = array(
                    'title' => 'Argument ' . $key,
                    'value' => $value,
                    'short' => true,
                );
            }
        }

        return $fields;
    }

    /**
     * Format execution context.
     *
     * @return array Slack attachments fields
     */
    private function formatContext()
    {
        $fields = array();
        if (is_array($this->context)) {
            foreach ($this->context as $key => $value) {
                $fields[] = array(
                    'title' => $key,
                    'value' => $value,
                    'short' => 1,
                );
            }
        }

        return $fields;
    }

    /**
     * Format request.
     *
     * @param array $message Stack message.
     */
    private function formatRequest(array &$message)
    {
        if (null !== $this->request) {
            if ($this->includeGetParameters) {
                $this->addAttachment($message, 'Request GET parameters', null, '#E08443', $this->formatBag($this->request->query), 'No GET parameters');
            }
            if ($this->includePostParameters) {
                $this->addAttachment($message, 'Request POST parameters', null, '#E08443', $this->formatBag($this->request->request), 'No POST parameters');
            }
            if ($this->includeRequestAttributes) {
                $this->addAttachment($message, 'Request Attributes', null, null, $this->formatBag($this->request->attributes), 'No request attributes');
            }
            if ($this->includeRequestHeaders) {
                $this->addAttachment($message, 'Request headers', null, null, $this->formatBag($this->request->headers), 'No request headers');
            }
            if ($this->includeRequestCookies) {
                $this->addAttachment($message, 'Request cookies', null, null, $this->formatBag($this->request->cookies), 'No request cookies');
            }
            if ($this->includeServerParameters) {
                $this->addAttachment($message, 'Server parameters', null, null, $this->formatBag($this->request->server), 'No server parameters');
            }
            if ($this->includeSessionAttributes) {
                $this->addAttachment($message, 'Session parameters', null, null, $this->formatSession(), 'No session attributes');
            }
        }
    }

    /**
     * Format attributes bag.
     *
     * @param ParameterBag|HeaderBag $bag Bag to format.
     *
     * @return array Slack attachments fields
     */
    private function formatBag($bag)
    {
        $fields = array();
        if (count($bag->all()) > 0) {
            $keys = $bag->keys();
            sort($keys);
            foreach ($keys as $key) {
                $fields[] = array(
                    'title' => $key,
                    'value' => $bag->get($key),
                    'short' => 1,
                );
            }
        }

        return $fields;
    }

    /**
     * Format session attributes.
     *
     * @return array Slack attachments fields
     */
    private function formatSession()
    {
        $fields = array();
        if (count($this->request->getSession()->all())) {
            $attributes = $this->request->getSession()->all();
            $keys       = array_keys($attributes);
            sort($keys);
            foreach ($keys as $key) {
                $fields[] = array(
                    'title' => $key,
                    'value' => $attributes[$key],
                    'short' => true,
                );
            }
        }

        return $fields;
    }

    //</editor-fold>

    //<editor-fold desc="Code and file formatters">
    /**
     * Formats an array as a string.
     *
     * @param array $args The argument array
     *
     * @return string
     */
    private function formatArgs($args)
    {
        $result = '';
        foreach ($args as $key => $item) {
            if ('object' === $item[0]) {
                $formattedValue = sprintf('*object* `(%s)` ', $item[1]);
            } elseif ('array' === $item[0]) {
                $formattedValue = sprintf('*array* `(%s)`', is_array($item[1]) ? $this->formatArgs($item[1]) : $item[1]);
            } elseif ('string' === $item[0]) {
                $formattedValue = sprintf("`'%s'`", $item[1]);
            } elseif ('null' === $item[0]) {
                $formattedValue = '`null` ';
            } elseif ('boolean' === $item[0]) {
                $formattedValue = '`' . strtolower(var_export($item[1], true)) . '` ';
            } elseif ('resource' === $item[0]) {
                $formattedValue = '`resource` ';
            } else {
                $formattedValue = str_replace("\n", '', var_export((string)$item[1], true));
            }

            $result .= '>' . (is_int($key) ? $formattedValue : sprintf("'%s' => %s", $key, $formattedValue)) . "  \n";
        }

        return $result;
    }

    /**
     * Returns an excerpt of a code file around the given line number.
     *
     * @param string $file         A file path
     * @param int    $line         The selected line number
     * @param int    $paddingLines Lines show before and after.
     *
     * @return string An markdown string
     */
    private function fileExcerpt($file, $line, $paddingLines = 3)
    {
        if (is_readable($file)) {
            $code    = @file_get_contents($file, true);
            $content = preg_split('/$\R?^/m', $code);

            $lines = "```\n";
            for ($i = max($line - $paddingLines, 1), $max = min($line + $paddingLines, count($content)); $i <= $max; ++$i) {
                $lines .= $content[$i - 1] . "  \n";
            }

            return $lines . "```\n";
        }

        return null;
    }

    /**
     * Formats a file path.
     *
     * @param string $file An absolute file path
     * @param int    $line The line number
     * @param string $text Use this text for the link rather than the file path
     *
     * @return string
     */
    private function formatFile($file, $line, $text = null)
    {
        $file = trim($file);

        if (null === $text) {
            $text = str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (0 === strpos($text, $this->rootDir)) {
                $text = substr($text, strlen($this->rootDir));
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $text = explode(DIRECTORY_SEPARATOR, $text, 2);
                /** @noinspection UnSafeIsSetOverArrayInspection */
                $text = sprintf('%s%s', $text[0], isset($text[1]) ? DIRECTORY_SEPARATOR . $text[1] : '');
            }
        }

        return sprintf('%s at line %d', $text, $line);
    }
    //</editor-fold>
}
