<?php /* Smarty version Smarty-3.1.19, created on 2018-06-23 18:55:01
         compiled from "/app/backend/admin/themes/default/template/controllers/modules/warning_module.tpl" */ ?>
<?php /*%%SmartyHeaderCode:6787253685b2e7b65804fb9-37792901%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '3449b4c13b1d9903505e208ffcb2a9b8c1b88256' => 
    array (
      0 => '/app/backend/admin/themes/default/template/controllers/modules/warning_module.tpl',
      1 => 1527595552,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '6787253685b2e7b65804fb9-37792901',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'module_link' => 0,
    'text' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7b6580f4a5_78941903',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7b6580f4a5_78941903')) {function content_5b2e7b6580f4a5_78941903($_smarty_tpl) {?>
<a href="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['module_link']->value, ENT_QUOTES, 'UTF-8', true);?>
"><?php echo $_smarty_tpl->tpl_vars['text']->value;?>
</a>
<?php }} ?>
