<?php

namespace Highco\SlackErrorNotifierBundle\Formatter;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;

interface ExceptionFormatterInterface
{
    /**
     * Format Exception.
     *
     * @param FlattenException $exception Exception.
     *
     * @return array
     */
    public function formatException(FlattenException $exception);

    /**
     * Set HTTP request.
     *
     * @param Request $request HTTP request.
     *
     * @return static
     */
    public function setRequest(Request $request = null);

    /**
     * Set execution context.
     *
     * @param array|null $context Execution context
     *
     * @return static
     */
    public function setContext(array $context = null);

    /**
     * Set executed command.
     *
     * @param Command $command Executed command.
     *
     * @return static
     */
    public function setCommand(Command $command = null);

    /**
     * Set executed command input.
     *
     * @param InputInterface $commandInput Executed command input.
     *
     * @return static
     */
    public function setCommandInput(InputInterface $commandInput = null);

}
