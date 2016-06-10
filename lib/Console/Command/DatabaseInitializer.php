<?php
/**
 * Contains DatabaseInitializer class.
 *
 * PHP version 5.5
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
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A
 * copy of the GNU GPL should also be available in the GNU-GPL.md file.
 *
 * @copyright 2014-2016 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\Console\Command;

use DirectoryIterator;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Output\OutputInterface;
use Yapeal\Container\ContainerInterface;
use Yapeal\Exception\YapealConsoleException;
use Yapeal\Exception\YapealDatabaseException;

/**
 * Class DatabaseInitializer
 */
class DatabaseInitializer extends AbstractDatabaseCommon
{
    /**
     * @param string|null        $name
     * @param ContainerInterface $dic
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct($name, ContainerInterface $dic)
    {
        $this->setDescription(
            'Retrieves SQL from files and initializes database'
        );
        $this->setName($name);
        $this->setDic($dic);
        parent::__construct($name);
    }
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->addOptions();
        $help = <<<'HELP'
The <info>%command.full_name%</info> command is used to initialize (create) a new
 database and tables to be used by Yapeal. If you already have a
 config/yapeal.yaml file setup you can use the following:

    <info>php %command.full_name%</info>

EXAMPLES:
To use a configuration file in a different location:
    <info>%command.name% -c /my/very/special/config.yaml</info>

<info>NOTE:</info>
Only the Database section of the configuration file will be used.

You can also use the command before setting up a configuration file like so:
    <info>%command.name% -o "localhost" -d "yapeal" -u "YapealUser" -p "secret"

HELP;
        $this->setHelp($help);
    }
    /**
     * @param OutputInterface $output
     *
     * @return string[]
     * @throws InvalidArgumentException
     * @throws YapealConsoleException
     */
    protected function getCreateFileList(OutputInterface $output)
    {
        $sections = ['Database', 'Util', 'Account', 'Api', 'Char', 'Corp', 'Eve', 'Map', 'Server'];
        $path = $this->getDic()['Yapeal.Sql.dir'];
        if (!is_readable($path)) {
            $mess = sprintf(
                '<info>Could NOT access Sql directory %1$s</info>',
                $path
            );
            $output->writeln($mess);
            return [];
        }
        $fileList = [];
        foreach ($sections as $dir) {
            foreach (new DirectoryIterator($path . $dir . '/') as $fileInfo) {
                if (!$fileInfo->isFile()
                    || 'sql' !== $fileInfo->getExtension()
                    || 'Create' !== substr($fileInfo->getBasename(), 0, 6)
                ) {
                    continue;
                }
                $fileList[] = $this->getFpn()
                    ->normalizeFile($fileInfo->getPathname());
            }
        }
        $fileNames = '%1$sCreateCustomTables.sql,%2$sconfig/CreateCustomTables.sql';
        $vendorPath = '';
        if (!empty($this->getDic()['Yapeal.vendorParentDir'])) {
            $fileNames .= ',%3$sconfig/CreateCustomTables.sql';
            $vendorPath = $this->getDic()['Yapeal.vendorParentDir'];
        }
        /**
         * @var array $customFiles
         */
        $customFiles = explode(',', sprintf($fileNames, $path, $this->getDic()['Yapeal.baseDir'], $vendorPath));
        foreach ($customFiles as $fileName)
        {
            if (!is_readable($fileName) || !is_file($fileName)) {
                continue;
            }
            $fileList[] = $fileName;
        }
        return $fileList;
    }
    /**
     * @param OutputInterface $output
     *
     * @throws InvalidArgumentException
     * @throws YapealConsoleException
     * @throws YapealDatabaseException
     */
    protected function processSql(OutputInterface $output)
    {
        foreach ($this->getCreateFileList($output) as $fileName) {
            $sqlStatements = file_get_contents($fileName);
            if (false === $sqlStatements) {
                $mess = sprintf(
                    '<warning>Could NOT get contents of SQL file %1$s</warning>',
                    $fileName
                );
                $output->writeln($mess);
                continue;
            }
            $output->writeln($fileName);
            $this->executeSqlStatements($sqlStatements, $fileName, $output);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $output->writeln('');
        }
    }
}
