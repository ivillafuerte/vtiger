<?php /* Smarty version Smarty-3.1.19, created on 2019-06-25 00:11:45
         compiled from "/var/www/vhosts/stackfire.com/crm.stackfire.com/tickets/layouts/default/templates/Project/partials/DetailContentBefore.tpl" */ ?>
<?php /*%%SmartyHeaderCode:14155446395d1166c15ad027-62806762%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '4d4dc0cf37d7c3f87d6888ce8f3f7fe8d043c86b' => 
    array (
      0 => '/var/www/vhosts/stackfire.com/crm.stackfire.com/tickets/layouts/default/templates/Project/partials/DetailContentBefore.tpl',
      1 => 1561415591,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '14155446395d1166c15ad027-62806762',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5d1166c15dcb38_21290729',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5d1166c15dcb38_21290729')) {function content_5d1166c15dcb38_21290729($_smarty_tpl) {?>


<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 ticket-detail-header-row ">
  <h3 class="fsmall">
    <detail-navigator>
      <span>
        <a ng-click="navigateBack(module)" style="font-size:small;">{{ptitle}}
        </a>
      </span>
    </detail-navigator>
    {{record[header]}}
  <button ng-if="documentsEnabled" translate="Attach document to this project" class="btn btn-primary attach-files-ticket" ng-click="attachDocument('Documents','LBL_ADD_DOCUMENT')"></button></h3>
</div>
</div>
<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

<script type="text/javascript" src="<?php echo portal_componentjs_file('Documents');?>
"></script>
<?php echo $_smarty_tpl->getSubTemplate (portal_template_resolve('Documents',"partials/IndexContentAfter.tpl"), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<?php }} ?>
