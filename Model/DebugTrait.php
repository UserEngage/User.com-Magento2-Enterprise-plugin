<?php

namespace Usercom\Analytics\Model;

trait DebugTrait
{
    protected bool $debug = false;
    protected \Psr\Log\LoggerInterface $logger;

    /**
     * @param string $message
     *
     * @return void
     */
    protected function log(string $message, $data): void
    {
        $this->logger->info($message, $data);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function debug(string $message, $data): void
    {
        if (defined('USER_COM_DEBUG')) {
            $this->debug = USER_COM_DEBUG;
        }
        if ( ! $this->debug) {
            return;
        }
        $this->logger->debug($message, $data);
    }
}
