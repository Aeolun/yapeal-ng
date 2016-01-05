<?php
/**
 * Contains APIKeyInfo class.
 *
 * PHP version 5.4
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
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A
 * copy of the GNU GPL should also be available in the GNU-GPL.md file.
 *
 * @copyright 2016 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\EveApi\Account;

use PDO;
use PDOException;
use Yapeal\Event\EveApiEventInterface;
use Yapeal\Event\MediatorInterface;
use Yapeal\Log\Logger;
use Yapeal\Sql\PreserverTrait;
use Yapeal\Xml\EveApiReadWriteInterface;

/**
 * Class APIKeyInfo
 */
class APIKeyInfo extends AccountSection
{
    use PreserverTrait;
    /**
     * @param EveApiEventInterface $event
     * @param string               $eventName
     * @param MediatorInterface    $yem
     *
     * @return EveApiEventInterface
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function preserveEveApi(EveApiEventInterface $event, $eventName, MediatorInterface $yem)
    {
        $this->setYem($yem);
        $data = $event->getData();
        $xml = $data->getEveApiXml();
        $ownerID = $this->extractOwnerID($data->getEveApiArguments());
        $this->getYem()
            ->triggerLogEvent(
                'Yapeal.Log.log',
                Logger::DEBUG,
                $this->getReceivedEventMessage($data, $eventName, __CLASS__)
            );
        $this->getPdo()
            ->beginTransaction();
        try {
            $this->preserveToAPIKeyInfo($xml, $ownerID)
                ->preserveToCharacters($xml)
                ->preserveToKeyBridge($xml, $ownerID);
            $this->getPdo()
                ->commit();
        } catch (PDOException $exc) {
            $mess = 'Failed to upsert data of';
            $this->getYem()
                ->triggerLogEvent(
                    'Yapeal.Log.log',
                    Logger::WARNING,
                    $this->createEveApiMessage($mess, $data),
                    ['exception' => $exc]
                );
            $this->getPdo()
                ->rollBack();
            return $event;
        }
        return $event->setHandledSufficiently();
    }
    /**
     * @param EveApiReadWriteInterface $data
     *
     * @return array
     * @throws \LogicException
     */
    protected function getActive(EveApiReadWriteInterface $data)
    {
        $sql = $this->getCsq()
            ->getActiveRegisteredKeys();
        $this->getYem()
            ->triggerLogEvent('Yapeal.Log.log', Logger::DEBUG, $sql);
        try {
            /**
             * @type \PDOStatement $stmt
             */
            $stmt = $this->getPdo()
                ->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exc) {
            $mess = 'Could NOT get a list of active owners for';
            $this->getYem()
                ->triggerLogEvent(
                    'Yapeal.Log.log',
                    Logger::WARNING,
                    $this->createEveApiMessage($mess, $data),
                    ['exception' => $exc]
                );
            return [];
        }
    }
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @return self Fluent interface.
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    protected function preserveToAPIKeyInfo($xml, $ownerID)
    {
        $tableName = 'accountAPIKeyInfo';
        $columnDefaults = [
            'accessMask' => null,
            'expires'    => '2038-01-19 03:14:07',
            'keyID'      => $ownerID,
            'type'       => null
        ];
        $this->attributePreserveData($xml, $columnDefaults, $tableName, '//key');
        return $this;
    }
    /**
     * @param string $xml
     *
     * @return self Fluent interface.
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    protected function preserveToCharacters($xml)
    {
        $tableName = 'accountCharacters';
        $columnDefaults = [
            'allianceID'      => null,
            'allianceName'    => null,
            'characterID'     => null,
            'characterName'   => null,
            'corporationID'   => null,
            'corporationName' => null,
            'factionID'       => null,
            'factionName'     => null
        ];
        $this->attributePreserveData($xml, $columnDefaults, $tableName, '//characters/row');
        return $this;
    }
    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $xml
     * @param string $ownerID
     *
     * @return self Fluent interface.
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    protected function preserveToKeyBridge($xml, $ownerID)
    {
        $tableName = 'accountKeyBridge';
        $columnDefaults = ['keyID' => $ownerID, 'characterID' => null];
        $this->attributePreserveData($xml, $columnDefaults, $tableName, '//characters/row');
        return $this;
    }
}
