<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Corrensa_PotentialEmailListView_Model extends Vtiger_RelationListView_Model {

    /**
     * Function to get Relation query
     * @return <String>
     */
    public function getRelationQuery() {
        $parentRecordId = $this->getParentRecordModel()->getId();

        $query = "";

        if($this->parentRecordModel->getModule()->get('name') == 'Potentials') {
            $query = "SELECT CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN CONCAT(vtiger_users.first_name,' ',vtiger_users.last_name) ELSE vtiger_groups.groupname END AS user_name, 
vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.activitytype, vtiger_crmentity.modifiedtime, 
vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start, vtiger_activity.time_start, vtiger_seactivityrel.crmid AS parent_id
FROM vtiger_activity, vtiger_seactivityrel, vtiger_potential, vtiger_users, vtiger_crmentity
LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid
WHERE vtiger_seactivityrel.activityid = vtiger_activity.activityid AND vtiger_potential.potentialid = vtiger_seactivityrel.crmid 
AND vtiger_users.id=vtiger_crmentity.smownerid AND vtiger_crmentity.crmid = vtiger_activity.activityid AND vtiger_potential.potentialid = '$parentRecordId' 
AND vtiger_activity.activitytype='Emails' AND vtiger_crmentity.deleted = 0";
        } else if($this->parentRecordModel->getModule()->get('name') == 'HelpDesk') {
            $query = "SELECT CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN CONCAT(vtiger_users.first_name,' ',vtiger_users.last_name) ELSE vtiger_groups.groupname END AS user_name, 
vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.activitytype, vtiger_crmentity.modifiedtime, 
vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start, vtiger_activity.time_start, vtiger_seactivityrel.crmid AS parent_id
FROM vtiger_activity, vtiger_seactivityrel, vtiger_troubletickets, vtiger_users, vtiger_crmentity
LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid
WHERE vtiger_seactivityrel.activityid = vtiger_activity.activityid AND vtiger_troubletickets.ticketid = vtiger_seactivityrel.crmid 
AND vtiger_users.id=vtiger_crmentity.smownerid AND vtiger_crmentity.crmid = vtiger_activity.activityid AND vtiger_troubletickets.ticketid = '$parentRecordId' 
AND vtiger_activity.activitytype='Emails' AND vtiger_crmentity.deleted = 0";
//            exit($query);
        }
//exit($query);
        return $query;
    }

    public static function getInstance($parentRecordModel, $relationModuleName, $label=false) {
        $parentModuleName = $parentRecordModel->getModule()->get('name');
        $className = "Corrensa_PotentialEmailListView_Model";
        $instance = new $className();

        $parentModuleModel = $parentRecordModel->getModule();
        $relatedModuleModel = Vtiger_Module_Model::getInstance($relationModuleName);
        $instance->setRelatedModuleModel($relatedModuleModel);

        $relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModuleModel, $label);
        $instance->setParentRecordModel($parentRecordModel);

        if(!$relationModel){
            $relatedModuleName = $relatedModuleModel->getName();
            $parentModuleModel = $instance->getParentRecordModel()->getModule();
            $referenceFieldOfParentModule = $parentModuleModel->getFieldsByType('reference');
            foreach ($referenceFieldOfParentModule as $fieldName=>$fieldModel) {
                $refredModulesOfReferenceField = $fieldModel->getReferenceList();
                if(in_array($relatedModuleName, $refredModulesOfReferenceField)){
                    $relationModelClassName = Vtiger_Loader::getComponentClassName('Model', 'Relation', $parentModuleModel->getName());
                    $relationModel = new $relationModelClassName();
                    $relationModel->setParentModuleModel($parentModuleModel)->setRelationModuleModel($relatedModuleModel);
                    $parentModuleModel->set('directRelatedFieldName',$fieldModel->get('column'));
                }
            }
        }
        if(!$relationModel){
            $relationModel = false;
        }
        $instance->setRelationModel($relationModel);
        return $instance;
    }

}