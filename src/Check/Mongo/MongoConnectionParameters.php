<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Mongo;

/**
 * Model that stores connection parameters for MongoDB.
 */
class MongoConnectionParameters
{
    /**
     * @var string
     */
    private string $protocol;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var string
     */
    private string $username;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var string
     */
    private string $db;

    /**
     * @var array<string,int|bool|string>
     */
    private array $options;

    /**
     * Constructor.
     *
     * @param string               $protocol
     * @param string               $host
     * @param int                  $port
     * @param string               $username
     * @param string               $password
     * @param string               $db
     * @param array<string, mixed> $options
     */
    public function __construct(string $protocol, string $host, int $port, string $username, string $password, string $db, array $options = [])
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->options = $options;
    }

    /**
     * Format DSN for connect
     *
     * @return string
     */
    public function getDsn(): string
    {
        $userPass = \sprintf('%s:%s@', $this->username, $this->password);

        return \sprintf(
            '%s://%s%s:%s%s',
            $this->protocol,
            $userPass,
            $this->host,
            $this->port,
            $this->formatOptions(),
        );
    }

    /**
     * Get protocol
     *
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get port
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDb(): string
    {
        return $this->db;
    }

    /**
     * Get options
     *
     * @return array<string,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Format options to string
     *
     * @return string
     */
    private function formatOptions(): string
    {
        if (!\count($this->options)) {
            return '';
        }

        $options = \array_map(static function ($v) {
            if (\is_bool($v)) {
                return $v ? 'true' : 'false';
            }

            return $v;
        }, $this->options);

        return '/?'.\http_build_query($options);
    }
}
