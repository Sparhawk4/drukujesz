<div style="margin-left:auto; margin-right:auto; width:70%;">
	{if !$errorCount}
		{if isset($newM)}
	
		<div>
			<div style="display: inline; float: left;  width: 300px;">
						<span style="cursor: pointer;"   onclick ="{literal}{{/literal}$('#xmlManufactuers').toggle();{literal}}{/literal}"><h3> Producenci w xml </h3></span><br />
						<div  id="xmlManufactuers" ' {if !count($newM)}style="display: none;" {/if} >
							{foreach from=$manufacturers key=myId item=i}
							  			{$myId}<br />
							{/foreach}		
						</div>
				</div>
		
			<div style="display: inline; float: left;  width: 300px;">
				<span style="cursor: pointer;"   onclick ="{literal}{{/literal} $('#shopManufactuers').toggle(); {literal}}{/literal}"  ><h3> Producenci w sklepie </h3></span><br />
					<div  id="shopManufactuers">
					
					{foreach from=$shopmanufactuers key=myId item=i}
			  			{$myId}<br />
					{/foreach}							
					</div>
			</div>			
			
		
			<div style="display: inline; float: left;  width: 300px;">
				<span style="cursor: pointer;"  onclick ="{literal}{{/literal}$('#newManufactuers').toggle(); {literal}}{/literal}"><h3> Producenci do dodania</h3></span><br />
						<div  id="newManufactuers" >
						{foreach from=$newM key=myId item=i}
				  			{$myId}<br />
						{/foreach}						
						</div>
			</div>			
		</div>
		<hr style="width:100%; clear: both;" >	
		{/if}
	
		{$prodTodisplay}

		<input name="submitImportClass" type="submit" value="import" class="button" onclick="importPsSynch();" />
			<hr style="width:100%;">
				<div id="info_id">
					</div>
						<div id="errorinfo_id" style="color: red;">
					</div>		
					<hr style="width:100%;">
			<script type="text/javascript" src="../modules/importdrukujesz24/importps.js"></script>
			<script type="text/javascript" src="../modules/importdrukujesz24/json2.js"></script>
			<script type="text/javascript">
			{if isset($afteRready)}
				var afteRready  = "{$afteRready}";	
			{/if} 	
			var globalAjaxToken = "{$globalAjaxToken}";
			{*var classname = "{$classname}";*}
			var provision  = {$provision};
					//var categories  = ' . json_encode($parser->categories).';
					//var categoriesTree  = ' . json_encode($parser->categoriesTree).';'.
					var products = {$products};
					
					{if isset($defaultSupliers)}
						var defaultSupliers = {$defaultSupliers};
						var supliers = {$supliers};
					{else}
						var defaultSupliers = [];
						var supliers = [];
					{/if}
					
					{if isset($classname)}					
						var classname = "{$classname}";
					{/if}	
					{if isset($newM)}					
						var classname = "{$classname}";
						var manufacturers  = {$jsmanufacturers};						
						var attributesGroups = {$jsAttributesGroups};					
					{/if}	
					function afteRreadyCallbadck(){							
						var context = $('#productsTableId');
						var rows  = $('tr' , context);
						$( "<th><div>kod csv</div></th>" ).insertAfter( rows[0].children[2]);						
						rows = rows.slice(1);
						$.each( rows, function(key, v){
							$( "<td></td>" ).insertAfter( v.children[2]);
						});	
						var rows  = $('tr' , context);
						rows = rows.slice(1);
						var index = 0;
						$.each( products, function(key, product){
							$( 'td:nth-child(4)' , rows[index]).append('<div>'+product['xml_kod_produktu']+'</div>');
							if(product['prestaId'] == undefined){								
								products[key]['noActive'] = 1;
								$( 'td:nth-child(3)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(5)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(6)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(7)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(8)' , rows[index]).css({ "font-style": "italic", "color": "red" });																	
							}
							if(product['disabled'] !== undefined){
								var c  = $( 'td:nth-child(2) input' , rows[index]);
								$(c).attr("checked" ,  false);
								$(c).attr('disabled' , true);  
								$( 'td:nth-child(3)' , rows[index]).css({ "font-style": "italic", "color": "grey" });
								$( 'td:nth-child(5)' , rows[index]).css({ "font-style": "italic", "color": "grey" });
								$( 'td:nth-child(6)' , rows[index]).css({ "font-style": "italic", "color": "grey" });
								$( 'td:nth-child(7)' , rows[index]).css({ "font-style": "italic", "color": "grey" });
								$( 'td:nth-child(8)' , rows[index]).css({ "font-style": "italic", "color": "grey" });

							}
							
							index++;
						});
						{if isset($afteRready)}
							{literal}
								loadNewProducts();
								
							{/literal}
						{/if}
					}
					</script>		
		
	{/if}
</div>
