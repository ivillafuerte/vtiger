<?php
class API_Emails extends BaseModule
{
    public function getListEmailTemplate($request){
        $parentId = $request->get('parentId');
        $parentModule = $request->get('relatedModule');
        $user_id = $request->get('user_id');
        $adb = PearDatabase::getInstance();
        $query = "SELECT DISTINCT
                    vtiger_emailtemplates.templateid,
                    vtiger_emailtemplates.body,
                    vtiger_emailtemplates.subject
                FROM vtiger_emailtemplates 
                WHERE
                    vtiger_emailtemplates.deleted = 0";
        $result = array();
        $i = 0;
        $pquery = $adb->pquery($query, array());
        if ($adb->num_rows($pquery) > 0) {
            while ($row = $adb->fetchByAssoc($pquery)) {
                $result[$i] = $row;
                $result[$i]['body'] = decode_html($row['body']);
                $result[$i]['subject'] = decode_html($row['subject']);
                
                $mergedDescription = getMergedDescription($result[$i]['body'], $user_id, 'Users');
                $mergedSubject = getMergedDescription($result[$i]['subject'],$user_id, 'Users');

                if ($parentModule != 'Users') {
                    // Apply merge for non-Users module merge tags.
                    $result[$i]['body'] = getMergedDescription($mergedDescription, $parentId, $parentModule);
                    $result[$i]['subject'] = getMergedDescription($mergedSubject, $parentId, $parentModule);
                } else {
                    // Re-merge the description for user tags based on actual user.
                    $result[$i]['body'] = getMergedDescription($result[$i]['body'], $parentId, 'Users');
                    $result[$i]['subject'] = getMergedDescription($mergedSubject, $parentId, 'Users');
                }

                if (strpos($result[$i]['body'], '$logo$')) {
                    $result[$i]['body'] = str_replace('$logo$','<img src="'.vglobal('site_URL').'/modules/Corrensa/api.php?module=Emails&action=companyLogo" />', $result[$i]['body']);
                }
                if (strpos($result[$i]['body'], '<td> </td>')) {
                    $result[$i]['body'] = str_replace('<td> </td>','<td>&nbsp;</td>', $result[$i]['body']);
                }
                $i++;
            }
        }

        $this->displayJson($result);
    }

    public function companyLogo() {
        $file = 'layouts/vlayout/skins/images/logo_mail.jpg';

        if(file_exists($file)) {
            header("Content-type: image/jpg");
            header("Pragma: public");
            header("Cache-Control: private");
            header("Content-Disposition: attachment; filename=logo.jpg");
            header("Content-Description: PHP Generated Data");
            echo file_get_contents($file);
        }
    }
    
}