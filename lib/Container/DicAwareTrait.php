<?php
declare(strict_types = 1);
/**
 * Contains trait DicAwareTrait.
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
namespace Yapeal\Container;

/**
 * Trait DicAwareTrait.
 */
trait DicAwareTrait
{
    /**
     * Smart getter that tries to get instance from parent first before failing.
     *
     * @return ContainerInterface|\ArrayAccess
     * @throws \LogicException
     */
    public function getDic(): ContainerInterface
    {
        if (null === $this->dic) {
            $parent = get_parent_class($this);
            if ($parent instanceof DicAwareInterface) {
                $this->dic = $parent->getDic();
            } else {
                $mess = 'Trying to access $dic before it was set';
                throw new \LogicException($mess, 1);
            }
        }
        return $this->dic;
    }
    /**
     * @return bool
     */
    public function hasDic(): bool
    {
        return null !== $this->dic;
    }
    /**
     * @param ContainerInterface $value
     *
     * @return void
     */
    public function setDic(ContainerInterface $value)
    {
        $this->dic = $value;
    }
    /**
     * @var ContainerInterface $dic
     */
    private $dic;
}
