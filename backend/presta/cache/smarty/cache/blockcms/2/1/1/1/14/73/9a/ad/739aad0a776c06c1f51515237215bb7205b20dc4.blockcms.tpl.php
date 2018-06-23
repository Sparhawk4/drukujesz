<?php /*%%SmartyHeaderCode:10837255795b2e7d1817f732-85851448%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '739aad0a776c06c1f51515237215bb7205b20dc4' => 
    array (
      0 => '/app/backend/themes/default-bootstrap/modules/blockcms/blockcms.tpl',
      1 => 1527595554,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '10837255795b2e7d1817f732-85851448',
  'version' => 'Smarty-3.1.19',
  'unifunc' => 'content_5b2e7d605af683_10505926',
  'has_nocache_code' => true,
  'cache_lifetime' => 31536000,
),true); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5b2e7d605af683_10505926')) {function content_5b2e7d605af683_10505926($_smarty_tpl) {?>
	<!-- Block CMS module footer -->
	<section class="footer-block col-xs-12 col-sm-2" id="block_various_links_footer">
		<h4>Information</h4>
		<ul class="toggle-footer">
							<li class="item">
					<a href="http://localhost/index.php?controller=prices-drop" title="Specials">
						Specials
					</a>
				</li>
									<li class="item">
				<a href="http://localhost/index.php?controller=new-products" title="New products">
					New products
				</a>
			</li>
										<li class="item">
					<a href="http://localhost/index.php?controller=best-sales" title="Best sellers">
						Best sellers
					</a>
				</li>
										<li class="item">
					<a href="http://localhost/index.php?controller=stores" title="Our stores">
						Our stores
					</a>
				</li>
									<li class="item">
				<a href="http://localhost/index.php?controller=contact" title="Contact us">
					Contact us
				</a>
			</li>
															<li class="item">
						<a href="http://localhost/index.php?id_cms=3&amp;controller=cms&amp;id_lang=1" title="Terms and conditions of use">
							Terms and conditions of use
						</a>
					</li>
																<li class="item">
						<a href="http://localhost/index.php?id_cms=4&amp;controller=cms&amp;id_lang=1" title="About us">
							About us
						</a>
					</li>
													<li>
				<a href="http://localhost/index.php?controller=sitemap" title="Sitemap">
					Sitemap
				</a>
			</li>
					</ul>
		
	</section>
		<section class="bottom-footer col-xs-12">
		<div>
			<?php echo smartyTranslate(array('s'=>'[1] %3$s %2$s - Ecommerce software by %1$s [/1]','mod'=>'blockcms','sprintf'=>array('PrestaShop™',date('Y'),'©'),'tags'=>array('<a class="_blank" href="http://www.prestashop.com">')),$_smarty_tpl);?>

		</div>
	</section>
		<!-- /Block CMS module footer -->
<?php }} ?>
