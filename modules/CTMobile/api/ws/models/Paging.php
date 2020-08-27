<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
class CTMobile_WS_PagingModel {
	
	var $_start;
	var $_limit;
	var $_page;
	
	function __construct() {
		$this->_limit = CTMobile::config('API_RECORD_FETCH_LIMIT', 20);
	}
	
	function start() {
		return $this->_start;
	}
	
	function limit() {
		return $this->_limit;
	}
	
	function currentCount() {
		return ($this->current() * $this->limit());
	}
	
	function current() {
		return $this->_page;
	}
	
	function next() {
		return ($this->current()+1);
	}
	
	function previous() {
		return ($this->current() < 1? 0 : ($this->current()-1));
	}
	
	function hasNext($countOnPage) {
		return ($countOnPage >= $this->limit());
	}
	
	function hasPrevious() {
		return ($this->start() != 0);
	}
	
	function initStart($page) {
		
		if(empty($page)) $page = 0;
		$this->_page = $page;		
		
		if($page < 1) $this->_start = 0;
		else $this->_start = ($page * $this->_limit);
	}
	
	function setLimit($limit) {
		$this->_limit = $limit;
	}
	
	static function modelWithPageStart($start) {
		$instance = new self();
		$instance->initStart($start);
		return $instance;
	}
}
?>
