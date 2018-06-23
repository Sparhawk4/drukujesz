<?php /* Smarty version Smarty-3.1.19, created on 2018-06-23 18:55:04
         compiled from "/app/backend/admin/themes/default/template/helpers/list/list_action_edit.tpl" */ ?>
<?php /*%%SmartyHeaderCode:4001555505b2e7b685d8699-27868405%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd96d8772647b5e9f076565c44ae8e197df0ec1b4' => 
    array (
      0 => '/app/backend/admin/themes/default/template/helpers/list/list_action_edit.tpl',
      1 => 1527595552,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '4001555505b2e7b685d8699-27868405',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'href' => 0,
    'action' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7b685e2c58_18735229',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7b685e2c58_18735229')) {function content_5b2e7b685e2c58_18735229($_smarty_tpl) {?>
<a href="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['href']->value, ENT_QUOTES, 'UTF-8', true);?>
" title="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['action']->value, ENT_QUOTES, 'UTF-8', true);?>
" class="edit">
	<i class="icon-pencil"></i> <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['action']->value, ENT_QUOTES, 'UTF-8', true);?>

</a>
<?php }} ?>
