<?php
/**
 * Contains abstract class for server section.
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
 * @author     Michael Cummings <mgcummings@yahoo.com>
 * @copyright  Copyright (c) 2008-2009, Michael Cummings
 * @license    http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @package    Yapeal
 * @link       http://code.google.com/p/yapeal/
 * @link       http://www.eve-online.com/
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
 * Abstract class for Server APIs.
 *
 * @package Yapeal
 * @subpackage Api_server
 */
abstract class AServer implements IFetchApiTable, IStoreApiTable {
  /**
   * @var string Holds proxy info.
   */
  protected $proxy;
  /**
   * @var string Name of Eve server.
   */
  protected $serverName;
  /**
   * @var string DB table prefix.
   */
  protected $tablePrefix;
  /**
   * @var SimpleXMLElement Hold the XML return from API.
   */
  protected $xml;
  /**
   * Constructor
   *
   * @param string $proxy Allows overriding API server for example to use a
   * different proxy on a per char/corp basis. It should contain a url format
   * string made to used in sprintf() to replace %1$s with $api and %2$s with
   * @param array $params Holds the required parameters like userID, apiKey,
   * etc as needed.
   *
   * @return object Returns the instance of the class.
   *
   * @throws LengthException for any missing required $params.
   */
  public function __construct($proxy, array $params) {
    $this->tablePrefix = YAPEAL_TABLE_PREFIX . 'server';
    $this->proxy = $proxy;
    if (isset($params['serverName']) && is_string($params['serverName'])) {
      $this->serverName = $params['serverName'];
    } else {
      $mess = 'Missing required parameter $params["serverName"] to constructor';
      $mess .= ' for ' . $this->api . ' in ' . basename(__FILE__);
      throw new LengthException($mess, 1);
    };// else isset $params['serverName'] ...
  }// function __construct
  /**
   * Used to get an item from Map API.
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
      $mess .= ' in ' . basename(__FILE__);
      $tracing->activeTrace(YAPEAL_TRACE_SERVER, 2) &&
      $tracing->logTrace(YAPEAL_TRACE_SERVER, $mess);
      $xml = YapealApiRequests::getCachedXml($cacheName, YAPEAL_API_SERVER);
      if (empty($xml)) {
        $mess = 'getAPIinfo for ' . $this->api;
        $mess .= ' in ' . basename(__FILE__);
        $tracing->activeTrace(YAPEAL_TRACE_SERVER, 2) &&
        $tracing->logTrace(YAPEAL_TRACE_SERVER, $mess);
        $xml = YapealApiRequests::getAPIinfo($this->api, YAPEAL_API_SERVER, NULL,
          $this->proxy);
        if ($xml instanceof SimpleXMLElement) {
          // Store XML in local cache.
          YapealApiRequests::cacheXml($xml->asXML(), $cacheName, YAPEAL_API_SERVER);
        };// if $xml ...
      };// if empty $xml ...
      if (!empty($xml)) {
        $this->xml = $xml;
        return TRUE;
      } else {
        $mess = 'No XML found for ' . $tableName;
        trigger_error($mess, E_USER_NOTICE);
        return FALSE;
      };
    }
    catch (YapealApiException $e) {
      return FALSE;
    }
    catch (YapealApiFileException $e) {
      return FALSE;
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
  }// function apiFetch
}
?>
