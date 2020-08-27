<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once('modules/Corrensa/libs/utils.php');
require_once 'include/Webservices/Query.php';
require_once 'include/Webservices/ModuleTypes.php';
require_once 'include/Webservices/DescribeObject.php';

/**
 * Corrensa VtigerData Model Class
 */
class Corrensa_VtigerData_Model
{

    private $adminUser          = null;
    private $loadedUsers        = null;
    private $loadedModules      = null;
    private $loadedAllFields    = null;
    private $loadedUserObjects  = null;
    private $utils              = null;

    /**
     * Corrensa_VtigerData_Model constructor.
     */
    public function __construct($adminUserId = false)
    {
        if($adminUserId !== false) {
            $adminUserId = str_replace('19x', '', $adminUserId);
            $seed_user = new Users();
            $this->adminUser = $seed_user->retrieveCurrentUserInfoFromFile($adminUserId);
        } else {
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $seed_user = new Users();
            $this->adminUser = $seed_user->retrieveCurrentUserInfoFromFile($currentUser->get('id'));
        }

        $this->utils = new CorrensaUtils();
    }


    public function getEntityModules()
    {
        $adb = new PearDatabase();
        if(!empty($this->loadedModules)) return $this->loadedModules;

        # Get entity module
//        $sql = "select name,id,ismodule from vtiger_ws_entity";
//        $res = $adb->pquery($sql, array());
//        $moduleArray = Array();
//        $entityArray = Array();
//        while($row = $adb->fetchByAssoc($res)){
//            if($row['ismodule'] == '1'){
//                array_push($moduleArray,$row['name']);
//            }else{
//                array_push($entityArray,$row['name']);
//            }
//        }
//        $types = array('module'=>$moduleArray,'entity'=>$entityArray);

        #/ Get entity module
        $types = vtws_listtypes(null, $this->adminUser);

        $modules = array();

//		$notEntityModules = array('Webmails', 'Emails', 'Integration', 'Dashboard', 'Users');

        $allowedModules = array(
            'Campaigns', 'Quotes', 'PurchaseOrder', 'SalesOrder', 'Invoice',
            'Calendar', 'Leads', 'Accounts', 'Contacts', 'Potentials',
            'Products', 'HelpDesk', 'Events', 'Users', 'Services', 'Events', 'ModComments', 'ProductTaxes', 'Emails', 'Groups', 'Currency','Documents', 'Vendors'
        );

        foreach ($types['information'] as $moduleName => $module) {
            if ($module['isEntity'] && in_array($moduleName, $allowedModules)) {
                $modules[$moduleName] = $module;
            }
        }
        $this->loadedModules = $modules;
        return $this->loadedModules;

    }

    public function getAllFields() {
        if(!empty($this->loadedAllFields)) return $this->loadedAllFields;

        $fields = array();
        $modules = $this->getEntityModules();
        foreach ($modules as $moduleName => $moduleMeta) {
            $fields[$moduleName] = $this->getFields($moduleName);
        }

        $this->loadedAllFields = $fields;
        return $this->loadedAllFields;
    }

    public function getFields($moduleName)
    {
        $moduleInfo = vtws_describe($moduleName, $this->adminUser);
        return $moduleInfo['fields'];
    }

    public function getUserCount() {
        $adb = PearDatabase::getInstance();

        $userCountRs = $adb->pquery("select count(*) total_users from vtiger_users where vtiger_users.deleted = 0 and vtiger_users.`status` = 'Active'", array());
        if($adb->num_rows($userCountRs) > 0) {
            $row = $adb->fetch_array($userCountRs);
            return $row['total_users'];
        } else {
            return false;
        }
    }

    public function getUsers()
    {
        if(!empty($this->loadedUsers)) return $this->loadedUsers;

        $users = array();
        $limitPerpage = 100;

        $userCount = vtws_query("SELECT COUNT(*) FROM Users;", $this->adminUser);

        if(isset($userCount[0]) && isset($userCount[0]['count'])) {
            $userCount = $userCount[0]['count'];
        } else {
            $userCount = count($userCount);
        }

        $pageSize = ceil($userCount / $limitPerpage);
        for ($i = 0; $i < $pageSize; $i++) {
            $startIndex = $i * $limitPerpage;

            $query = "SELECT * FROM Users LIMIT $startIndex, $limitPerpage;";
            $result = vtws_query($query, $this->adminUser);

            $users = array_merge($users, $result);

            foreach ($users as $key => $user) {
                $users[$key]['accesskey'] = '';
                $users[$key]['user_password'] = '';
                $users[$key]['user_hash'] = '';
                $users[$key]['confirm_password'] = '';
            }
        }

        $this->loadedUsers = $users;
        return $this->loadedUsers;
    }

    public function getUserById($id) {
        $id = str_replace('19x', '', $id);
        $query = "SELECT * FROM Users WHERE id = '19x{$id}';";
        $result = vtws_query($query, $this->adminUser);

        if(!empty($result)) {
            return $result[0];
        }

        return false;
    }

    public function getUserByUsername($username) {
        $query = "SELECT * FROM Users WHERE user_name = '$username';";
        $result = vtws_query($query, $this->adminUser);

        if(!empty($result)) {
            return $result[0];
        }

        return false;
    }

    public function getUserObjects() {
        $users = $this->getUsers();
        $userObjects = array();

        foreach ($users as $user) {
            $userId = str_replace('19x', '', $user['id']);
            $seed_user = new Users();
            $userObject = $seed_user->retrieveCurrentUserInfoFromFile($userId);
            $userObjects[$userId] = $userObject;
        }
        return $userObjects;
    }

    public function getUserObjectByID($userId) {
        $userId = str_replace('19x', '', $userId);
        $seed_user = new Users();
        $userObject = $seed_user->retrieveCurrentUserInfoFromFile($userId);
        return $userObject;
    }

    public function getUserPermissionByUser($userId)
    {
        $modulePermissionData = array();
        $fieldPermissionData = array();

        $userObject = $this->getUserObjectByID($userId);
        $modules     = $this->getEntityModules();
        $allFields   = $this->getAllFields();

        foreach ($modules as $moduleName => $moduleMeta) {
            try {
                $moduleInfo = vtws_describe($moduleName, $userObject);

                array_push($modulePermissionData, array(
                    'vtiger_user_id' => '19x'.$userObject->id,
                    'module' => $moduleName,
                    'createable' => intval($moduleInfo['createable']),
                    'updateable' => intval($moduleInfo['updateable']),
                    'deleteable' => intval($moduleInfo['deleteable']),
                    'retrieveable' => intval($moduleInfo['retrieveable'])
                ));

                $queriedFields = array();
                foreach ($moduleInfo['fields'] as $field) {
                    $queriedFields[$field['name']] = $field;
                }

                $allFieldOfModule = $allFields[$moduleName];

                foreach ($allFieldOfModule as $field) {
                    if(isset($queriedFields[$field['name']])) {
                        array_push($fieldPermissionData, array(
                            'vtiger_user_id' => '19x'.$userObject->id,
                            'module' => $moduleName,
                            'fieldname' => $field['name'],
                            'canedit' => $queriedFields[$field['name']]['editable'],
                            'canview' => 1
                        ));
                    } else {
                        array_push($fieldPermissionData, array(
                            'vtiger_user_id' => '19x'.$userObject->id,
                            'module' => $moduleName,
                            'fieldname' => $field['name'],
                            'canedit' => 0,
                            'canview' => 0
                        ));
                    }
                }
            } catch (Exception $ex) {
                array_push($modulePermissionData, array(
                    'vtiger_user_id' => '19x'.$userObject->id,
                    'module' => $moduleName,
                    'createable' => 0,
                    'updateable' => 0,
                    'deleteable' => 0,
                    'retrieveable' => 0
                ));

                $fields = $allFields[$moduleName];
                foreach ($fields as $field) {
                    array_push($fieldPermissionData, array(
                        'vtiger_user_id' => '19x'.$userObject->id,
                        'module' => $moduleName,
                        'fieldname' => $field['name'],
                        'canedit' => 0,
                        'canview' => 0
                    ));
                }

                logRuntimeError(E_ERROR, $ex->getMessage(), __FILE__, __LINE__);
            }
        }

        $permissionCollection = array(
            'modules' => $modulePermissionData,
            'fields' => $fieldPermissionData
        );

        return $permissionCollection;
    }

    public function getUserPermission($userId, $modules, $allFields)
    {
        $modulePermissionData = array();
        $fieldPermissionData = array();
        $buildUrl       = vglobal('site_URL');
        $userIdInt = str_replace('19x', '', $userId);
        $userModel = $this->getUserObjectByID($userIdInt);

        foreach ($modules as $moduleName => $moduleMeta) {
            try {
                $moduleInfo = vtws_describe($moduleName, $userModel);
                if($moduleInfo && isset($moduleInfo['createable'])) {
                    array_push($modulePermissionData, array(
                        'vtiger_user_id' => $userId,
                        'module' => $moduleName,
                        'createable' => intval($moduleInfo['createable']),
                        'updateable' => intval($moduleInfo['updateable']),
                        'deleteable' => intval($moduleInfo['deleteable']),
                        'retrieveable' => intval($moduleInfo['retrieveable'])
                    ));

                    $queriedFields = array();
                    foreach ($moduleInfo['fields'] as $field) {
                        $queriedFields[$field['name']] = $field;
                    }

                    $allFieldOfModule = $allFields[$moduleName];

                    foreach ($allFieldOfModule as $field) {
                        if(isset($queriedFields[$field['name']])) {
                            array_push($fieldPermissionData, array(
                                'vtiger_user_id' => $userId,
                                'module' => $moduleName,
                                'fieldname' => $field['name'],
                                'canedit' => $queriedFields[$field['name']]['editable'],
                                'canview' => 1
                            ));
                        } else {
                            array_push($fieldPermissionData, array(
                                'vtiger_user_id' => $userId,
                                'module' => $moduleName,
                                'fieldname' => $field['name'],
                                'canedit' => 0,
                                'canview' => 0
                            ));
                        }
                    }
                } else {
                    array_push($modulePermissionData, array(
                        'vtiger_user_id' => $userId,
                        'module' => $moduleName,
                        'createable' => 0,
                        'updateable' => 0,
                        'deleteable' => 0,
                        'retrieveable' => 0
                    ));

                    $fields = $allFields[$moduleName];
                    foreach ($fields as $field) {
                        array_push($fieldPermissionData, array(
                            'vtiger_user_id' => $userId,
                            'module' => $moduleName,
                            'fieldname' => $field['name'],
                            'canedit' => 0,
                            'canview' => 0
                        ));
                    }
                }
            } catch (Exception $e) {
                array_push($modulePermissionData, array(
                    'vtiger_user_id' => $userId,
                    'module' => $moduleName,
                    'createable' => 0,
                    'updateable' => 0,
                    'deleteable' => 0,
                    'retrieveable' => 0
                ));

                $fields = $allFields[$moduleName];
                foreach ($fields as $field) {
                    array_push($fieldPermissionData, array(
                        'vtiger_user_id' => $userId,
                        'module' => $moduleName,
                        'fieldname' => $field['name'],
                        'canedit' => 0,
                        'canview' => 0
                    ));
                }
            }
        }

        $permissionCollection = array(
            'modules' => $modulePermissionData,
            'fields' => $fieldPermissionData
        );

        return $permissionCollection;
    }

    public function getFieldInfoAndPermission($moduleName, $fieldName)
    {
        $fieldPermissionData = array();
        $userObjects = $this->getUserObjects();
        $fieldData = array();

        foreach ($userObjects as $userObject) {
            try {
                $moduleInfo = vtws_describe($moduleName, $userObject);

                $queriedFields = array();
                foreach ($moduleInfo['fields'] as $field) {
                    $queriedFields[$field['name']] = $field;
                }

                $fieldData = $queriedFields[$fieldName];

                if(isset($queriedFields[$fieldName])) {
                    array_push($fieldPermissionData, array(
                        'vtiger_user_id' => '19x'.$userObject->id,
                        'module' => $moduleName,
                        'fieldname' => $fieldName,
                        'canedit' => $queriedFields[$fieldName]['editable'],
                        'canview' => 1
                    ));
                } else {
                    array_push($fieldPermissionData, array(
                        'vtiger_user_id' => '19x'.$userObject->id,
                        'module' => $moduleName,
                        'fieldname' => $fieldName,
                        'canedit' => 0,
                        'canview' => 0
                    ));
                }
            } catch (Exception $ex) {
                array_push($fieldPermissionData, array(
                    'vtiger_user_id' => '19x'.$userObject->id,
                    'module' => $moduleName,
                    'fieldname' => $fieldName,
                    'canedit' => 0,
                    'canview' => 0
                ));

                logRuntimeError(E_ERROR, $ex->getMessage(), __FILE__, __LINE__);
            }
        }

        return array(
            'field' => $fieldData,
            'permission' => $fieldPermissionData
        );
    }

    public function getCurrencies()
    {
        $query = "SELECT * FROM Currency;";
        $result = vtws_query($query, $this->adminUser);

        return $result;
    }

    public function getTaxes()
    {
        $query = "SELECT * FROM Tax;";
        $result = vtws_query($query, $this->adminUser);

        return $result;
    }

    public function getFieldByLabel($label, $module) {
        global $adb;

        $query = "SELECT * FROM vtiger_field field
				 INNER JOIN vtiger_tab tab on field.tabid = tab.tabid
				 WHERE field.fieldlabel = ? AND tab.name = ?";
        $result = $adb->pquery($query, array($label, $module));

        if($adb->num_rows($result) > 0) {
            return $adb->fetchByAssoc($result);
        } else {
            return false;
        }
    }

//	public static function get

}