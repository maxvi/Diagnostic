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

namespace FiveLab\Component\Diagnostic\Tests\Check\Mongo;

use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoCollectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMongoTestCase;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;

class MongoCollectionCheckTest extends AbstractMongoTestCase
{
    /**
     * @var Manager
     */
    private Manager $mongoManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->connectionParametersProvided()) {
            self::markTestSkipped('MongoDB is not configured.');
        }

        $jsonSchema = [
            'required' => [ 'a', 'b', 'c' ],
            'properties' => [
                'a' => [ 'bsonType' => 'string' ],
                'b' => [ 'bsonType' => 'string' ],
                'c' => [ 'bsonType' => 'string' ],
            ],
        ];

        $createCollection = new Command(
            [
                'create' => $this->getCollection(),
                'validator' => [
                    '$jsonSchema' => $jsonSchema,
                ],
                'validationLevel' => 'strict',
                'validationAction' => 'error',
            ],
        );

        try {
            $this->mongoManager = new Manager($this->getConnectionParameters()->getDsn());
            $this->mongoManager->executeCommand($this->getDb(), $createCollection);
        } catch (\Exception $e) {
            self::throwException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        try {
            $this->mongoManager->executeCommand($this->getDb(), new Command(['drop' => $this->getCollection()]));
        } catch (\Exception $e) {
            self::throwException($e);
        }
    }

    /**
     * @test
     */
    public function testSuccessfulCheck(): void
    {
        $check = new MongoCollectionCheck(
            $this->getConnectionParameters(),
            $this->getCollection(),
            $this->getExpectedSettings()
        );

        $result = $check->check();

        self::assertEquals(new Success('Successful MongoDB collection check.'), $result);
    }

    /**
     * @test
     */
    public function testFailedCheckConnectionFailed(): void
    {
        $invalidHost = $this->getHost().'_some';

        $connectionParameters = new MongoConnectionParameters(
            $this->getProtocol(),
            $invalidHost,
            $this->getPort(),
            $this->getUsername(),
            $this->getPassword(),
            $this->getDb(),
        );

        $check = new MongoCollectionCheck(
            $connectionParameters,
            $this->getCollection(),
            $this->getExpectedSettings()
        );

        $result = $check->check();

        $msg = \sprintf('MongoDB collection check failed: No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve \'%s\'].', $invalidHost);

        self::assertEquals(new Failure($msg), $result);
    }

    /**
     * @test
     */
    public function testFailedCheckCollectionNotFound(): void
    {
        $wrongCollection = 'wrong_collection';

        $check = new MongoCollectionCheck(
            $this->getConnectionParameters(),
            $wrongCollection,
            $this->getExpectedSettings()
        );

        $result = $check->check();

        $msg = \sprintf(
            'collection \'%s\' not found in db \'%s\'.',
            $wrongCollection,
            $this->getDb(),
        );

        self::assertEquals(new Failure(\sprintf('MongoDB collection check failed: %s', $msg)), $result);
    }

    /**
     * @test
     */
    public function testFailedCheckSettingsDoNotEqual(): void
    {
        $setting = 'options.validator.$jsonSchema';
        $expectedSettings = [
            $setting => [
                'a',
                'b',
                'c',
            ],
        ];

        $check = new MongoCollectionCheck(
            $this->getConnectionParameters(),
            $this->getCollection(),
            $expectedSettings
        );

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('MongoDB collection check failed: the actual setting \'%s\' is different than expected.', $setting)), $result);
    }

    /**
     * @test
     */
    public function testGetExtraParameters(): void
    {
        $check = new MongoCollectionCheck(
            $this->getConnectionParameters(),
            'test',
            $this->getExpectedSettings()
        );

        $check->check();

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'protocol' => 'mongodb',
            'host' => 'diagnostic-mongo',
            'port' => 27017,
            'user' => 'root',
            'pass' => '***',
            'db' => 'diagnostic',
            'options' => [],
            'collection' => 'test',
            'expected settings' => [
                'options.validator.$jsonSchema' => [
                    'required' => [
                        'a',
                        'b',
                        'c',
                    ],
                    'properties' => [
                        'a' => [
                            'bsonType' => 'string',
                        ],
                        'b' => [
                            'bsonType' => 'string',
                        ],
                        'c' => [
                            'bsonType' => 'string',
                        ],
                    ],
                ],
            ],
            'actual settings' => [
                'name' => 'test',
                'type' => 'collection',
                'options' => [
                    'validator' => [
                        '$jsonSchema' => [
                            'required' => [
                                'a',
                                'b',
                                'c',
                            ],
                            'properties' => [
                                'a' => [
                                    'bsonType' => 'string',
                                ],
                                'b' => [
                                    'bsonType' => 'string',
                                ],
                                'c' => [
                                    'bsonType' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'validationLevel' => 'strict',
                    'validationAction' => 'error',
                ],
            ],
        ], $parameters);
    }
}
