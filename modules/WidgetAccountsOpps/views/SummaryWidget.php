<?php

 class WidgetAccountsOpps_SummaryWidget_View extends Vtiger_Index_View {
     function __construct() {
         parent::__construct();
         $this->exposeMethod('showRelatedWidget');
     }
     public function checkPermission(Vtiger_Request $request) {
         $moduleName = $request->getModule();
         $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

         $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
         if(!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
             throw new AppException(vtranslate($moduleName).' '.vtranslate('LBL_NOT_ACCESSIBLE'));
         }
     }
     function process(Vtiger_Request $request) {
         $mode = $request->getMode();
         if(!empty($mode)) {
             $this->invokeExposedMethod($mode, $request);
             return;
         }
     }
     function showRelatedWidget(Vtiger_Request $request) {
         global $currentModule;

         $parentId = $request->get('record');
         $pageNumber = $request->get('page');
         $limit = $request->get('limit');
         $relatedModuleName = $request->get('relatedModule');
         $moduleName = $request->get('sourcemodule');
         $currentModule = $moduleName;
         if(empty($moduleName)|| $moduleName!='Accounts') return;
         if(empty($pageNumber)) {
             $pageNumber = 1;
         }

         $pagingModel = new Vtiger_Paging_Model();
         $pagingModel->set('page', $pageNumber);
         if(!empty($limit)) {
             $pagingModel->set('limit', $limit);
         }
         $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
         //echo $parentId.' '.$relatedModuleName;
         $relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName);

         $whereCondition = $request->get('whereCondition');
         if ($whereCondition!='') {
             $relationListView->set('whereCondition', $whereCondition);
         }
         $orderBy = $request->get('sortby');
         $sortOrder = $request->get('sorttype');
         if(!empty($orderBy) && $orderBy != -1) {
             $relationListView->set('orderby', $orderBy);
             $relationListView->set('sortorder',$sortOrder);
         }

         $models = $relationListView->getEntries($pagingModel);
         foreach ($models as $recordId => $recordModel) {
             $record = Vtiger_Record_Model::getInstanceById($recordId);
             $relatedRecordList[$recordId] = $record;
         }
         if(count($relatedRecordList)<=0) {
             echo 'No related record';
             return;
         }
         $header=array();
         $fieldList=$request->get('fieldList');
         if( $fieldList)
         {
             foreach ($fieldList as $fieldname) {
                 $moduleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
                     $fieldModel = Vtiger_Field_Model::getInstance($fieldname, $moduleModel);
                     if ($fieldModel->isViewable()) {
                         $header[$fieldname]=$fieldModel;
                     }
             }
         }else
             $header = $relationListView->getHeaders();
         $viewer = $this->getViewer($request);

         $viewer->assign('MODULE' , $moduleName);
         $viewer->assign('RELATED_RECORDS' , $relatedRecordList);
         $viewer->assign('RELATED_HEADERS', $header);

         $relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
         $viewer->assign('RELATED_MODULE_MODEL', $relatedModuleModel);
         $viewer->assign('RELATED_MODULE_NAME', $relatedModuleName);
         echo $viewer->view('RelatedWidgetsContent.tpl', 'WidgetAccountsOpps',true);
     }
 }