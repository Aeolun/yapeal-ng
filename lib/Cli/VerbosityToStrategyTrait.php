<?php
declare(strict_types = 1);
/**
 * Contains trait VerbosityToStrategyTrait.
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
 * @copyright 2016 Michael Cummings
 * @license   LGPL-3.0+
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\Cli;

use Symfony\Component\Console\Output\OutputInterface;
use Yapeal\Log\Logger;

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/**
 * Trait VerbosityToStrategyTrait.
 *
 * @method \Yapeal\Container\ContainerInterface getDic()
 */
trait VerbosityToStrategyTrait
{
    /**
     * @param OutputInterface $output
     *
     * @return $this Fluent Interface.
     * @throws \LogicException
     */
    protected function setLogThresholdFromVerbosity(OutputInterface $output)
    {
        $logMap = [
            $output::VERBOSITY_QUIET => Logger::ERROR,
            $output::VERBOSITY_NORMAL => Logger::WARNING,
            $output::VERBOSITY_VERBOSE => Logger::NOTICE,
            $output::VERBOSITY_VERY_VERBOSE => Logger::INFO,
            $output::VERBOSITY_DEBUG => Logger::DEBUG
        ];
        $errorMap = [
            $output::VERBOSITY_QUIET => Logger::CRITICAL,
            $output::VERBOSITY_NORMAL => Logger::ERROR,
            $output::VERBOSITY_VERBOSE => Logger::WARNING,
            $output::VERBOSITY_VERY_VERBOSE => Logger::NOTICE,
            $output::VERBOSITY_DEBUG => Logger::INFO
        ];
        /**
         * @var \Yapeal\Log\ActivationStrategy $strategy
         */
        $verbosity = $output->getVerbosity();
        $strategy = $this->getDic()['Yapeal.Log.Callable.Strategy'];
        $strategy->setActionLevel($logMap[$verbosity]);
        $strategy = $this->getDic()['Yapeal.Error.Callable.Strategy'];
        $strategy->setActionLevel($errorMap[$verbosity]);
        return $this;
    }
}
