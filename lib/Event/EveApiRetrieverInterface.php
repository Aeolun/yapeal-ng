<?php
/**
 * Contains EveApiRetrieverInterface Interface.
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
namespace Yapeal\Event;

/**
 * Common interface for any class that would retrieve (file / network) the Eve
 * Api data in some way.
 */
interface EveApiRetrieverInterface
{
    /**
     * Method that is called for retrieve event.
     *
     * @param EveApiEventInterface $event
     * @param string               $eventName
     * @param MediatorInterface    $yem
     *
     * @return EveApiEventInterface
     * @throws \LogicException
     */
    public function retrieveEveApi(EveApiEventInterface $event, $eventName, MediatorInterface $yem);
    /**
     * Turn on or off retrieving of Eve API data by this retriever.
     *
     * Allows class to stay registered for events but be enabled or disabled during runtime.
     *
     * @param boolean $value
     *
     * @return $this Fluent interface
     */
    public function setRetrieve($value = true);
}