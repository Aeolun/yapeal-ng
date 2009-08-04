<?php
/**
 * Class used to fetch and store ConquerableStationList API.
 *
 * PHP version 5
 *
 * LICENSE: This file is part of Yet Another Php Eve Api library also know
 * as Yapeal which will be used to refer to it in the rest of this license.
 *
 *  Yapeal is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Yapeal is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with Yapeal. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Michael Cummings <mgcummings@yahoo.com>
 * @copyright Copyright (c) 2008-2009, Michael Cummings
 * @license http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @package Yapeal
 */
/**
 * @internal Allow viewing of the source code in web browser.
 */
if (isset($_REQUEST['viewSource'])) {
  highlight_file(__FILE__);
  exit();
};
/**
 * @internal Only let this code be included or required not ran directly.
 */
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  exit();
};
/**
 * Class used to fetch and store AllianceList API.
 *
 * @package Yapeal
 * @subpackage Api_eve
 */
class eveConquerableStationList implements IFetchApiTable, IStoreApiTable {
  /**
   * @var string Holds the name of the API.
   */
  protected $api = 'ConquerableStationList';
  /**
   * @var string Name of Eve server.
   */
  protected $serverName;
  /**
   * @var string DB table prefix.
   */
  protected $tablePrefix;
  /**
   * @var array Holds the database column names and ADOdb types.
   */
  private $types = array('corporationID' => 'I', 'corporationName' => 'C',
    'solarSystemID' => 'I', 'stationID' => 'I', 'stationName' => 'C',
    'stationTypeID' => 'I');
  /**
   * @var SimpleXMLElement Hold the XML return from API.
   */
  protected $xml;
  /**
   * @var string Xpath used to select data from XML.
   */
  private $xpath = '//row';
  /**
   * Constructor
   *
   * @param array $params Holds the required parameters like userID, apiKey,
   * etc as needed.
   *
   * @return object Returns the instance of the class.
   *
   * @throws LengthException for any missing required $params.
   */
  public function __construct(array $params = array('serverName' =>'Tranquility')) {
    $this->tablePrefix = YAPEAL_TABLE_PREFIX . 'eve';
    if (isset($params['serverName']) && is_string($params['serverName'])) {
      $this->serverName = $params['serverName'];
    } else {
      $mess = 'Missing required parameter $params["serverName"] to constructor';
      $mess .= ' for ' . $this->api . ' from eve section in ' . __FILE__;
      throw new LengthException($mess, 1);
    };// else isset $params['serverName'] ...
  }
  /**
   * Used to get an item from Eve API.
   *
   * Parent item (object) should call all child(ren)'s apiFetch() as appropriate.
   *
   * @return boolean Returns TRUE if item received.
   */
  function apiFetch() {
    global $tracing;
    $tableName = $this->tablePrefix . $this->api;
    try {
      // Build base part of cache file name.
      $cacheName = $this->serverName . $tableName . '.xml';
      // Try to get XML from local cache first if we can.
      $mess = 'getCachedXml for ' . $cacheName;
      $mess .= ' from eve section in ' . __FILE__;
      $tracing->activeTrace(YAPEAL_TRACE_EVE, 2) &&
      $tracing->logTrace(YAPEAL_TRACE_EVE, $mess);
      $xml = YapealApiRequests::getCachedXml($cacheName, YAPEAL_API_EVE);
      if (empty($xml)) {
        $mess = 'getAPIinfo for ' . $this->api;
        $mess .= ' from eve section in ' . __FILE__;
        $tracing->activeTrace(YAPEAL_TRACE_EVE, 2) &&
        $tracing->logTrace(YAPEAL_TRACE_EVE, $mess);
        $xml = YapealApiRequests::getAPIinfo($this->api, YAPEAL_API_EVE);
        if ($xml instanceof SimpleXMLElement) {
          // Store XML in local cache.
          YapealApiRequests::cacheXml($xml->asXML(), $cacheName, YAPEAL_API_EVE);
        };// if $xml ...
      };// if empty $xml ...
      if (!empty($xml)) {
        $this->xml = $xml;
        return TRUE;
      } else {
        $mess = 'No XML found for ' . $tableName;
        $mess .= ' from eve section in ' . __FILE__;
        trigger_error($mess, E_USER_NOTICE);
        return FALSE;
      };
    }
    catch (YapealApiException $e) {
      return FALSE;
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
  }
  /**
   * Used to save an item into database.
   *
   * Parent item (object) should call all child(ren)'s apiStore() as appropriate.
   *
   * @return boolean Returns TRUE if item was saved to database.
   */
  function apiStore() {
    global $tracing;
    global $cachetypes;
    $ret = FALSE;
    $tableName = $this->tablePrefix . $this->api;
    if ($this->xml instanceof SimpleXMLElement) {
      $mess = 'Xpath for ' . $tableName . ' from eve section in ' . __FILE__;
      $tracing->activeTrace(YAPEAL_TRACE_EVE, 2) &&
      $tracing->logTrace(YAPEAL_TRACE_EVE, $mess);
      $datum = $this->xml->xpath($this->xpath);
      if (count($datum) > 0) {
        try {
          $mess = 'Connect for '. $tableName;
          $mess .= ' from eve section in ' . __FILE__;
          $tracing->activeTrace(YAPEAL_TRACE_EVE, 2) &&
          $tracing->logTrace(YAPEAL_TRACE_EVE, $mess);
          $con = connect(YAPEAL_DSN, $tableName);
          $mess = 'Before truncate ' . $tableName;
          $mess .= ' from eve section in ' . __FILE__;
          $tracing->activeTrace(YAPEAL_TRACE_EVE, 2) &&
          $tracing->logTrace(YAPEAL_TRACE_EVE, $mess);
          // Empty out old data then upsert (insert) new
          $sql = 'truncate table ' . $this->tablePrefix . $this->api;
          $con->Execute($sql);
          $mess = 'multiUpsertAttributes for ' . $tableName;
          $mess .= ' from eve section in ' . __FILE__;
          $tracing->activeTrace(YAPEAL_TRACE_EVE, 1) &&
          $tracing->logTrace(YAPEAL_TRACE_EVE, $mess);
          multipleUpsertAttributes($datum, $this->types, $tableName,
            YAPEAL_DSN);
        }
        catch (ADODB_Exception $e) {
          return FALSE;
        }
        $ret = TRUE;
      } else {
      $mess = 'There was no XML data to store for ' . $tableName;
      $mess .= ' from eve section in ' . __FILE__;
      trigger_error($mess, E_USER_NOTICE);
      $ret = FALSE;
      };// else count $datum ...
      try {
        // Update CachedUntil time since we should have a new one.
        $cuntil = (string)$this->xml->cachedUntil[0];
        $data = array('tableName' => $tableName, 'ownerID' => 0,
          'cachedUntil' => $cuntil);
        $mess = 'Upsert for '. $tableName;
        $mess .= ' from eve section in ' . __FILE__;
        $tracing->activeTrace(YAPEAL_TRACE_CACHE, 0) &&
        $tracing->logTrace(YAPEAL_TRACE_CACHE, $mess);
        upsert($data, $cachetypes, YAPEAL_TABLE_PREFIX . 'utilCachedUntil',
          YAPEAL_DSN);
      }
      catch (ADODB_Exception $e) {
        // Already logged nothing to do here.
      }
    };// if $this->xml ...
    return $ret;
  }// function apiStore()
}
?>
