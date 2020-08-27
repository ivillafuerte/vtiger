
<div class="summaryWidgetContainer">
{assign var=RELATED_MODULE_NAME value=Vtiger_Functions::getModuleName($WIDGET['data']['relatedmodule'])}
	<div class="widgetContainer_{$key}" data-url="{$WIDGET['url']}" data-name="{$WIDGET['label']}">
		 <div class="widget_header row-fluid">
			<input type="hidden" name="relatedModule" value="{$RELATED_MODULE_NAME}" />
			<span class="span10 margin0px">
				<div class="row-fluid">
					<span class="span8 margin0px"><h4>{vtranslate($WIDGET['label'],$MODULE_NAME)}</h4></span>
				</div>
			</span>
			 {if $WIDGET['data']['action'] eq 1}
				{assign var=VRM value=Vtiger_Record_Model::getInstanceById($RECORD->getId(), $MODULE_NAME)}
				{assign var=VRMM value=Vtiger_RelationListView_Model::getInstance($VRM, $RELATED_MODULE_NAME)}
				{assign var=RELATIONMODEL value=$VRMM->getRelationModel()}
				{assign var=RELATION_FIELD value=$RELATIONMODEL->getRelationField()}
				<span class="span2">
					<span class="pull-right">
						<button class="btn addButton pull-right vteWidgetCreateButton" type="button" href="javascript:void(0)"
                               data-url="{$WIDGET['actionURL']}" data-name="{$RELATED_MODULE_NAME}"
						{if $RELATION_FIELD} data-prf="{$RELATION_FIELD->getName()}" {/if}>
							<strong>{vtranslate('LBL_ADD',$MODULE_NAME)}</strong>
						</button>
					</span>
				</span>
			{/if}
		</div>
		<div class="widget_contents">
		</div>
	</div>
</div>