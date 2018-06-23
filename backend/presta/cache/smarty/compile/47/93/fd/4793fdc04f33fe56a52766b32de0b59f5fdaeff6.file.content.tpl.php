<?php /* Smarty version Smarty-3.1.19, created on 2018-06-23 18:54:59
         compiled from "/app/backend/admin/themes/default/template/content.tpl" */ ?>
<?php /*%%SmartyHeaderCode:753000495b2e7b63b474e2-42440851%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '4793fdc04f33fe56a52766b32de0b59f5fdaeff6' => 
    array (
      0 => '/app/backend/admin/themes/default/template/content.tpl',
      1 => 1527595552,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '753000495b2e7b63b474e2-42440851',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'content' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7b63b57057_50439243',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7b63b57057_50439243')) {function content_5b2e7b63b57057_50439243($_smarty_tpl) {?>
<div id="ajax_confirmation" class="alert alert-success hide"></div>

<div id="ajaxBox" style="display:none"></div>


<div class="row">
	<div class="col-lg-12">
		<?php if (isset($_smarty_tpl->tpl_vars['content']->value)) {?>
			<?php echo $_smarty_tpl->tpl_vars['content']->value;?>

		<?php }?>
	</div>
</div>
<?php }} ?>
