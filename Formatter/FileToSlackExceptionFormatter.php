<?php

namespace Highco\SlackErrorNotifierBundle\Formatter;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class FileToSlackExceptionFormatter extends AbstractExceptionFormatter
{
    use MessageTrait;

    /**
     * Twig service.
     *
     * @var Environment
     */
    private $twig;

    /**
     * Set twig service.
     *
     * @param Environment $twig Twig environement.
     *
     * @return self
     */
    public function setTwig(Environment $twig)
    {
        $this->twig = $twig;

        return $this;
    }

    /**
     * Format Exception.
     *
     * @param FlattenException $exception Exception.
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

        return array(
            'message' => $message,
            'exception' => $this->formatExceptionAsHTML($exception),
        );
    }

    /**
     * Format exception as html (using twig templates)
     *
     * @param FlattenException $exception Exception
     *
     * @return string
     */
    protected function formatExceptionAsHTML(FlattenException $exception)
    {
        $code = $exception->getStatusCode();
        $html = $this->twig->render('@Twig/Exception/exception_full.html.twig',
            array(
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'logger' => null,
                'currentContent' => '',
            )
        );

        return $html;
    }
}
