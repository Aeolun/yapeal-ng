<?php
declare(strict_types = 1);
/**
 * Contains trait ConfigFileProcessingTrait.
 *
 * PHP version 7.0+
 *
 * LICENSE:
 * This file is part of Yet Another Php Eve Api Library also know as Yapeal
 * which can be used to access the Eve Online API data and place it into a
 * database.
 * Copyright (C) 2016 Michael Cummings
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
 * @author    Michael Cummings <mgcummings@yahoo.com>
 * @copyright 2016 Michael Cummings
 * @license   LGPL-3.0+
 */
namespace Yapeal\Configuration;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Yapeal\Container\ContainerInterface;
use Yapeal\Exception\YapealException;
use Yapeal\FileSystem\SafeFileHandlingTrait;

/**
 * Trait ConfigFileProcessingTrait.
 */
trait ConfigFileProcessingTrait
{
    use SafeFileHandlingTrait;
    /**
     * Looks for and replaces any {Yapeal.*} it finds in values with the corresponding other setting value.
     *
     * This will replace full value or part of the value. Examples:
     *
     *     $settings = [
     *         'Yapeal.baseDir' => '/my/junk/path/Yapeal/',
     *         'Yapeal.libDir' => '{Yapeal.baseDir}lib/'
     *         'Yapeal.Sql.dir' => '{Yapeal.libDir}Sql/'
     *     ];
     *
     * After doSubstitutions would be:
     *
     *     $settings = [
     *         'Yapeal.baseDir' => '/my/junk/path/Yapeal/',
     *         'Yapeal.libDir' => '/my/junk/path/Yapeal/lib/'
     *         'Yapeal.Sql.dir' => '/my/junk/path/Yapeal/lib/Sql/'
     *     ];
     *
     * Note that order in which subs are done is undefined so it could have
     * done libDir first and then baseDir into both or done baseDir into libDir
     * then libDir into Sql.dir.
     *
     * Subs from within $settings itself are used first with $dic used to
     * fill-in as needed for any unknown ones.
     *
     * Subs are tried up to 10 times as long as any {Yapeal.*} are found before
     * giving up to prevent infinite loop.
     *
     * @param array              $settings
     * @param ContainerInterface $dic
     *
     * @return array
     * @throws \DomainException
     */
    protected function doSubstitutions(array $settings, ContainerInterface $dic): array
    {
        if (0 === count($settings)) {
            return [];
        }
        $depth = 0;
        $maxDepth = 10;
        $regEx = '%(?<all>\{(?<name>Yapeal(?:\.\w+)+)\})%';
        do {
            $settings = preg_replace_callback($regEx,
                function ($match) use ($settings, $dic) {
                    if (array_key_exists($match['name'], $settings)) {
                        return $settings[$match['name']];
                    }
                    if (!empty($dic[$match['name']])) {
                        return $dic[$match['name']];
                    }
                    return $match['all'];
                },
                $settings,
                -1,
                $count);
            if (++$depth > $maxDepth) {
                $mess = 'Exceeded maximum depth, check for possible circular reference(s)';
                throw new \DomainException($mess);
            }
            $lastError = preg_last_error();
            if (PREG_NO_ERROR !== $lastError) {
                $constants = array_flip(get_defined_constants(true)['pcre']);
                $lastError = $constants[$lastError];
                $mess = 'Received preg error ' . $lastError;
                throw new \DomainException($mess);
            }
        } while ($count > 0);
        return $settings;
    }
    /**
     * Converts any depth Yaml config file into a flattened array with '.' separators and values.
     *
     * @param string $configFile
     * @param array  $existing
     *
     * @return array
     * @throws \DomainException
     * @throws \Yapeal\Exception\YapealException
     */
    protected function parserConfigFile(string $configFile, array $existing = []): array
    {
        $yaml = $this->safeFileRead($configFile);
        if (false === $yaml) {
            return $existing;
        }
        try {
            /**
             * @var \RecursiveIteratorIterator|\Traversable $rItIt
             */
            $rItIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator((new Parser())->parse($yaml,
                true,
                false)));
        } catch (ParseException $exc) {
            $mess = sprintf('Unable to parse the YAML configuration file %2$s. The error message was %1$s',
                $exc->getMessage(),
                $configFile);
            throw new YapealException($mess, 0, $exc);
        }
        $settings = [];
        foreach ($rItIt as $leafValue) {
            $keys = [];
            foreach (range(0, $rItIt->getDepth()) as $depth) {
                $keys[] = $rItIt->getSubIterator($depth)
                    ->key();
            }
            $settings[implode('.', $keys)] = $leafValue;
        }
        return array_replace($existing, $settings);
    }
}