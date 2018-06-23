<?php /* Smarty version Smarty-3.1.19, created on 2018-06-23 18:55:03
         compiled from "/app/backend/admin/themes/default/template/controllers/slip/_print_pdf_icon.tpl" */ ?>
<?php /*%%SmartyHeaderCode:18138426415b2e7b673ac6e2-29177030%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '2d7d189c0810dfa168df38c3dce6b9c902e473d5' => 
    array (
      0 => '/app/backend/admin/themes/default/template/controllers/slip/_print_pdf_icon.tpl',
      1 => 1527595552,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '18138426415b2e7b673ac6e2-29177030',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'link' => 0,
    'order_slip' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7b673b8769_54694608',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7b673b8769_54694608')) {function content_5b2e7b673b8769_54694608($_smarty_tpl) {?>



<a class="btn btn-default _blank" href="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['link']->value->getAdminLink('AdminPdf'), ENT_QUOTES, 'UTF-8', true);?>
&amp;submitAction=generateOrderSlipPDF&amp;id_order_slip=<?php echo intval($_smarty_tpl->tpl_vars['order_slip']->value->id);?>
">
	<i class="icon-file-text"></i>
	<?php echo smartyTranslate(array('s'=>'Download credit slip'),$_smarty_tpl);?>

</a>

<?php }} ?>
