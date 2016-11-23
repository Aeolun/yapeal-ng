<?php
declare(strict_types = 1);
/**
 * Contains class WalletTransactions.
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
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\EveApi\Corp;

use Yapeal\Event\EveApiPreserverInterface;
use Yapeal\Log\Logger;
use Yapeal\Sql\PreserverTrait;
use Yapeal\Xml\EveApiReadWriteInterface;

/**
 * Class WalletTransactions
 */
class WalletTransactions extends CorpSection implements EveApiPreserverInterface
{
    use PreserverTrait;

    /** @noinspection MagicMethodsValidityInspection */
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->accountKeys = [1000, 1001, 1002, 1003, 1004, 1005, 1006, 10000];
        $this->mask = 2097152;
        $this->preserveTos = [
            'preserveToWalletTransactions'
        ];
    }
    /**
     * @param EveApiReadWriteInterface $data
     *
     * @return self Fluent interface.
     * @throws \LogicException
     */
    protected function preserveToWalletTransactions(EveApiReadWriteInterface $data)
    {
        $tableName = 'corpWalletTransactions';
        $ownerID = $this->extractOwnerID($data->getEveApiArguments());
        $sql = $this->getCsq()
            ->getDeleteFromTableWithOwnerID($tableName, $ownerID);
        $this->getYem()
            ->triggerLogEvent('Yapeal.Log.log', Logger::DEBUG, $sql);
        $this->getPdo()
            ->exec($sql);
        $columnDefaults = [
            'characterID' => null,
            'characterName' => '',
            'clientID' => null,
            'clientName' => '',
            'clientTypeID' => null,
            'journalTransactionID' => null,
            'ownerID' => $ownerID,
            'price' => null,
            'quantity' => null,
            'stationID' => null,
            'stationName' => '',
            'transactionDateTime' => '1970-01-01 00:00:01',
            'transactionFor' => null,
            'transactionID' => null,
            'transactionType' => null,
            'typeID' => null,
            'typeName' => ''
        ];
        $xPath = '//transactions/row';
        $elements = (new \SimpleXMLElement($data->getEveApiXml()))->xpath($xPath);
        $this->attributePreserveData($elements, $columnDefaults, $tableName);
        return $this;
    }
}
