<div style="margin-left:auto; margin-right:auto; width:70%;">
	{if !$errorCount}
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
	
	
	
		{$prodTodisplay}
				
		<input name="submitImportClass" type="submit" value="import" class="button" onclick="importPsSynch();" />
			<hr style="width:100%;">
				<div id="info_id">
					</div>
						<div id="errorinfo_id" style="color: red;">
					</div>		
					<hr style="width:100%;">
			<script type="text/javascript" src="../modules/importdrukujesz24/importps.js"></script>
			<script type="text/javascript">
			var globalAjaxToken = "{$globalAjaxToken}";
			var classname = "{$classname}";
			var provision  = {$provision};
					//var categories  = ' . json_encode($parser->categories).';
					//var categoriesTree  = ' . json_encode($parser->categoriesTree).';'.
					var manufacturers  = {$jsmanufacturers};
					var products = {$products};
					var attributesGroups = {$jsAttributesGroups};
					var prestaProductsMap = {$prestaProductsMap};
					function afteRreadyCallbadck(){						
						var context = $('#productsTableId');
						var rows  = $('tr' , context); 
						rows = rows.slice(1);
						var index = 0;
						$.each( products, function(key, product){
							if(!prestaProductsMap[key]){
								products[key]['noActive'] = 1;
								$( 'td:nth-child(3)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(4)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(5)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(6)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								$( 'td:nth-child(7)' , rows[index]).css({ "font-style": "italic", "color": "red" });
								key++;									
							}else{
								products[key]['prestaId'] =prestaProductsMap[key]; 
							} 
							index++;
						});
						
					}
					</script>		
		
	{/if}
</div>
