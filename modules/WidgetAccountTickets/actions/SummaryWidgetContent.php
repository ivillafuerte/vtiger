<?php

class WidgetAccountTickets_SummaryWidgetContent_Action extends Vtiger_Action_Controller {

    function checkPermission(Vtiger_Request $request) {
        return;
    }

    function process(Vtiger_Request $request) {
        $mode = $request->getMode();
        $response = new Vtiger_Response();

        $result= $this->getCustomWidgets($request);

        $response->setResult($result);
        $response->emit();

    }
    public function getCustomWidgets(Vtiger_Request $request) {
        global $vtiger_current_version;
        if(version_compare($vtiger_current_version, '7.0.0', '<')) {
            return $this->getCustomWidgetsV6($request);
        }else{
            return $this->getCustomWidgetsV7($request);
        }

    }

    public function getCustomWidgetsV7(Vtiger_Request $request) {
        global $adb;


        $moduleName = $request->get('sourcemodule');
        if ($moduleName != 'Accounts') {
            return;
        }
        $moduleModel =  Vtiger_Module_Model::getInstance($moduleName);;
        $Record = $request->get('record');
        if($moduleName=='' || $Record=='') return;
        $html_viewwidgets = array();
        $html_span7='';
        $helpDeskModuleModel = Vtiger_Module_Model::getInstance('HelpDesk');
        $idHelpDesk = $helpDeskModuleModel->getId();
        $AccountsModuleModel = Vtiger_Module_Model::getInstance('Accounts');
        $relationModel=Vtiger_Relation_Model::getInstance($AccountsModuleModel,$helpDeskModuleModel);
        $action = $relationModel->get('actions');

        $actionAdd = 0;
        $actionSelect = 0;
        if (strpos($action, 'add') !== false) {
            $actionAdd = 1;
        }
        if (strpos($action, 'select') !== false) {
            $actionSelect = 1;
        }
        $widgetCol = array(
                1 => array(
                    'type' => 'RelatedModule',
                    'wcol' => '1',
                    'label' => 'Tickets',
                    'data' => array(
                        'limit' => '20',
                        'relatedmodule' => $idHelpDesk,
                        'action' => $actionAdd,
                        'select' => $actionSelect,
                        'sorttype' => 'DESC',
                        'sortby' => 'createdtime',
                        'fieldList' => array('ticket_title', 'ticketstatus', 'ticketpriorities', 'createdtime'),
                        'isactive' => 1,
                        'filter' => '-',
                    ),

                    'type' => 'RelatedModule',
                ),
        );
        $index=0;
            foreach ($widgetCol as $widget) {
                //foreach ($ModelWidgets[1] as $widget) {
                $widgetName =  'WidgetAccountTickets_'.$widget['type'].'_Handler';
                if (class_exists($widgetName)) {
                    $widgetInstance = new $widgetName($moduleName, $moduleModel, $Record, $widget);
                    $WIDGET = $widgetInstance->getWidget();
                    if (count($WIDGET) > 0)
                    {
                        if($WIDGET['isactive']=='0')  continue;
                        $RELATED_MODULE_NAME=Vtiger_Functions::getModuleName($WIDGET['data']['relatedmodule']);
                        if($RELATED_MODULE_NAME !='') {
                            $RELATED_MODULE_MODEL =Vtiger_Module_Model::getInstance($RELATED_MODULE_NAME);
                            if($WIDGET['field_name']!=''){
                                $FIELD_MODEL =$RELATED_MODULE_MODEL->getField($WIDGET['field_name']);
                                $FIELD_INFO =Zend_Json::encode($FIELD_MODEL->getFieldInfo());
                                if($WIDGET['column_name']=='taxtype'){
                                    $PICKLIST_VALUES=array();
                                    $PICKLIST_VALUES["individual"] =vtranslate('LBL_INDIVIDUAL', $RELATED_MODULE_NAME);
                                    $PICKLIST_VALUES["group"] =vtranslate('LBL_GROUP', $RELATED_MODULE_NAME);
                                }else{
                                    $PICKLIST_VALUES =$FIELD_MODEL->getPicklistValues();
                                }
                                $SPECIAL_VALIDATOR=$FIELD_MODEL->getValidator();
                            }
                            if($WIDGET['data']['fieldList'] !=''){
                                $fieldlist=  ZEND_JSON::encode($WIDGET['data']['fieldList']);
                            }
                        }
                        // odd widget
                        // if($index%2 != 0){
                        if($WIDGET['wcol']=='1'){
                            $class='customwidgetContainer_tickets';
                            $filter=$WIDGET['data']['filter'];
                            $url=$WIDGET['url'].'&sourcemodule='.$moduleName;
                            $html_span7.='<div class="summaryWidgetContainer">
                                            <div class="'.$class.' widgetContentBlock" data-url="'.$url.'" data-name="'.$WIDGET['label'].'" data-type="'.$widget['type'].'">
		                                        <div class="widget_header row-fluid">
			                                        <input type="hidden" class="relatedlimit" name="relatedlimit" value="'.$WIDGET['data']['limit'].'" />
			                                        <input type="hidden" class="relatedModuleName" name="relatedModule" value="'.$RELATED_MODULE_NAME.'" />';
                            if($widget['type']=='RelatedModule'){
                                $html_span7.=' <input type="hidden" name="columnslist" value="'.Vtiger_Util_Helper::toSafeHTML($fieldlist).'" />';
                                $html_span7.=' <input type="hidden" name="sortby" value="'.$WIDGET['data']['sortby'].'" />';
                                $html_span7.=' <input type="hidden" name="sorttype" value="'.$WIDGET['data']['sorttype'].'" />';
                            }
                            $html_span7.='<span class="span11 margin0px">
                                            <div class="row-fluid"><h4 class="display-inline-block" style="width:10em;">'.vtranslate($WIDGET['label'],$moduleName).'</h4>';
                            if ($filter !='' && $filter!='-')
                            {
                                $html_span7.='<input type="hidden"  name="filter_data" value="'.$filter.'" />
                                                <span class="span2 alignCenter" style="margin-left:30px">
                                                    <select class="chzn-select filterField" style="max-width:200px;" name="'.$FIELD_MODEL->get('name').'" data-validation-engine="validate[';
                                if($FIELD_MODEL->isMandatory()==true) $html_span7.=' required,';
                                $html_span7.='funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"';
                                $html_span7.=' data-fieldinfo="'.Vtiger_Util_Helper::toSafeHTML($FIELD_INFO).'" ';

                                if (!empty($SPECIAL_VALIDATOR)) $html_span7.=' data-validator="'.Zend_Json::encode($SPECIAL_VALIDATOR).'"';
                                $html_span7.= ' data-fieldlable="'.vtranslate($FIELD_MODEL->get('label'),$RELATED_MODULE_NAME).'"';
                                $html_span7.= ' data-filter="'.$FIELD_MODEL->get('table').'.'.$WIDGET['column_name'].'" data-urlparams="whereCondition">';
                                $html_span7.=  '<option>'.'Select '.vtranslate($FIELD_MODEL->get('label'),$RELATED_MODULE_NAME).'</option>';

                                foreach ($PICKLIST_VALUES as $key =>$value) {
                                    $html_span7.='   <option value="'.$key.'"';
                                    if ($FIELD_MODEL->get('fieldvalue') == $key) $html_span7.='selected';
                                    $html_span7.='>'.$value.'</option>';
                                }
                                $html_span7.='</select> </span>';

                            }
                            if ($WIDGET['data']['action'] == '1'|| $WIDGET['data']['select'] == '1'){
                                $html_span7.=' <span class="pull-right"  >';
                            }
                            if ($WIDGET['data']['action'] == '1'){
                                $VRM =Vtiger_Record_Model::getInstanceById($Record, $moduleName);
                                $VRMM  =Vtiger_RelationListView_Model::getInstance($VRM, $RELATED_MODULE_NAME);
                                $RELATIONMODEL  = $VRMM->getRelationModel();
                                $RELATION_FIELD  = $RELATIONMODEL->getRelationField();
                                $html_span7.='<button class="btn addButton vteWidgetCreateTicketsButton" style="background: url(layouts/v7/modules/WidgetAccountTickets/resources/imgs/add.png);height:26px;width:26px;border:none; background-size: cover" type="button" href="javascript:void(0)"
                                                data-url="'.$WIDGET['actionURL'].'" data-name="'.$RELATED_MODULE_NAME.'"';
                                if ($RELATION_FIELD) $html_span7.=' data-prf="'.$RELATION_FIELD->getName().'"';
                                $html_span7.='> </button>';
                            }
                            if ($WIDGET['data']['select'] == '1'){
                                $html_span7.='<button class="btn addButton selectRelationTicketonWidget"  style="margin-right:5px; background: url(layouts/v7/modules/WidgetAccountTickets/resources/imgs/select.png);height:26px;width:26px;border:none; background-size: cover" type="button"
                                                data-modulename="'.$RELATED_MODULE_NAME.'"';
                                if ($RELATION_FIELD) $html_span7.=' data-prf="'.$RELATION_FIELD->getName().'"';
                                $html_span7.='> </button>';
                            }
                            if ($WIDGET['data']['action'] == '1'|| $WIDGET['data']['select'] == '1')   $html_span7.=' </span> ';
                            $html_span7.=' </div></span>';
                            $html_span7.='</div>
                             		      <div class="widget_contents"></div>
		                                </div> </div>';
                        }
                        //even widget
                        $index++;

                    }
                }
            }
        $html_viewwidgets['span7']=$html_span7; //odd widget
        return $html_viewwidgets;
    }

}
