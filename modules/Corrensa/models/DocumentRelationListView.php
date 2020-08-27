<?php

class Contacts_RelationListView_Model extends Vtiger_RelationListView_Model {

    public function getEntries($pagingModel)
    {
        $db = PearDatabase::getInstance();
        $parentModule = $this->getParentRecordModel()->getModule();
        $relationModule = $this->getRelationModel()->getRelationModuleModel();
        $relationModuleName = $relationModule->get('name');
        $relatedColumnFields = $relationModule->getConfigureRelatedListFields();

        if(count($relatedColumnFields) <= 0){
            $relatedColumnFields = $relationModule->getRelatedListFields();
        }

        if ($relationModuleName == 'Documents') {
            $relatedColumnFields['filelocationtype'] = 'filelocationtype';
            $relatedColumnFields['filestatus'] = 'filestatus';
        }

        $query = $this->getRelationQuery();

        if ($this->get('whereCondition')) {
            $query = $this->updateQueryWithWhereCondition($query);
        }

        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();

        $orderBy = $this->getForSql('orderby');
        $sortOrder = $this->getForSql('sortorder');
        if($orderBy) {

            $orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
            if($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
                //If reference field then we need to perform a join with crmentity with the related to field
                $queryComponents = $split = spliti(' where ', $query);
                $selectAndFromClause = $queryComponents[0];
                $whereCondition = $queryComponents[1];
                $qualifiedOrderBy = 'vtiger_crmentity'.$orderByFieldModuleModel->get('column');
                $selectAndFromClause .= ' LEFT JOIN vtiger_crmentity AS '.$qualifiedOrderBy.' ON '.
                    $orderByFieldModuleModel->get('table').'.'.$orderByFieldModuleModel->get('column').' = '.
                    $qualifiedOrderBy.'.crmid ';
                $query = $selectAndFromClause.' WHERE '.$whereCondition;
                $query .= ' ORDER BY '.$qualifiedOrderBy.'.label '.$sortOrder;
            } elseif($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
                $query .= ' ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) '.$sortOrder;
            } else{
                // Qualify the the column name with table to remove ambugity
                $qualifiedOrderBy = $orderBy;
                $orderByField = $relationModule->getFieldByColumn($orderBy);
                if ($orderByField) {
                    $qualifiedOrderBy = $relationModule->getOrderBySql($qualifiedOrderBy);
                }
                $query = "$query ORDER BY $qualifiedOrderBy $sortOrder";
            }
        }

        $limitQuery = $query .' LIMIT '.$startIndex.','.$pageLimit;
        $result = $db->pquery($limitQuery, array());
        $relatedRecordList = array();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
        for($i=0; $i< $db->num_rows($result); $i++ ) {
            $row = $db->fetch_row($result,$i);
            $recordId = $db->query_result($result,$i,'crmid');
            $newRow = array();
            foreach($row as $col=>$val){
                if(array_key_exists($col,$relatedColumnFields)){
                    $newRow[$relatedColumnFields[$col]] = $val;
                }
            }
            //To show the value of "Assigned to"
            $ownerId = $row['smownerid'];
            $newRow['assigned_user_id'] = $row['smownerid'];

            $record = Vtiger_Record_Model::getCleanInstance($relationModule->get('name'));
            $record->setData($newRow)->setModuleFromInstance($relationModule);
            $record->setId($row['crmid']);
            $relatedRecordList[$row['crmid']] = $record;
        }
        $pagingModel->calculatePageRange($relatedRecordList);

        // Query total records
        $totalRecordsResult = $db->pquery($query, array());

        return array(
            'models'    => $relatedRecordList,
            'total'     => $db->num_rows($totalRecordsResult)
        );
    }
}

class Leads_RelationListView_Model extends Vtiger_RelationListView_Model {

    public function getEntries($pagingModel)
    {
        $db = PearDatabase::getInstance();
        $parentModule = $this->getParentRecordModel()->getModule();
        $relationModule = $this->getRelationModel()->getRelationModuleModel();
        $relationModuleName = $relationModule->get('name');
        $relatedColumnFields = $relationModule->getConfigureRelatedListFields();

        if(count($relatedColumnFields) <= 0){
            $relatedColumnFields = $relationModule->getRelatedListFields();
        }

        if ($relationModuleName == 'Documents') {
            $relatedColumnFields['filelocationtype'] = 'filelocationtype';
            $relatedColumnFields['filestatus'] = 'filestatus';
        }

        $query = $this->getRelationQuery();

        if ($this->get('whereCondition')) {
            $query = $this->updateQueryWithWhereCondition($query);
        }

        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();

        $orderBy = $this->getForSql('orderby');
        $sortOrder = $this->getForSql('sortorder');
        if($orderBy) {

            $orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
            if($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
                //If reference field then we need to perform a join with crmentity with the related to field
                $queryComponents = $split = spliti(' where ', $query);
                $selectAndFromClause = $queryComponents[0];
                $whereCondition = $queryComponents[1];
                $qualifiedOrderBy = 'vtiger_crmentity'.$orderByFieldModuleModel->get('column');
                $selectAndFromClause .= ' LEFT JOIN vtiger_crmentity AS '.$qualifiedOrderBy.' ON '.
                    $orderByFieldModuleModel->get('table').'.'.$orderByFieldModuleModel->get('column').' = '.
                    $qualifiedOrderBy.'.crmid ';
                $query = $selectAndFromClause.' WHERE '.$whereCondition;
                $query .= ' ORDER BY '.$qualifiedOrderBy.'.label '.$sortOrder;
            } elseif($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
                $query .= ' ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) '.$sortOrder;
            } else{
                // Qualify the the column name with table to remove ambugity
                $qualifiedOrderBy = $orderBy;
                $orderByField = $relationModule->getFieldByColumn($orderBy);
                if ($orderByField) {
                    $qualifiedOrderBy = $relationModule->getOrderBySql($qualifiedOrderBy);
                }
                $query = "$query ORDER BY $qualifiedOrderBy $sortOrder";
            }
        }

        $limitQuery = $query .' LIMIT '.$startIndex.','.$pageLimit;
        $result = $db->pquery($limitQuery, array());
        $relatedRecordList = array();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
        for($i=0; $i< $db->num_rows($result); $i++ ) {
            $row = $db->fetch_row($result,$i);
            $recordId = $db->query_result($result,$i,'crmid');
            $newRow = array();
            foreach($row as $col=>$val){
                if(array_key_exists($col,$relatedColumnFields)){
                    $newRow[$relatedColumnFields[$col]] = $val;
                }
            }
            //To show the value of "Assigned to"
            $ownerId = $row['smownerid'];
            $newRow['assigned_user_id'] = $row['smownerid'];

            $record = Vtiger_Record_Model::getCleanInstance($relationModule->get('name'));
            $record->setData($newRow)->setModuleFromInstance($relationModule);
            $record->setId($row['crmid']);
            $relatedRecordList[$row['crmid']] = $record;
        }
        $pagingModel->calculatePageRange($relatedRecordList);

        $nextLimitQuery = $query. ' LIMIT '.($startIndex+$pageLimit).' , 1';
        $nextPageLimitResult = $db->pquery($nextLimitQuery, array());
        if($db->num_rows($nextPageLimitResult) > 0){
            $pagingModel->set('nextPageExists', true);
        }else{
            $pagingModel->set('nextPageExists', false);
        }

        return $relatedRecordList;
    }


}