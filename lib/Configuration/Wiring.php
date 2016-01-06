<?php
/**
 * Contains Wiring class.
 *
 * PHP version 5.5
 *
 * LICENSE:
 * This file is part of Yet Another Php Eve Api Library also know as Yapeal
 * which can be used to access the Eve Online API data and place it into a
 * database.
 * Copyright (C) 2014-2015 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A
 * copy of the GNU GPL should also be available in the GNU-GPL.md file.
 *
 * @copyright 2014-2015 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\Configuration;

use ArrayAccess;
use DomainException;
use FilePathNormalizer\FilePathNormalizerTrait;
use FilesystemIterator;
use InvalidArgumentException;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use PDO;
use RecursiveArrayIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Traversable;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFilter;
use Yapeal\Container\ContainerInterface;
use Yapeal\Exception\YapealDatabaseException;
use Yapeal\Exception\YapealException;
use Yapeal\Network\GuzzleNetworkRetriever;

/**
 * Class Wiring
 */
class Wiring
{
    use FilePathNormalizerTrait;
    /**
     * @param ContainerInterface $dic
     */
    public function __construct(ContainerInterface $dic)
    {
        $this->dic = $dic;
    }
    /**
     * @return self Fluent interface.
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws YapealException
     * @throws YapealDatabaseException
     */
    public function wireAll()
    {
        $this->wireConfig()
            ->wireError()
            ->wireEvent()
            ->wireLog()
            ->wireSql()
            ->wireXml()
            ->wireXsl()
            ->wireXsd()
            ->wireCache()
            ->wireNetwork()
            ->wireEveApi();
        return $this;
    }
    /**
     * @param array|string $settings
     *
     * @return array|string
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    protected function doSubs($settings)
    {
        if (is_string($settings)) {
            $settings = (array)$settings;
        }
        if (!is_array($settings)) {
            $mess = 'Settings MUST be a string or string array, but was given ' . gettype($settings);
            throw new InvalidArgumentException($mess);
        }
        if (0 === count($settings)) {
            return [];
        }
        $depth = 0;
        $maxDepth = 10;
        $regEx = '/(?<all>\{(?<name>Yapeal(?:\.\w+)+)\})/';
        $dic = $this->dic;
        do {
            $settings = preg_replace_callback(
                $regEx,
                function ($match) use ($settings, $dic) {
                    if (!empty($settings[$match['name']])) {
                        return $settings[$match['name']];
                    }
                    if (!empty($dic[$match['name']])) {
                        return $dic[$match['name']];
                    }
                    return $match['all'];
                },
                $settings,
                -1,
                $count
            );
            if (++$depth > $maxDepth) {
                $mess = 'Exceeded maximum depth, check for possible circular reference(s)';
                throw new DomainException($mess);
            }
            $lastError = preg_last_error();
            if (PREG_NO_ERROR !== $lastError) {
                $constants = array_flip(get_defined_constants(true)['pcre']);
                $lastError = $constants[$lastError];
                $mess = 'Received preg error ' . $lastError;
                throw new DomainException($mess);
            }
        } while ($count > 0);
        return $settings;
    }
    /**
     * @return array
     */
    protected function getFilteredEveApiSubscriberList()
    {
        $flags = FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::SKIP_DOTS
            | FilesystemIterator::UNIX_PATHS;
        $rdi = new RecursiveDirectoryIterator($this->dic['Yapeal.EveApi.dir']);
        $rdi->setFlags($flags);
        $rcfi = new RecursiveCallbackFilterIterator(
            $rdi, function ($current, $key, $rdi) {
            /**
             * @type \RecursiveDirectoryIterator $rdi
             */
            if ($rdi->hasChildren()) {
                return true;
            }
            $dirs = ['Account', 'Api', 'Char', 'Corp', 'Eve', 'Map', 'Server'];
            /**
             * @type \SplFileInfo $current
             */
            $dirExists = in_array(basename(dirname($key)), $dirs, true);
            return ($dirExists && $current->isFile() && 'php' === $current->getExtension());
        }
        );
        $rii = new RecursiveIteratorIterator(
            $rcfi, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $rii->setMaxDepth(3);
        $fpn = $this->getFpn();
        $files = [];
        foreach ($rii as $file) {
            $files[] = $fpn->normalizeFile($file->getPathname());
        }
        return $files;
    }
    /**
     * @param $configFile
     * @param $settings
     *
     * @return array|string
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws YapealException
     */
    protected function parserConfigFile($configFile, $settings)
    {
        if (!is_readable($configFile) || !is_file($configFile)) {
            return $settings;
        }
        try {
            /**
             * @type RecursiveIteratorIterator|Traversable $rItIt
             */
            $rItIt = new RecursiveIteratorIterator(
                new RecursiveArrayIterator(
                    (new Parser())->parse(
                        file_get_contents($configFile),
                        true,
                        false
                    )
                )
            );
        } catch (ParseException $exc) {
            $mess = sprintf(
                'Unable to parse the YAML configuration file %2$s.' . ' The error message was %1$s',
                $exc->getMessage(),
                $configFile
            );
            throw new YapealException($mess, 0, $exc);
        }
        foreach ($rItIt as $leafValue) {
            $keys = [];
            foreach (range(0, $rItIt->getDepth()) as $depth) {
                $keys[] = $rItIt->getSubIterator($depth)
                    ->key();
            }
            $settings[implode(
                '.',
                $keys
            )] = $leafValue;
        }
        return $this->doSubs($settings);
    }
    /**
     * @return self Fluent interface.
     */
    protected function wireCache()
    {
        $dic = $this->dic;
        if ('none' !== $dic['Yapeal.Cache.fileSystemMode']) {
            if (empty($dic['Yapeal.FileSystem.CachePreserver'])) {
                $dic['Yapeal.FileSystem.CachePreserver'] = function () use ($dic) {
                    return new $dic['Yapeal.Cache.Handlers.preserve']($dic['Yapeal.Cache.dir']);
                };
            }
            if (empty($dic['Yapeal.FileSystem.CacheRetriever'])) {
                $dic['Yapeal.FileSystem.CacheRetriever'] = function () use ($dic) {
                    return new $dic['Yapeal.Cache.Handlers.retrieve']($dic['Yapeal.Cache.dir']);
                };
            }
            /**
             * @type \Yapeal\Event\MediatorInterface $mediator
             */
            $mediator = $dic['Yapeal.Event.Mediator'];
            $mediator->addServiceSubscriberByEventList(
                'Yapeal.FileSystem.CachePreserver',
                ['Yapeal.EveApi.preserve' => ['preserveEveApi', 'last']]
            );
            $mediator->addServiceSubscriberByEventList(
                'Yapeal.FileSystem.CacheRetriever',
                ['Yapeal.EveApi.retrieve' => ['retrieveEveApi', 'last']]
            );
        }
        return $this;
    }
    /**
     * @return self Fluent interface.
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws YapealException
     */
    protected function wireConfig()
    {
        $dic = $this->dic;
        $fpn = $this->getFpn();
        $path = $fpn->normalizePath(dirname(dirname(__DIR__)));
        if (empty($dic['Yapeal.baseDir'])) {
            $dic['Yapeal.baseDir'] = $path;
        }
        if (empty($dic['Yapeal.libDir'])) {
            $dic['Yapeal.libDir'] = $path . 'lib/';
        }
        $configFiles = [
            $fpn->normalizeFile(__DIR__ . '/yapeal_defaults.yaml'),
            $fpn->normalizeFile($dic['Yapeal.baseDir'] . 'config/yapeal.yaml')
        ];
        if (empty($dic['Yapeal.vendorParentDir'])) {
            $vendorPos = strpos(
                $path,
                'vendor/'
            );
            if (false !== $vendorPos) {
                $dic['Yapeal.vendorParentDir'] = substr(
                    $path,
                    0,
                    $vendorPos
                );
                $configFiles[] = $fpn->normalizeFile($dic['Yapeal.vendorParentDir'] . 'config/yapeal.yaml');
            }
        } else {
            $configFiles[] = $fpn->normalizeFile($dic['Yapeal.vendorParentDir'] . 'config/yapeal.yaml');
        }
        $settings = [];
        // Process each file in turn so any substitutions are done in a more
        // consistent way.
        foreach ($configFiles as $configFile) {
            $settings = $this->parserConfigFile(
                $configFile,
                $settings
            );
        }
        if (0 !== count($settings)) {
            // Assure NOT overwriting already existing settings.
            foreach ($settings as $key => $value) {
                $dic[$key] = empty($dic[$key]) ? $value : $dic[$key];
            }
        }
        return $this;
    }
    /**
     * @return self Fluent interface.
     */
    protected function wireError()
    {
        if (!empty($this->dic['Yapeal.Error.Logger'])) {
            return $this;
        }
        $this->dic['Yapeal.Error.Logger'] = function ($dic) {
            /**
             * @type Logger $logger
             */
            $logger = new $dic['Yapeal.Error.class']($dic['Yapeal.Error.channel']);
            $group = [];
            if ('cli' === PHP_SAPI) {
                $group[] = new $dic['Yapeal.Error.Handlers.stream'](
                    'php://stderr', 100
                );
            }
            $group[] = new $dic['Yapeal.Error.Handlers.stream'](
                $dic['Yapeal.Error.dir'] . $dic['Yapeal.Error.fileName'], 100
            );
            $logger->pushHandler(
                new $dic['Yapeal.Error.Handlers.fingersCrossed'](
                    new $dic['Yapeal.Error.Handlers.group']($group),
                    (int)$dic['Yapeal.Error.threshold'],
                    (int)$dic['Yapeal.Error.bufferSize']
                )
            );
            /**
             * @type ErrorHandler $error
             */
            $error = $dic['Yapeal.Error.Handlers.error'];
            $error::register(
                $logger,
                [],
                (int)$dic['Yapeal.Error.threshold'],
                (int)$dic['Yapeal.Error.threshold']
            );
            return $error;
        };
        // Activate error logger now since it is needed to log any future fatal
        // errors or exceptions.
        $this->dic['Yapeal.Error.Logger'];
        return $this;
    }
    /**
     * @return self Fluent interface.
     */
    protected function wireEveApi()
    {
        /**
         * @type ContainerInterface|\ArrayObject $dic
         */
        $dic = $this->dic;
        /**
         * @type \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        $internal = $this->getFilteredEveApiSubscriberList();
        if (0 !== count($internal)) {
            $base = 'Yapeal.EveApi';
            /**
             * @type \SplFileInfo $subscriber
             */
            foreach ($internal as $subscriber) {
                $service = sprintf(
                    '%1$s.%2$s.%3$s',
                    $base,
                    basename(dirname($subscriber)),
                    basename($subscriber, '.php')
                );
                if (!array_key_exists($service, $dic)) {
                    $dic[$service] = function () use ($dic, $service, $mediator) {
                        $class = '\\' . str_replace('.', '\\', $service);
                        /**
                         * @type \Yapeal\EveApi\EveApiToolsTrait $callable
                         */
                        $callable = new $class();
                        return $callable->setCsq($dic['Yapeal.Sql.CommonQueries'])
                            ->setPdo($dic['Yapeal.Sql.Connection']);
                    };
                }
                $events = [$service . '.start' => ['startEveApi', 'last']];
                if (false === strpos($subscriber, 'Section')) {
                    $events[$service . '.preserve'] = ['preserveEveApi', 'last'];
                }
                $mediator->addServiceSubscriberByEventList($service, $events);
            }
        }
        if (empty($dic['Yapeal.EveApi.Creator'])) {
            $dic['Yapeal.EveApi.Creator'] = function () use ($dic) {
                $loader = new Twig_Loader_Filesystem($dic['Yapeal.EveApi.dir']);
                $twig = new Twig_Environment(
                    $loader, ['debug' => true, 'strict_variables' => true, 'autoescape' => false]
                );
                $filter = new Twig_SimpleFilter(
                    'ucFirst', function ($value) {
                    return ucfirst($value);
                }
                );
                $twig->addFilter($filter);
                $filter = new Twig_SimpleFilter(
                    'lcFirst', function ($value) {
                    return lcfirst($value);
                }
                );
                $twig->addFilter($filter);
                /**
                 * @type \Yapeal\EveApi\Creator $create
                 */
                $create = new $dic['Yapeal.EveApi.create']($twig, $dic['Yapeal.EveApi.dir']);
                $create->setOverwrite($dic['Yapeal.Create.overwrite']);
                return $create;
            };
            $mediator->addServiceSubscriberByEventList(
                'Yapeal.EveApi.Creator',
                ['Yapeal.EveApi.create' => ['createEveApi', 'last']]
            );
        }
        return $this;
    }
    /**
     * @return self Fluent interface.
     * @throws InvalidArgumentException
     */
    protected function wireEvent()
    {
        $dic = $this->dic;
        if (empty($dic['Yapeal.Event.EveApiEvent'])) {
            $dic['Yapeal.Event.EveApi'] = $dic->factory(
                function ($dic) {
                    return new $dic['Yapeal.Event.Factories.eveApi']();
                }
            );
        }
        if (empty($this->dic['Yapeal.Event.LogEvent'])) {
            $this->dic['Yapeal.Event.LogEvent'] = $this->dic->factory(
                function ($dic) {
                    return new $dic['Yapeal.Event.Factories.log'];
                }
            );
        }
        if (empty($dic['Yapeal.Event.Mediator'])) {
            $dic['Yapeal.Event.Mediator'] = function ($dic) {
                return new $dic['Yapeal.Event.mediator']($dic);
            };
        }
        return $this;
    }
    /**
     * @return self Fluent interface.
     */
    protected function wireLog()
    {
        $dic = $this->dic;
        $class = $dic['Yapeal.Log.class'];
        if (empty($dic['Yapeal.Log.Logger'])) {
            $dic['Yapeal.Log.Logger'] = function () use ($dic, $class) {
                $group = [];
                $lineFormatter = new LineFormatter;
                $lineFormatter->includeStacktraces();
                /**
                 * @type \Monolog\Handler\StreamHandler $handler
                 */
                if (PHP_SAPI === 'cli') {
                    $handler = new $dic['Yapeal.Log.Handlers.stream'](
                        'php://stderr', 100
                    );
                    $handler->setFormatter($lineFormatter);
                    $group[] = $handler;
                }
                $handler = new $dic['Yapeal.Log.Handlers.stream'](
                    $dic['Yapeal.Log.dir'] . $dic['Yapeal.Log.fileName'], 100
                );
                $handler->setFormatter($lineFormatter);
                $group[] = $handler;
                return new $class(
                    $dic['Yapeal.Log.channel'], [
                        new $dic['Yapeal.Log.Handlers.fingersCrossed'](
                            new $dic['Yapeal.Log.Handlers.group']($group),
                            (int)$dic['Yapeal.Log.threshold'],
                            (int)$dic['Yapeal.Log.bufferSize']
                        )
                    ]
                );
            };
        }
        /**
         * @type \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        $mediator->addServiceSubscriberByEventList(
            'Yapeal.Log.Logger',
            ['Yapeal.Log.log' => ['logEvent', 'last']]
        );
        return $this;
    }
    /**
     * @return self Fluent interface.
     */
    protected function wireNetwork()
    {
        $dic = $this->dic;
        if (empty($dic['Yapeal.Network.Client'])) {
            $dic['Yapeal.Network.Client'] = function ($dic) {
                $appComment = $dic['Yapeal.Network.appComment'];
                $appName = $dic['Yapeal.Network.appName'];
                $appVersion = $dic['Yapeal.Network.appVersion'];
                if ('' === $appName) {
                    $appComment = '';
                    $appVersion = '';
                }
                $userAgent = trim(
                    str_replace(
                        [
                            '{machineType}',
                            '{osName}',
                            '{osRelease}',
                            '{phpVersion}',
                            '{appComment}',
                            '{appName}',
                            '{appVersion}'
                        ],
                        [
                            php_uname('m'),
                            php_uname('s'),
                            php_uname('r'),
                            PHP_VERSION,
                            $appComment,
                            $appName,
                            $appVersion
                        ],
                        $dic['Yapeal.Network.userAgent']
                    )
                );
                $userAgent = ltrim(
                    $userAgent,
                    '/ '
                );
                $headers = [
                    'Accept'          => $dic['Yapeal.Network.Headers.Accept'],
                    'Accept-Charset'  => $dic['Yapeal.Network.Headers.Accept-Charset'],
                    'Accept-Encoding' => $dic['Yapeal.Network.Headers.Accept-Encoding'],
                    'Accept-Language' => $dic['Yapeal.Network.Headers.Accept-Language'],
                    'Connection'      => $dic['Yapeal.Network.Headers.Connection'],
                    'Keep-Alive'      => $dic['Yapeal.Network.Headers.Keep-Alive']
                ];
                // Clean up any extra spaces and EOL chars from Yaml.
                foreach ($headers as &$value) {
                    /** @noinspection ReferenceMismatchInspection */
                    $value = trim(
                        str_replace(
                            ' ',
                            '',
                            $value
                        )
                    );
                }
                unset($value);
                if ('' !== $userAgent) {
                    $headers['User-Agent'] = $userAgent;
                }
                $defaults = [
                    'base_uri'        => $dic['Yapeal.Network.baseUrl'],
                    'connect_timeout' => (int)$dic['Yapeal.Network.connect_timeout'],
                    'headers'         => $headers,
                    'timeout'         => (int)$dic['Yapeal.Network.timeout'],
                    'verify'          => $dic['Yapeal.Network.verify']
                ];
                return new $dic['Yapeal.Network.class']($defaults);
            };
        }
        if (empty($dic['Yapeal.Network.Retriever'])) {
            $dic['Yapeal.Network.Retriever'] = function ($dic) {
                return new GuzzleNetworkRetriever($dic['Yapeal.Network.Client']);
            };
        }
        /**
         * @type \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        $mediator->addServiceSubscriberByEventList(
            'Yapeal.Network.Retriever',
            ['Yapeal.EveApi.retrieve' => ['retrieveEveApi', 'last']]
        );
        return $this;
    }
    /**
     * @return self Fluent interface.
     * @throws InvalidArgumentException
     * @throws YapealDatabaseException
     */
    protected function wireSql()
    {
        $dic = $this->dic;
        if (empty($dic['Yapeal.Sql.CommonQueries'])) {
            $dic['Yapeal.Sql.CommonQueries'] = function ($dic) {
                return new $dic['Yapeal.Sql.sharedSql'](
                    $dic['Yapeal.Sql.database'], $dic['Yapeal.Sql.tablePrefix']
                );
            };
        }
        if (!empty($dic['Yapeal.Sql.Connection'])) {
            return $this;
        }
        if ('mysql' !== $dic['Yapeal.Sql.platform']) {
            $mess = 'Unknown platform, was given ' . $dic['Yapeal.Sql.platform'];
            throw new YapealDatabaseException($mess);
        }
        $dic['Yapeal.Sql.Connection'] = function ($dic) {
            $dsn = $dic['Yapeal.Sql.platform'] . ':host=' . $dic['Yapeal.Sql.hostName'] . ';charset=utf8';
            if (!empty($dic['Yapeal.Sql.port'])) {
                $dsn .= ';port=' . $dic['Yapeal.Sql.port'];
            }
            /**
             * @type PDO $database
             */
            $database = new $dic['Yapeal.Sql.class'](
                $dsn, $dic['Yapeal.Sql.userName'], $dic['Yapeal.Sql.password']
            );
            $database->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );
            $database->exec('SET SESSION SQL_MODE=\'ANSI,TRADITIONAL\'');
            $database->exec('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            $database->exec('SET SESSION TIME_ZONE=\'+00:00\'');
            $database->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci');
            return $database;
        };
        if (empty($dic['Yapeal.Sql.Creator'])) {
            $dic['Yapeal.Sql.Creator'] = $dic->factory(
                function ($dic) {
                    $loader = new Twig_Loader_Filesystem($dic['Yapeal.Sql.dir']);
                    $twig = new Twig_Environment(
                        $loader, ['debug' => true, 'strict_variables' => true, 'autoescape' => false]
                    );
                    $filter = new Twig_SimpleFilter(
                        'ucFirst', function ($value) {
                        return ucfirst($value);
                    }
                    );
                    $twig->addFilter($filter);
                    $filter = new Twig_SimpleFilter(
                        'lcFirst', function ($value) {
                        return lcfirst($value);
                    }
                    );
                    $twig->addFilter($filter);
                    /**
                     * @type \Yapeal\Sql\Creator $create
                     */
                    $create = new $dic['Yapeal.Sql.create']($twig, $dic['Yapeal.Sql.dir'], $dic['Yapeal.Sql.platform']);
                    $create->setOverwrite($dic['Yapeal.Create.overwrite']);
                    return $create;
                }
            );
        }
        /**
         * @type \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        $mediator->addServiceSubscriberByEventList(
            'Yapeal.Sql.Creator',
            ['Yapeal.EveApi.create' => ['createSql', 'last']]
        );
        return $this;
    }
    /**
     * Wire Xml section.
     *
     * @return self Fluent interface.
     * @throws InvalidArgumentException
     */
    protected function wireXml()
    {
        if (empty($this->dic['Yapeal.Xml.Data'])) {
            $this->dic['Yapeal.Xml.Data'] = $this->dic->factory(
                function ($dic) {
                    return new $dic['Yapeal.Xml.data']();
                }
            );
        }
        return $this;
    }
    /**
     * Wire Xsd section.
     *
     * @return self Fluent interface.
     * @throws InvalidArgumentException
     */
    protected function wireXsd()
    {
        $dic = $this->dic;
        if (empty($dic['Yapeal.Xsd.Creator'])) {
            $dic['Yapeal.Xsd.Creator'] = $dic->factory(
                function ($dic) {
                    $loader = new Twig_Loader_Filesystem($dic['Yapeal.Xsd.dir']);
                    $twig = new Twig_Environment(
                        $loader, ['debug' => true, 'strict_variables' => true, 'autoescape' => false]
                    );
                    $filter = new Twig_SimpleFilter(
                        'ucFirst', function ($value) {
                        return ucfirst($value);
                    }
                    );
                    $twig->addFilter($filter);
                    $filter = new Twig_SimpleFilter(
                        'lcFirst', function ($value) {
                        return lcfirst($value);
                    }
                    );
                    $twig->addFilter($filter);
                    /**
                     * @type \Yapeal\Xsd\Creator $create
                     */
                    $create = new $dic['Yapeal.Xsd.create']($twig, $dic['Yapeal.Xsd.dir']);
                    $create->setOverwrite($dic['Yapeal.Create.overwrite']);
                    return $create;
                }
            );
        }
        if (empty($dic['Yapeal.Xsd.Validator'])) {
            $dic['Yapeal.Xsd.Validator'] = $dic->factory(
                function ($dic) {
                    return new $dic['Yapeal.Xsd.validate']($dic['Yapeal.Xsd.dir']);
                }
            );
        }
        /**
         * @type \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        $mediator->addServiceSubscriberByEventList(
            'Yapeal.Xsd.Creator',
            ['Yapeal.EveApi.create' => ['createXsd', 'last']]
        );
        $mediator->addServiceSubscriberByEventList(
            'Yapeal.Xsd.Validator',
            ['Yapeal.EveApi.validate' => ['validateEveApi', 'last']]
        );
        return $this;
    }
    /**
     * Wire Xsl section.
     *
     * @return self Fluent interface.
     * @throws InvalidArgumentException
     */
    protected function wireXsl()
    {
        $dic = $this->dic;
        if (empty($dic['Yapeal.Xsl.Transformer'])) {
            $dic['Yapeal.Xsl.Transformer'] = $dic->factory(
                function ($dic) {
                    return new $dic['Yapeal.Xsl.transform']($dic['Yapeal.Xsl.dir']);
                }
            );
        }
        /**
         * @type \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        $mediator->addServiceSubscriberByEventList(
            'Yapeal.Xsl.Transformer',
            ['Yapeal.EveApi.transform' => ['transformEveApi', 'last']]
        );
        return $this;
    }
    /**
     * @type ContainerInterface|ArrayAccess $dic
     */
    protected $dic;
}
