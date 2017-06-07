<?php

namespace Highco\SlackErrorNotifierBundle\Formatter;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractExceptionFormatter implements ExceptionFormatterInterface
{

    //<editor-fold desc="Members">
    /**
     * App root dir.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * Request where error occurs.
     *
     * @var Request
     */
    protected $request;

    /**
     * Execution context.
     *
     * @var array
     */
    protected $context;

    /**
     * Request where error occurs.
     *
     * @var Command
     */
    protected $command;

    /**
     * Command input.
     *
     * @var InputInterface
     */
    protected $commandInput;

    /**
     * Symfony environment.
     *
     * @var string
     */
    protected $env;

    /**
     * Slack channel to post.
     *
     * @var string
     */
    protected $channel;

    /**
     * Include GET parameters
     *
     * @var bool
     */
    protected $includeGetParameters;

    /**
     * Include POST parameters.
     *
     * @var bool
     */
    protected $includePostParameters;

    /**
     * Include request attributes.
     *
     * @var bool
     */
    protected $includeRequestAttributes;

    /**
     * Include request cookies.
     *
     * @var bool
     */
    protected $includeRequestCookies;

    /**
     * Include request headers.
     *
     * @var bool
     */
    protected $includeRequestHeaders;

    /**
     * Include server parameters.
     *
     * @var bool
     */
    protected $includeServerParameters;

    /**
     * Include session attributes.
     *
     * @var bool
     */
    protected $includeSessionAttributes;

    /**
     * First class lines included before and after code.
     *
     * @var int
     */
    protected $firstClassLinesBeforeAfter;

    /**
     * Following class lines included before and after code.
     *
     * @var int
     */
    protected $followingClassLinesBeforeAfter;

    /**
     * Format timestamp
     *
     * @var int
     */
    protected $timestamp;
    //</editor-fold>

    /**
     * Constructor.
     *
     * @param string $rootDir App root dir.
     * @param string $env     Symfony environment.
     * @param array  $config  Bundle config
     */
    public function __construct($rootDir, $env, $config)
    {
        $this->rootDir                        = $rootDir;
        $this->env                            = $env;
        $this->channel                        = $config['channel'];
        $this->includeGetParameters           = $config['formatter']['includeGetParameters'];
        $this->includePostParameters          = $config['formatter']['includePostParameters'];
        $this->includeRequestAttributes       = $config['formatter']['includeRequestAttributes'];
        $this->includeRequestCookies          = $config['formatter']['includeRequestCookies'];
        $this->includeRequestHeaders          = $config['formatter']['includeRequestHeaders'];
        $this->includeServerParameters        = $config['formatter']['includeServerParameters'];
        $this->includeSessionAttributes       = $config['formatter']['includeSessionAttributes'];
        $this->firstClassLinesBeforeAfter     = $config['formatter']['firstClassLinesBeforeAfter'];
        $this->followingClassLinesBeforeAfter = $config['formatter']['followingClassLinesBeforeAfter'];
    }

    /**
     * Set HTTP request.
     *
     * @param Request $request HTTP request.
     *
     * @return static
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Set execution context.
     *
     * @param array|null $context Execution context
     *
     * @return static
     */
    public function setContext(array $context = null)
    {
        $this->context = $context;
    }

    /**
     * Set executed command.
     *
     * @param Command $command Executed command.
     *
     * @return static
     */
    public function setCommand(Command $command = null)
    {
        $this->command = $command;
    }

    /**
     * Set executed command input.
     *
     * @param InputInterface $commandInput Executed command input.
     *
     * @return static
     */
    public function setCommandInput(InputInterface $commandInput = null)
    {
        $this->commandInput = $commandInput;
    }
}
