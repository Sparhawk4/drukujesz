<?php /*%%SmartyHeaderCode:16175419745b2e7d1755c941-71407570%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '39f6fd669f14bc71bff43294af69145824cc22e6' => 
    array (
      0 => '/app/backend/themes/default-bootstrap/modules/blocksearch/blocksearch-top.tpl',
      1 => 1527595554,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '16175419745b2e7d1755c941-71407570',
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7d5fae0966_78671896',
  'has_nocache_code' => false,
  'cache_lifetime' => 31536000,
),true); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7d5fae0966_78671896')) {function content_5b2e7d5fae0966_78671896($_smarty_tpl) {?><!-- Block search module TOP -->
<div id="search_block_top" class="col-sm-4 clearfix">
	<form id="searchbox" method="get" action="//localhost/index.php?controller=search" >
		<input type="hidden" name="controller" value="search" />
		<input type="hidden" name="orderby" value="position" />
		<input type="hidden" name="orderway" value="desc" />
		<input class="search_query form-control" type="text" id="search_query_top" name="search_query" placeholder="Search" value="" />
		<button type="submit" name="submit_search" class="btn btn-default button-search">
			<span>Search</span>
		</button>
	</form>
</div>
<!-- /Block search module TOP -->
<?php }} ?>
