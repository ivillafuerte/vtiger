<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class Corrensa_EmailAjax_Action extends Vtiger_BasicAjax_Action
{
    function __construct()
    {
        parent::__construct();
        $this->exposeMethod('getPotentialEmails');
        $this->exposeMethod('getEmailPageCount');
        $this->exposeMethod('getRelatedEmail');
    }

    private function displayJson($data)
    {
        header('Content-type: text/json; charset=UTF-8');
        echo Zend_Json::encode($data);
    }

    function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
            return;
        }
    }

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    function getViewer(Vtiger_Request $request)
    {
        if(!$this->viewer) {
            global $vtiger_current_version;
            $viewer = new Vtiger_Viewer();
            $viewer->assign('APPTITLE', getTranslatedString('APPTITLE'));
            $viewer->assign('VTIGER_VERSION', $vtiger_current_version);
            $this->viewer = $viewer;
        }
        return $this->viewer;
    }


//    function getPotentialEmails() {
//        $moduleName = "Potentials";
//        $relatedModuleName = "Emails";
//        $parentId = 139;
//        $label = "Emails";
//        $requestedPage = 1;
//        if(empty ($requestedPage)) {
//            $requestedPage = 1;
//        }
//
//        $pagingModel = new Vtiger_Paging_Model();
//        $pagingModel->set('page',$requestedPage);
//
//        $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
//        $relationListView = Corrensa_PotentialEmailListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
//
//        $models = $relationListView->getEntries($pagingModel);
//        $links = $relationListView->getLinks();
//        $header = $relationListView->getHeaders();
//        $noOfEntries = count($models);
//
//        $data = array('data' => array(), 'header' => array(), 'total' => $noOfEntries);
//
//        foreach ($models as $model) {
//            $data['data'][] = $model->getData();
//        }
//
//        foreach ($header as $item) {
//            $data['header'][] = $item->label;
//        }
//
//        $this->displayJson($data);
//    }

    public function getEmailPageCount($request) {
        $moduleName = $request->get('mainModule');
        $relatedModuleName = $request->get('relatedModule');
        $parentId = $request->get('record');
        $label = $request->get('tab_label');
        $pagingModel = new Vtiger_Paging_Model();
        $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
        $relationListView = Corrensa_PotentialEmailListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
        $totalCount = $relationListView->getRelatedEntriesCount();
        $pageLimit = $pagingModel->getPageLimit();
        $pageCount = ceil((int) $totalCount / (int) $pageLimit);

        if($pageCount == 0){
            $pageCount = 1;
        }
        $result = array();
        $result['numberOfRecords'] = $totalCount;
        $result['page'] = $pageCount;
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }

    public function getRelatedEmail($request) {
        $moduleName = $request->get('mainModule');
        $relatedModuleName = "Emails";
        $parentId = $request->get('record');
        $label = $request->get('tab_label');
        $requestedPage = $request->get('page');
        if(empty ($requestedPage)) {
            $requestedPage = 1;
        }

        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page',$requestedPage);

        $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
        $relationListView = Corrensa_PotentialEmailListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
        $orderBy = $request->get('orderby');
        $sortOrder = $request->get('sortorder');
        if($sortOrder == "ASC") {
            $nextSortOrder = "DESC";
            $sortImage = "icon-chevron-down";
        } else {
            $nextSortOrder = "ASC";
            $sortImage = "icon-chevron-up";
        }
        if(!empty($orderBy)) {
            $relationListView->set('orderby', $orderBy);
            $relationListView->set('sortorder',$sortOrder);
        }
        $models = $relationListView->getEntries($pagingModel);
        $links = $relationListView->getLinks();
        $header = $relationListView->getHeaders();
        $noOfEntries = count($models);

        $relationModel = $relationListView->getRelationModel();
        $relatedModuleModel = $relationModel->getRelationModuleModel();
        $relationField = $relationModel->getRelationField();

        $viewer = $this->getViewer($request);
        $viewer->assign('RELATED_RECORDS' , $models);
        $viewer->assign('PARENT_RECORD', $parentRecordModel);
        $viewer->assign('RELATED_LIST_LINKS', $links);
        $viewer->assign('RELATED_HEADERS', $header);
        $viewer->assign('RELATED_MODULE', $relatedModuleModel);
        $viewer->assign('RELATED_ENTIRES_COUNT', $noOfEntries);
        $viewer->assign('RELATION_FIELD', $relationField);

        if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
            $totalCount = $relationListView->getRelatedEntriesCount();
            $pageLimit = $pagingModel->getPageLimit();
            $pageCount = ceil((int) $totalCount / (int) $pageLimit);

            if($pageCount == 0){
                $pageCount = 1;
            }
            $viewer->assign('PAGE_COUNT', $pageCount);
            $viewer->assign('TOTAL_ENTRIES', $totalCount);
            $viewer->assign('PERFORMANCE', true);
        }

        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('PAGING', $pagingModel);

        $viewer->assign('ORDER_BY',$orderBy);
        $viewer->assign('SORT_ORDER',$sortOrder);
        $viewer->assign('NEXT_SORT_ORDER',$nextSortOrder);
        $viewer->assign('SORT_IMAGE',$sortImage);
        $viewer->assign('COLUMN_NAME',$orderBy);
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('IS_EDITABLE', $relationModel->isEditable());
        $viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('VIEW', $request->get('view'));

        echo $viewer->view('EmailRelatedList.tpl', $moduleName, 'true');
    }

}