<?php /* Smarty version Smarty-3.1.19, created on 2018-06-23 18:55:04
         compiled from "/app/backend/admin/themes/default/template/helpers/tree/tree_node_item.tpl" */ ?>
<?php /*%%SmartyHeaderCode:6506598975b2e7b68ef1564-51715354%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '8670b75d8c40107bcbc636ce3ab3ea73ec45de70' => 
    array (
      0 => '/app/backend/admin/themes/default/template/helpers/tree/tree_node_item.tpl',
      1 => 1527595552,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '6506598975b2e7b68ef1564-51715354',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'node' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7b68ef83d8_85137136',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7b68ef83d8_85137136')) {function content_5b2e7b68ef83d8_85137136($_smarty_tpl) {?>

<li class="tree-item">
	<span class="tree-item-name">
		<i class="tree-dot"></i>
		<label class="tree-toggler"><?php echo $_smarty_tpl->tpl_vars['node']->value['name'];?>
</label>
	</span>
</li>
<?php }} ?>
