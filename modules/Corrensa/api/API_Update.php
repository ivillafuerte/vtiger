<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once ('modules/Corrensa/api/BaseModule.php');
require_once ('modules/Vtiger/models/Paging.php');
require_once ('modules/Vtiger/models/Record.php');
require_once ('modules/ModTracker/models/Record.php');

class API_Update extends BaseModule {

    /**
     * @param Vtiger_Request $request
     */
    function getUpdatedRecords(Vtiger_Request $request) {

        $recordId   = $request->get('recordId');
        $pageNumber = $request->get('page');
        $limit      = $request->get('limit');
        $moduleName = $request->getModule();

        if(empty($pageNumber)) {
            $pageNumber = 1;
        }

        $relatedModuleName = Vtiger_Functions::getCRMRecordType($recordId);

        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', 1);
        if(!empty($limit)) {
            $pagingModel->set('limit', 10);
        }
        $pagingModel->set('limit', 10);

        if(Vtiger_Version::check('7.0.0', '>=')) {
            $recentActivities = ModTracker_Record_Model::getUpdates($recordId, $pagingModel, $relatedModuleName);
        } else {
            $recentActivities = ModTracker_Record_Model::getUpdates($recordId, $pagingModel);
        }

        $pagingModel->calculatePageRange($recentActivities);

        if(!empty($recentActivities))
        {
            foreach ($recentActivities as $key => $recentActivity){
                $proceed = True;
                if ($recentActivity -> isRelationLink() )
                {
                    $relation = $recentActivity -> getRelationInstance();
                    if (!$relation ->getLinkedRecord())
                    {
                        $proceed = False;
                    }
                }
                if($proceed) {
                    if ($recentActivity->isCreate()) {
                        $result['data'][$key]['action'] = 'created';
                        $result['data'][$key]['time'] = $recentActivity->getParent()->get('createdtime');
                        $result['data'][$key]['by_user'] = trim($recentActivity->getModifiedBy()->getName());
                    }
                    elseif ($recentActivity->isUpdate()) {
                        $result['data'][$key]['action'] = 'updated';
                        foreach ($recentActivity->getFieldInstances() as $fieldModel) {
                            if ($fieldModel && $fieldModel->getFieldInstance() && $fieldModel->getFieldInstance()->isViewable() && $fieldModel->getFieldInstance()->getDisplayType() != '5') {
                                $result['data'][$key]['record']['fieldLabel'][] = vtranslate($fieldModel->getName() ,$moduleName);
                                if ($fieldModel->get('prevalue') != '' && $fieldModel->get('postvalue') != '' &&   !($fieldModel->getFieldInstance()->getFieldDataType() == 'reference' && ($fieldModel->get('postvalue') == '0' || $fieldModel->get('prevalue') == '0'))){
                                    $result['data'][$key]['record']['from'][] = $fieldModel->get('prevalue');
                                }
                                elseif ($fieldModel->get('postvalue') == '' || ($fieldModel->getFieldInstance()->getFieldDataType() == 'reference' && $fieldModel->get('postvalue') == '0')){
                                    $result['data'][$key]['record']['to'][] = $fieldModel->get('prevalue');
                                    $result['data'][$key]['record']['who'][] = $recentActivity->getActivityTime();
                                }

                                if ($fieldModel->get('postvalue') != '' && !($fieldModel->getFieldInstance()->getFieldDataType() == 'reference' && $fieldModel->get('postvalue') == '0'))
                                {
                                    $result['data'][$key]['record']['to'][] = $fieldModel->get('postvalue');
                                    $result['data'][$key]['time'] = $recentActivity->getActivityTime();
                                    $result['data'][$key]['by_user'] = trim($recentActivity->getModifiedBy()->getName());
                                }
                            }
                        }
                    }
                    elseif ($recentActivity -> isRelationLink() || $recentActivity -> isRelationUnLink()) {
                        $relation = $recentActivity->getRelationInstance();
                        if ($recentActivity -> isRelationLink())
                        {
                            $result['data'][$key]['action'] = 'added';

                        }
                        elseif ($recentActivity -> isRelationUnLink())
                        {
                            $result['data'][$key]['action'] = 'removed';
                        }
                        $result['data'][$key]['record']['type'] = vtranslate($relation->getLinkedRecord()->getModuleName(), $relation->getLinkedRecord()->getModuleName());
                        $result['data'][$key]['record']['name'] = $relation->getLinkedRecord()->getName();
                        $result['data'][$key]['time'] = $relation->get('changedon');
                        $result['data'][$key]['by_user'] = $recentActivity->getModifiedBy()->getName();
                    }
                    $result['success'] = true;
                    $result['size'] = count($result['data']);
                }
                else
                {
                    $result['success'] = false;
                }
            }
        }
        else {
            $result['success'] = false;
        }
        //print_r($result);
        echo json_encode($result);
    }
}