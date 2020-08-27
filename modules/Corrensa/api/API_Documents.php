<?php

/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once 'modules/Corrensa/libs/CorrensaCrypter.php';
require_once 'include/Webservices/Utils.php';
require_once 'modules/Corrensa/models/DocumentRelationListView.php';
require_once 'modules/Corrensa/models/DocumentModuleModel.php';
require_once 'modules/Corrensa/Config.php';
require_once 'modules/Corrensa/libs/utils.php';

class API_Documents extends BaseModule
{
    private $crypter = null;
    private $key = '';
    private $timeout = 600;
    private $utils = null;

    public function __construct()
    {
        $this->key = Corrensa_Config::CORRENSA_ENCRYPT_KEY;
        $this->crypter = new CorrensaCrypter();
        $this->utils = new CorrensaUtils();
    }

    function getRelatedDocuments($request)
    {
        $siteURL = vglobal('site_URL');
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', $request->get('page'));
        $pagingModel->set('limit', $request->get('limit'));

        $parentId = $request->get('recordId');

        $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, 'Contacts'/*Need to change*/);
        $relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, 'Documents', 'Documents');

        $orderBy = 'createdtime';
        $sortOrder = 'DESC';

        if (!empty($orderBy)) {
            $relationListView->set('orderby', $orderBy);
            $relationListView->set('sortorder', $sortOrder);
        }

        $result = $relationListView->getEntries($pagingModel);
        $models = $result['models'];

        $docs = array();

        foreach ($models as $model) {
            $doc = array(
                'id' => $model->get('id'),
                'crmid' => $parentId,
                'title' => $model->get('notes_title'),
                'filesize' => $model->get('filesize'),
                'modifiedtime' => $model->get('modifiedtime'),
                'filename' => $model->get('filename'),
                'filestatus' => $model->get('filestatus'),
                'filelocationtype' => $model->get('filelocationtype'),
                'linkdownload' => '',
            );

            if ($doc['filesize'] > 0) {
                $downloadId = $doc['id'];
                $downloadId = $this->crypter->encrypt($downloadId, $this->key);
                $time = $this->crypter->encrypt(time(), $this->key);
                $downloadId = base64_encode($downloadId);
                $time = base64_encode($time);
                $doc['linkdownload'] = $siteURL . "/modules/Corrensa/api.php?module=Documents&action=Download&d=$downloadId&t=$time";
            } else if ($doc['filesize'] == 0 && $doc['filelocationtype'] == 'E') {
                $doc['linkdownload'] = $doc['filename'];
            } else {
                $doc['linkdownload'] = "";
            }

            array_push($docs, $doc);
        }

        $docs['total_records'] = $result['total'];

        $this->displayJson($docs);
    }

    function getDocumentDetail($request)
    {
        $parentId = $request->get('recordId');
        $documentId = $request->get('documentId');

        $recordPermission = Users_Privileges_Model::isPermitted('Documents', 'DetailView', $documentId);
        if (!$recordPermission) {
            $this->displayError(vtranslate('LBL_PERMISSION_DENIED'));
        }

        if (empty($parentId)) {
            $this->displayError('Invalid ParentID');
        }

        if (empty($documentId)) {
            $this->displayError('Invalid DocumentID');
        }

        $doc = Documents_Record_Model::getInstanceById($documentId);

        $result = array(
            'id' => $doc->get('id'),
            'title' => $doc->get('notes_title'),
            'notecontent' => $doc->get('notecontent'),
            'smownerid' => $doc->get('assigned_user_id'),
            'modifiedtime' => $doc->get('modifiedtime'),
            'createdtime' => $doc->get('createdtime'),
        );

        $this->displayJson($result);
    }


    function Download($request)
    {
        $adb = PearDatabase::getInstance();

        $documentId = $request->get('d');
        $time = $request->get('t');

        $documentId = base64_decode($documentId);
        $time = base64_decode($time);

        $documentId = CorrensaCrypter::decrypt($documentId, $this->key);
        $time = CorrensaCrypter::decrypt($time, $this->key);

        if (empty($documentId) || empty($time)) {
            exit("Invalid url");
        }

        $timeNow = time();

        $timeOffset = $timeNow - intval($time);

        if ($timeOffset > $this->timeout) {
            exit("Timeout");
        }

        $fileDetails = array();

        $result = $adb->pquery("SELECT *,vtiger_notes.filelocationtype FROM vtiger_attachments
                        INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
                        INNER JOIN vtiger_notes ON vtiger_notes.notesid = vtiger_seattachmentsrel.crmid
                        WHERE crmid = ?", array($documentId));

        if ($adb->num_rows($result)) {
            $fileDetails = $adb->query_result_rowdata($result);
        }


        // Get attachment info

        $fileContent = false;

        if (!empty ($fileDetails)) {
            $filePath = $fileDetails['path'];
            $fileName = $fileDetails['name'];
            $fileType = $fileDetails['filelocationtype'];
            if ($fileType == 'I') {
                $fileName = html_entity_decode($fileName, ENT_QUOTES, vglobal('default_charset'));
                $savedFile = $fileDetails['attachmentsid'] . "_" . $fileName;

                $fileSize = filesize($filePath . $savedFile);
                $fileSize = $fileSize + ($fileSize % 1024);

                if (fopen($filePath . $savedFile, "r")) {
                    $fileContent = fread(fopen($filePath . $savedFile, "r"), $fileSize);
                    header("Content-type: " . $fileDetails['type']);
                    header("Pragma: public");
                    header("Cache-Control: private");
                    header("Content-Disposition: attachment; filename=$fileName");
                    header("Content-Description: PHP Generated Data");

                }
            }
        }
        echo $fileContent;
    }

    public function saveRecordDocument($request)
    {
        $userId = $request->get('user_id');
        $filename = $request->get('file_name');
        $type = $request->get('file_type');
        $length = $request->get('file_length');
        $fileContent = $_POST['file_content'];
        $tmpUploadDir = ini_get('upload_tmp_dir');

        $tmpName = $tmpUploadDir . '/php7283647.tmp';

        $_FILES['filename'] = array(
            'name' => $filename,
            'type' => $type,
            'tmp_name' => $tmpName,
            'error' => 0,
            'size' => intval($length)
        );

        file_put_contents($tmpName, $fileContent);

        $recordModel = $this->getRecordModelFromRequest($request);

        require_once("modules/Vtiger/models/Module.php");
        //        $Vtiger_Module_Model = new Vtiger_Module_Model();
        $result = $this->_saveRecord($recordModel);

        $documentId = $result->get('id');

        $adb = PearDatabase::getInstance();

        $upload_file_path = decideFilePath();

        $current_id = $adb->getUniqueID("vtiger_crmentity");

        file_put_contents($upload_file_path . $current_id . "_" . $filename, $fileContent);
        //upload the file in server
        //        $upload_status = move_uploaded_file($filetmp_name, $upload_file_path . $userId . "_" . $filename);
        $date_var = date("Y-m-d H:i:s");
        //This is only to update the attached filename in the vtiger_notes vtiger_table for the Notes module
        $sql1 = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?, ?)";
        $params1 = array($current_id, $userId, $request->get('assigned_user_id'), $request->get('module') . " Attachment", '', $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
        $adb->pquery($sql1, $params1);

        $sql2 = "insert into vtiger_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
        $params2 = array($current_id, $filename, '', $type, $upload_file_path);
        $result = $adb->pquery($sql2, $params2);

        $query = "delete from vtiger_seattachmentsrel where crmid = ?";
        $qparams = array($documentId);
        $adb->pquery($query, $qparams);

        $sql3 = 'insert into vtiger_seattachmentsrel values(?,?)';
        $adb->pquery($sql3, array($documentId, $current_id));

        $sql4 = "insert into vtiger_senotesrel(crmid,notesid) values(?, ?)";
        $params4 = array($userId, $documentId);

        $adb->pquery($sql4, $params4);
        $this->displayJson($result);

    }

    public function saveRecordDocumentFromAttachment($request)
    {
        $relatedIds = $request->get('relatedIds');
        $filename = $request->get('file_name');
        $type = $request->get('file_type');
        $length = $request->get('file_length');
        $fileContent = $_POST['file_content'];
        $tmpUploadDir = ini_get('upload_tmp_dir');

        $tmpName = $tmpUploadDir . '/php7283647.tmp';

        $relatedIds = explode(',', $relatedIds);

        $_FILES['filename'] = array(
            'name' => $filename,
            'type' => $type,
            'tmp_name' => $tmpName,
            'error' => 0,
            'size' => intval($length)
        );

        file_put_contents($tmpName, $fileContent);

        $recordModel = $this->getRecordModelFromRequest($request);

        require_once("modules/Vtiger/models/Module.php");
        //        $Vtiger_Module_Model = new Vtiger_Module_Model();
        $result = $this->_saveRecord($recordModel);

        $documentId = $result->get('id');

        $adb = PearDatabase::getInstance();

        $upload_file_path = decideFilePath();

        $current_id = $adb->getUniqueID("vtiger_crmentity");

        file_put_contents($upload_file_path . $current_id . "_" . $filename, $fileContent);
        //upload the file in server
        //        $upload_status = move_uploaded_file($filetmp_name, $upload_file_path . $userId . "_" . $filename);
        $date_var = date("Y-m-d H:i:s");
        //This is only to update the attached filename in the vtiger_notes vtiger_table for the Notes module
        $sql1 = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?, ?)";
        $params1 = array($current_id, $request->get('assigned_user_id'), $request->get('assigned_user_id'), $request->get('module') . " Attachment", '', $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
        $adb->pquery($sql1, $params1);

        $sql2 = "insert into vtiger_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
        $params2 = array($current_id, $filename, '', $type, $upload_file_path);
        $result = $adb->pquery($sql2, $params2);

        $query = "delete from vtiger_seattachmentsrel where crmid = ?";
        $qparams = array($documentId);
        $adb->pquery($query, $qparams);

        $sql3 = 'insert into vtiger_seattachmentsrel values(?,?)';
        $adb->pquery($sql3, array($documentId, $current_id));

        foreach ($relatedIds as $relatedId) {
            $sql4 = "insert into vtiger_senotesrel(crmid,notesid) values(?, ?)";
            $params4 = array($relatedId, $documentId);
            $adb->pquery($sql4, $params4);
        }

        $this->displayJson($result);
    }

    public function getMaxSizeUpload()
    {
        $size = $this->utils->file_upload_max_size();

        if(empty($size) || $size == 0) {
            $this->displayError("Could not get memory limit");
        }

        $this->displayJson(array('size' => $size));
    }

    protected function getRecordModelFromRequest($request)
    {
        $moduleName = $request->get('module');
        $recordId = $request->get('record');

        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            $modelData = $recordModel->getData();
            $recordModel->set('id', $recordId);
            $recordModel->set('mode', 'edit');
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $modelData = $recordModel->getData();
            $recordModel->set('mode', '');
        }

        $fieldModelList = $moduleModel->getFields();
        foreach ($fieldModelList as $fieldName => $fieldModel) {
            $fieldValue = $request->get($fieldName);
            $fieldDataType = $fieldModel->getFieldDataType();
            if ($fieldDataType == 'time') {
                $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
            }
            if ($fieldValue !== null) {
                if (!is_array($fieldValue)) {
                    $fieldValue = trim($fieldValue);
                }
                $recordModel->set($fieldName, $fieldValue);
            }
        }
        return $recordModel;
    }

    private function _saveRecord($recordModel)
    {
        $moduleName = 'Documents';
        $focus = CRMEntity::getInstance($moduleName);
        $fields = $focus->column_fields;
        foreach ($fields as $fieldName => $fieldValue) {
            $fieldValue = $recordModel->get($fieldName);
            if (is_array($fieldValue)) {
                $focus->column_fields[$fieldName] = $fieldValue;
            } else if ($fieldValue !== null) {
                $focus->column_fields[$fieldName] = decode_html($fieldValue);
            }
        }
        $focus->mode = $recordModel->get('mode');
        $focus->id = $recordModel->getId();
        $focus->save($moduleName);
        return $recordModel->setId($focus->id);
    }
}
