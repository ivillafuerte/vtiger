<?php

class WidgetAccountTickets_RelatedModule_Handler extends WidgetAccountTickets_Basic_Handler {
    public function getUrl() {
        $moduleName=Vtiger_Functions::getModuleName($this->Data['relatedmodule']);
        return 'module=WidgetAccountTickets&view=SummaryWidget&record='.$this->Record.'&mode=showRelatedWidget&relatedModule='.$moduleName.'&page=1&limit='.$this->Data['limit'];
    }
    public function getWidget() {
        $widget = array();
        $moduleName=Vtiger_Functions::getModuleName($this->Data['relatedmodule']);
        $model = Vtiger_Module_Model::getInstance($moduleName);
        if( $model->isPermitted('DetailView') )
        {
            $this->Config['url'] = $this->getUrl();
            $this->Config['tpl'] = 'Basic.tpl';
            if($this->Data['action'] == 1){
                $createPermission = $model->isPermitted('EditView');
                $this->Config['action'] = ($createPermission == true) ? 1 : 0;
                if($model->isQuickCreateSupported())
                    $this->Config['actionURL'] =  $model->getQuickCreateUrl();
                else  $this->Config['actionURL'] =  'index.php?module='.$moduleName.'&view=Edit';
            }
            if(isset($this->Data['filter'])){
                $filterArray = explode('::',$this->Data['filter']);
                $this->Config['field_name'] = $filterArray[2];
                $this->Config['column_name'] = $filterArray[1];
            }
            $widget = $this->Config;
        }
        return $widget;
    }

}