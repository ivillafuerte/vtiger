{*<!--
/* ********************************************************************************
* The content of this file is subject to the Corrensa ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
-->*}
<div class="container-fluid">
    <div class="widget_header row">
        <h3>{vtranslate('Corrensa', 'Corrensa')} Uninstall</h3>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="setting-pane active uninstall-pane">
                <form action="index.php" method="post">
                    <h4>Are you sure you want to uninstall Corrensa module?</h4>
                    <input type="hidden" value="yes" />
                    <input type="hidden" name="module" value="Corrensa" />
                    <input type="hidden" name="action" value="Uninstall" />
                    <input type="hidden" name="mode" value="approve" />
                    <br>

                    <button class="btn btn-primary" type="submit">Yes</button>
                    &nbsp;
                    &nbsp;
                    <a href="javascript: history.back();">No</a>
                </form>
            </div>
        </div>
    </div>
</div>