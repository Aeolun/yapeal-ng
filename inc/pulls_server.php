<?php
/**
 * Used to get server information from Eve-online API.
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
$sectionFile = basename(__FILE__);
if ($sectionFile == basename($_SERVER['PHP_SELF'])) {
  exit();
};
/* **************************************************************************
* Global API pulls
* **************************************************************************/
$apis = array('serverServerStatus');
$serverName = 'Tranquility';
foreach ($apis as $api) {
  $tableName = YAPEAL_TABLE_PREFIX . $api;
  $mess = 'dontWait for ' . $tableName . ' in ' . $sectionFile;
  $tracing->activeTrace(YAPEAL_TRACE_SERVER, 2) &&
  $tracing->logTrace(YAPEAL_TRACE_SERVER, $mess);
  // Should we wait to get API data
  if (dontWait($tableName)) {
    // Set it so we wait a bit before trying again if something goes wrong.
    $data = array('tableName' => $tableName,
      'ownerID' => 0, 'cachedUntil' => YAPEAL_START_TIME);
    $mess = 'Before upsert for ' . $tableName . ' in ' . $sectionFile;
    $tracing->activeTrace(YAPEAL_TRACE_CACHE, 1) &&
    $tracing->logTrace(YAPEAL_TRACE_CACHE, $mess);
    try {
      upsert($data, $cachetypes, YAPEAL_TABLE_PREFIX . 'utilCachedUntil',
      YAPEAL_DSN);
    }
    catch(ADODB_Exception $e) {}
  } else {
    continue;
  };// else dontWait ...
  $params = array('serverName' => $serverName);
  $mess = 'Before instance for ' . $tableName;
  $mess .= ' in ' . $sectionFile;
  $tracing->activeTrace(YAPEAL_TRACE_SERVER, 2) &&
  $tracing->logTrace(YAPEAL_TRACE_SERVER, $mess);
  $instance = new $api($params);
  if ($instance->apiFetch()) {
    $instance->apiStore();
  };
  $instance = null;
};// foreach $apis ...
?>
