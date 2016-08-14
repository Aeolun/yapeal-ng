<?php
declare(strict_types = 1);
/**
 * Contains SchemaUpdater class.
 *
 * PHP version 7.0+
 *
 * LICENSE:
 * This file is part of Yet Another Php Eve Api Library also know as Yapeal
 * which can be used to access the Eve Online API data and place it into a
 * database.
 * Copyright (C) 2014-2016 Michael Cummings
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
 * <http://spdx.org/licenses/LGPL-3.0.html>.
 *
 * You should be able to find a copy of this license in the COPYING-LESSER.md
 * file. A copy of the GNU GPL should also be available in the COPYING.md file.
 *
 * @copyright 2014-2016 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\Cli\Schema;

use Symfony\Component\Console\Output\OutputInterface;
use Yapeal\Container\ContainerInterface;
use Yapeal\Event\YEMAwareTrait;
use Yapeal\Exception\YapealDatabaseException;
use Yapeal\Log\Logger;

/**
 * Class SchemaUpdater
 */
class SchemaUpdater extends AbstractSchemaCommon
{
    use YEMAwareTrait;
    /**
     * @param string             $name
     * @param ContainerInterface $dic
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(string $name, ContainerInterface $dic)
    {
        $this->setDescription('Retrieves SQL from files and updates schema');
        $this->setName($name);
        $this->setDic($dic);
        parent::__construct($name);
    }
    /**
     * @param OutputInterface $output
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Yapeal\Exception\YapealDatabaseException
     */
    protected function addDatabaseProcedure(OutputInterface $output)
    {
        $name = 'DatabaseUpdater::addDatabaseProcedure';
        $csq = $this->getCsq();
        $this->executeSqlStatements($csq->getDropAddOrModifyColumnProcedure()
            . PHP_EOL
            . $csq->getCreateAddOrModifyColumnProcedure(),
            $name,
            $output);
        $output->writeln('');
    }
    /**
     * Configures the current command.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $help = <<<'HELP'
The <info>%command.full_name%</info> command is used to initialize (create) a new
 schema and tables to be used by Yapeal-ng. If you already have a
 config/yapeal.yaml file setup you can use the following:

    <info>php %command.full_name%</info>

EXAMPLES:
To use a configuration file in a different location:
    <info>%command.name% -c /my/very/special/config.yaml</info>

<info>NOTE:</info>
Only the Sql section of the configuration file will be used.

You can also use the command before setting up a configuration file like so:
    <info>%command.name% -o "localhost" -d "yapeal" -u "YapealUser" -p "secret"

HELP;
        $this->addOptions($help);
        $this->setAliases(['Database:Update']);
    }
    /**
     * @param OutputInterface $output
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Yapeal\Exception\YapealDatabaseException
     */
    protected function dropDatabaseProcedure(OutputInterface $output)
    {
        $name = 'DatabaseUpdater::dropDatabaseProcedure';
        $this->executeSqlStatements($this->getCsq()
            ->getDropAddOrModifyColumnProcedure(),
            $name,
            $output);
    }
    /**
     * @param OutputInterface $output
     *
     * @return string
     * @throws \LogicException
     * @throws \Yapeal\Exception\YapealDatabaseException
     */
    protected function getLatestDatabaseVersion(OutputInterface $output): string
    {
        $sql = $this->getCsq()
            ->getUtilLatestDatabaseVersion();
        try {
            $result = $this->getPdo()
                ->query($sql, \PDO::FETCH_NUM);
            $version = sprintf('%018.3F', $result->fetchColumn());
            $result->closeCursor();
        } catch (\PDOException $exc) {
            $version = '19700101000001.000';
            $mess = sprintf('<error>Could NOT get latest database version using default %1$s</error>', $version);
            $output->writeln([$sql, $mess]);
            $mess = sprintf('<info>Error message from database connection was %s</info>',
                $exc->getMessage());
            $output->writeln($mess);
        }
        return $version;
    }
    /**
     * @return array
     * @throws \LogicException
     */
    protected function getReplacements()
    {
        $replacements = parent::getReplacements();
        $replacements['$$'] = ';';
        return $replacements;
    }
    /**
     * @param OutputInterface $output
     *
     * @return string[]
     * @throws \LogicException
     */
    protected function getUpdateFileList(OutputInterface $output): array
    {
        $fileNames = [];
        $path = $this->getDic()['Yapeal.Sql.dir'] . 'updates/';
        if (!is_readable($path) || !is_dir($path)) {
            $mess = sprintf('<info>Could NOT access update directory %1$s</info>',
                $path);
            $output->writeln($mess);
            return $fileNames;
        }
        foreach (new \DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }
            if ('sql' !== $fileInfo->getExtension()) {
                continue;
            }
            $fileNames[] = $this->getFpn()
                ->normalizeFile($fileInfo->getPathname());
        }
        asort($fileNames);
        return $fileNames;
    }
    /**
     * @param OutputInterface $output
     *
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Yapeal\Exception\YapealDatabaseException
     */
    protected function processSql(OutputInterface $output)
    {
        if (!$this->hasYem()) {
            $this->setYem($this->getDic()['Yapeal.Event.Mediator']);
        }
        $yem = $this->getYem();
        $this->addDatabaseProcedure($output);
        foreach ($this->getUpdateFileList($output) as $fileName) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $latestVersion = $this->getLatestDatabaseVersion($output);
            if (!is_file($fileName)) {
                if ($output::VERBOSITY_QUIET !== $output->getVerbosity()) {
                    $mess = sprintf('<info>Could NOT find SQL file %1$s</info>', $fileName);
                    $yem->triggerLogEvent('Yapeal.Log.log', Logger::INFO, strip_tags($mess));
                    $output->writeln($mess);
                }
                continue;
            }
            $updateVersion = basename($fileName, '.sql');
            if ($updateVersion <= $latestVersion) {
                if ($output::VERBOSITY_QUIET !== $output->getVerbosity()) {
                    $mess = sprintf('<info>Skipping SQL file %1$s since its <= the latest database version %2$s</info>',
                        basename($fileName),
                        $latestVersion);
                    $yem->triggerLogEvent('Yapeal.Log.log', Logger::INFO, strip_tags($mess));
                    $output->writeln($mess);
                }
                continue;
            }
            $sqlStatements = file_get_contents($fileName);
            if (false === $sqlStatements) {
                if ($output::VERBOSITY_QUIET !== $output->getVerbosity()) {
                    $mess = sprintf('<error>Could NOT get contents of SQL file %1$s</error>',
                        $fileName);
                    $yem->triggerLogEvent('Yapeal.Log.log', Logger::INFO, strip_tags($mess));
                    $output->writeln($mess);
                }
                continue;
            }
            $this->executeSqlStatements($sqlStatements, $fileName, $output);
            $this->updateDatabaseVersion($updateVersion);
        }
        $this->dropDatabaseProcedure($output);
    }
    /**
     * @param string $updateVersion
     *
     * @return SchemaUpdater
     * @throws \LogicException
     * @throws \Yapeal\Exception\YapealDatabaseException
     */
    protected function updateDatabaseVersion(string $updateVersion)
    {
        $pdo = $this->getPdo();
        $sql = $this->getCsq()
            ->getUtilLatestDatabaseVersionUpdate();
        try {
            $pdo->beginTransaction();
            $pdo->prepare($sql)
                ->execute([$updateVersion]);
            $pdo->commit();
        } catch (\PDOException $exc) {
            $mess = $sql . PHP_EOL;
            $mess .= sprintf('Database error message was %s', $exc->getMessage()) . PHP_EOL;
            $mess .= sprintf('Database "version" update failed for %1$s',
                $updateVersion);
            if ($this->getPdo()
                ->inTransaction()
            ) {
                $this->getPdo()
                    ->rollBack();
            }
            throw new YapealDatabaseException($mess, 2);
        }
        return $this;
    }
}