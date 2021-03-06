<?php
declare(strict_types = 1);
/**
 * Contains class FileSystemWiring.
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
namespace Yapeal\Configuration;

use Yapeal\Container\ContainerInterface;

/**
 * Class FileSystemWiring.
 */
class FileSystemWiring implements WiringInterface
{
    /**
     * @param ContainerInterface $dic
     *
     * @throws \LogicException
     */
    public function wire(ContainerInterface $dic)
    {
        if (empty($dic['Yapeal.FileSystem.CachePreserver'])) {
            $dic['Yapeal.FileSystem.CachePreserver'] = function () use ($dic) {
                return new $dic['Yapeal.FileSystem.Handlers.preserve']($dic['Yapeal.FileSystem.Cache.dir'],
                    (bool)$dic['Yapeal.FileSystem.Cache.preserve']);
            };
        }
        if (empty($dic['Yapeal.FileSystem.CacheRetriever'])) {
            $dic['Yapeal.FileSystem.CacheRetriever'] = function () use ($dic) {
                return new $dic['Yapeal.FileSystem.Handlers.retrieve']($dic['Yapeal.FileSystem.Cache.dir'],
                    (bool)$dic['Yapeal.FileSystem.Cache.retrieve']);
            };
        }
        if (empty($dic['Yapeal.Event.Mediator'])) {
            $mess = 'Tried to call Mediator before it has been added';
            throw new \LogicException($mess);
        }
        /**
         * @var \Yapeal\Event\MediatorInterface $mediator
         */
        $mediator = $dic['Yapeal.Event.Mediator'];
        foreach (['Yapeal.EveApi.preserve', 'Yapeal.EveApi.Raw.preserve', 'Yapeal.Xml.Error.preserve'] as $event) {
            $mediator->addServiceListener($event, ['Yapeal.FileSystem.CachePreserver', 'preserveEveApi'], 'last');
        }
        $mediator->addServiceListener('Yapeal.EveApi.retrieve',
            ['Yapeal.FileSystem.CacheRetriever', 'retrieveEveApi'],
            'last');
    }
}
