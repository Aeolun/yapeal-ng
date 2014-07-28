<?php
/**
 * Contains Standings class.
 * PHP version 5.3
 * LICENSE:
 * This file is part of yapeal-master
 * Copyright (C) 2014 Michael Cummings
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
 * You should be able to find a copy of this license in the LICENSE.md file. A
 * copy of the GNU GPL should also be available in the GNU-GPL.md file.
 *
 * @copyright 2014 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Database\Char;

use LogicException;
use PDOException;
use Yapeal\Database\AttributesDatabasePreserverTrait;
use Yapeal\Database\Char\AbstractCharSection;
use Yapeal\Database\EveApiNameTrait;

/**
 * Class Standings
 */
class Standings extends AbstractCharSection
{
    use EveApiNameTrait, AttributesDatabasePreserverTrait;
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @throws LogicException
     * @return bool
     */
    protected function preserve(
        $xml,
        $ownerID
    ) {
        try {
            $this->getPdo()
                 ->beginTransaction();
            $this->preserverToAgents($xml, $ownerID)
                 ->preserverToNpcCorporations($xml, $ownerID)
                 ->preserverToFactions($xml, $ownerID);
            $this->getPdo()
                 ->commit();
        } catch (PDOException $exc) {
            $mess = sprintf(
                'Failed to upsert data from Eve API %1$s/%2$s for %3$s',
                strtolower($this->getSectionName()),
                $this->getApiName(),
                $ownerID
            );
            $this->getLogger()
                 ->warning($mess, array('exception' => $exc));
            $this->getPdo()
                 ->rollBack();
            return false;
        }
        return true;
    }
    /**
     * @param $xml
     * @param $ownerID
     *
     * @return self
     */
    protected function preserverToAgents($xml, $ownerID)
    {
        $columnDefaults = array(
            'fromID' => null,
            'fromName' => null,
            'ownerID' => $ownerID,
            'standing' => null
        );
        $tableName = 'charAgents';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//agents/row'
        );
        return $this;
    }
    /**
     * @param $xml
     * @param $ownerID
     *
     * @return self
     */
    protected function preserverToFactions($xml, $ownerID)
    {
        $columnDefaults = array(
            'fromID' => null,
            'fromName' => null,
            'ownerID' => $ownerID,
            'standing' => null
        );
        $tableName = 'charFactions';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//factions/row'
        );
        return $this;
    }
    /**
     * @param $xml
     * @param $ownerID
     *
     * @return self
     */
    protected function preserverToNpcCorporations($xml, $ownerID)
    {
        $columnDefaults = array(
            'fromID' => null,
            'fromName' => null,
            'ownerID' => $ownerID,
            'standing' => null
        );
        $tableName = 'charNPCCorporations';
        $sql = $this->getCsq()
                    ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getLogger()
             ->info($sql);
        $this->getPdo()
             ->exec($sql);
        $this->attributePreserveData(
            $xml,
            $columnDefaults,
            $tableName,
            '//NPCCorporations/row'
        );
        return $this;
    }
}
