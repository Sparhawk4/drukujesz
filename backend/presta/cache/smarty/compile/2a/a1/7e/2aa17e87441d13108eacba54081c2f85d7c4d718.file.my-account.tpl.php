<?php /* Smarty version Smarty-3.1.19, created on 2018-06-23 18:55:02
         compiled from "/app/backend/themes/default-bootstrap/modules/loyalty/views/templates/hook/my-account.tpl" */ ?>
<?php /*%%SmartyHeaderCode:7154031565b2e7b66c76fd0-28118530%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '2aa17e87441d13108eacba54081c2f85d7c4d718' => 
    array (
      0 => '/app/backend/themes/default-bootstrap/modules/loyalty/views/templates/hook/my-account.tpl',
      1 => 1527595554,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7154031565b2e7b66c76fd0-28118530',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'link' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7b66c854c2_91228139',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7b66c854c2_91228139')) {function content_5b2e7b66c854c2_91228139($_smarty_tpl) {?>

<!-- MODULE Loyalty -->
<li class="loyalty">
	<a href="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['link']->value->getModuleLink('loyalty','default',array('process'=>'summary'),true), ENT_QUOTES, 'UTF-8', true);?>
" title="<?php echo smartyTranslate(array('s'=>'My loyalty points','mod'=>'loyalty'),$_smarty_tpl);?>
" rel="nofollow"><i class="icon-flag"></i><span><?php echo smartyTranslate(array('s'=>'My loyalty points','mod'=>'loyalty'),$_smarty_tpl);?>
</span></a>
</li>
<!-- END : MODULE Loyalty -->
<?php }} ?>
