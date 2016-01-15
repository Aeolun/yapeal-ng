<?php
/**
 * Contains AccountBalance class.
 *
 * PHP version 5.4
 *
 * @copyright 2015-2016 Michael Cummings
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace Yapeal\EveApi\Corp;

use Yapeal\Sql\PreserverTrait;

/**
 * Class AccountBalance
 */
class AccountBalance extends CorpSection
{
    use PreserverTrait;
    /**
     *
     */
    public function __construct()
    {
        $this->mask = 1;
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
    protected function preserveToAccountBalance($xml, $ownerID)
    {
        $columnDefaults = [
            'ownerID' => $ownerID,
            'accountID' => null,
            'accountKey' => null,
            'balance' => null
        ];
        $tableName = 'corpAccountBalance';
        $this->attributePreserveData($xml, $columnDefaults, $tableName);
        return $this;
    }
}
