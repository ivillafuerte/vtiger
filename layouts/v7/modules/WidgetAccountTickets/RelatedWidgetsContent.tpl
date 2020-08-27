<div class="relatedContents contents-bottomscroll">
    <table  class="table table-bordered listViewEntriesTable">
        <thead>
        <tr class="listViewHeaders">
            {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                <th {if $HEADER_FIELD@last} colspan="2" {/if} nowrap>
                        {vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE_MODEL->get('name'))}
                </th>
            {/foreach}
        </tr>
        </thead>
        {foreach item=RELATED_RECORD from=$RELATED_RECORDS}
            <tr class="listViewEntries1" data-id='{$RELATED_RECORD->getId()}'
                            data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'>
                {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                    {assign var=RELATED_HEADERNAME value=$HEADER_FIELD->get('name')}
                    <td class="{$WIDTHTYPE}" data-field-type="{$HEADER_FIELD->getFieldDataType()}" nowrap>
                            {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                    </td>
                {/foreach}
            </tr>
        {/foreach}

    </table>
</div>


