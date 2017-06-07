<?php

namespace Highco\SlackErrorNotifierBundle\Formatter;

use Symfony\Component\Debug\Exception\FlattenException;

/**
 * Trait used for slack messages
 *
 * @package Highco\SlackErrorNotifierBundle\Formatter
 */
trait MessageTrait
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Add new attachement to slack message.
     *
     * @param array       $message    Slack message.
     * @param string      $title      Attachment title.
     * @param string|null $fallback   Attachment fallback (replaced with title if null).
     * @param string      $color      Attachment left bar color.
     * @param array       $fields     Attachment fields.
     * @param string|null $emptyTitle Title to use if fields are empty.
     */
    private function addAttachment(array &$message, $title, $fallback, $color, array $fields, $emptyTitle = null)
    {
        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($fields) && !empty($emptyTitle)) {
            $fields[] = [
                'title' => 'Empty',
                'value' => '_'.$emptyTitle.'_',
            ];
        }
        $attachment = [
            'fallback'  => $fallback ?: $title,
            'color'     => $color,
            'pretext'   => '',
            'title'     => $title,
            'mrkdwn'    => true,
            'mrkdwn_in' => ['text', 'pretext', 'fields'],
            'footer'    => 'Slack error notifier',
            'ts'        => $this->timestamp,
        ];
        if (count($fields) > 0) {
            $attachment['fields']     = $fields;
            $message['attachments'][] = $attachment;
        }
    }

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

        $fields = [
            [
                'title' => 'Message',
                'value' => $text,
            ],
            [
                'title' => 'Environment',
                'value' => $this->env,
                'short' => true,
            ],
            [
                'title' => 'Code',
                'value' => $code,
                'short' => true,
            ],
            [
                'title' => 'File',
                'value' => $file,
                'short' => true,
            ],
            [
                'title' => 'Line',
                'value' => $line,
                'short' => true,
            ],
        ];
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
     * Format URI.
     *
     * @return array|null Slack attachments fields
     */
    private function formatUri()
    {
        if ($this->request) {
            return [
                'title' => 'Uri',
                'value' => '<'.$this->request->getUri().'>',
            ];
        }

        return null;
    }

    /**
     * Format command name.
     *
     * @return array|null Slack attachments fields
     */
    private function formatCommandName()
    {
        if ($this->command) {
            return [
                'title' => 'Command',
                'value' => $this->command->getName(),
            ];
        }

        return null;
    }
}
