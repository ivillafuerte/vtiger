<?php

class WidgetAccountTickets_Basic_Handler {
	var $Module = false;
	var $Record = false;
	var $Config = array();
	var $moduleModel = false;
	var $dbParams = array();
	
	function __construct($Module = false, $moduleModel = false, $Record = false, $widget = array() ) {
		$this->Module = $Module;
		$this->Record = $Record;
		$this->Config = $widget;
		$this->Config['tpl'] = 'Basic.tpl';
		$this->Data = $widget['data'];
		$this->moduleModel = $moduleModel;
	}
	public function getWidget() {
		return $this->Config;
	}
}