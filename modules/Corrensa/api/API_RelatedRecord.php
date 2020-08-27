<?php
require_once 'include/Webservices/Utils.php';
require_once 'include/Webservices/Query.php';
require_once 'modules/Corrensa/Config.php';
require_once 'modules/Corrensa/libs/utils.php';

class API_RelatedRecord extends BaseModule
{
    function summaryRelatedList($request){
        global $adb;
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $recordId = $request->get('mr');
        $relatedModules = array(
            'Potentials',
            'Quotes',
            'Invoice',
            'SalesOrder',
            'HelpDesk'
        );

        foreach ($relatedModules as $relatedModule) {
            $fieldsModuleRelSql = "SELECT `vtiger_field`.`fieldname`,`vtiger_fieldmodulerel`.`relmodule`
                                   FROM `vtiger_fieldmodulerel`
                                   INNER JOIN `vtiger_field`
                                   ON `vtiger_field`.`fieldid` = `vtiger_fieldmodulerel`.`fieldid`
                                   WHERE `vtiger_fieldmodulerel`.`module` = ?";
            $resultFieldModuleRel = $adb->pquery($fieldsModuleRelSql, array($relatedModule));
            $relModuleFields = array();
            foreach ($resultFieldModuleRel as $item) {
                $relModuleFields[$item['relmodule']] = $item['fieldname'];
            }

            $relatedQuery = "SELECT * FROM $relatedModule WHERE contact_id = '$recordId' ORDER BY createdtime DESC LIMIT 0,5;";
            $relatedResult = vtws_query($relatedQuery,$currentUser);

            foreach ($relatedResult as $key=>$item){
                foreach ($relModuleFields as $relModuleName=>$field){
                    if (!empty($item[$field])){
                        $relRecordRelated = end(explode('x',$item[$field]));
                        $relRecordModel = Vtiger_Record_Model::getInstanceById($relRecordRelated,$relModuleName);
                        $relatedResult[$key][$field] = $relRecordModel->getName();
                    }

                }
            }
            $result[strtolower($relatedModule)] = $relatedResult;
        }
        $this->displayJson($result);
    }
}