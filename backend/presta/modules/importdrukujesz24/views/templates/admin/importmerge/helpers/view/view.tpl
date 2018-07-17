<div>	
	<div class="clear">&nbsp;</div>
	<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
		<label class="clear" style="width:160px; text-align: left;">{l s='Proszę wybrać plik excela '} </label>	
			<div class="margin-form" style="padding-left:190px;">
				<input name="file" type="file" id="fileToUpload" />
				{$class=ExcelParser}
				<input name="class" type="hidden" value = "{$class}" />				
				<input type="submit" name="submitFileUpload" value="{l s='Upload'}" class="button" />				
				Prowizja:
				<input name="provision" type="text"  value="{$parsers.$class.provision}" />
		</div>		
	</form>		
	<div class="separation"></div>
		<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
			<label class="clear" style="width:160px; text-align: left;">{l s='ExcelParserPromocje'}</br>{l s='Proszę wybrać plik excela '} </label>	
				<div class="margin-form" style="padding-left:190px;">
					<input name="file" type="file" id="fileToUpload" />
					{$class=ExcelParserPromocje}
					<input name="class" type="hidden" value = "{$class}" />				
					<input type="submit" name="submitFileUpload" value="{l s='Upload'}" class="button" />				
					Prowizja:
					<input name="provision" type="text"  value="{$parsers.$class.provision}" />
			</div>		
		</form>	
	<div class="separation"></div>
	<div class="clear">&nbsp;</div>
		<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
			<label class="clear" style="width:160px; text-align: left;">PolcanCSV proszę wybrać plik csv</label>	
			<div class="margin-form" style="padding-left:190px;">
				<input name="file" type="file" id="fileToUpload" />
				{$class=PolcanCSV}
				<input name="class" type="hidden" value = "{$class}" />				
				<input type="submit" name="submitFileUpload" value="{l s='Upload'}" class="button" />
				Prowizja: 
				<input name="provision" type="text"  value="{$parsers.$class.provision}" />
				<input type="submit" name="submitDownloadFile" value="{l s='Pobierz plik polcan'}" class="button" />
			</div>
		</form>	
	<div class="separation"></div>
	<div id="offerSources">
		{include file="$tpl_dir./offerSources.tpl"}
	</div>	
	<div class="separation"></div>
	<div id="newProducts">
		{*include file="$tpl_dir./newProducts.tpl" *}
	</div>	
	<div class="separation"></div>
	<div id="megredProducts">
		{*include file="$tpl_dir./mergedProducts.tpl" *}
	</div>	
	<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
		<input type="submit"  value= "regenerate"  name = "regenerate" {*onClick="doOnClick(this)"*} ></div>		
	</form>	
	
	<div id="info_id"></div>
	<div id="errorinfo_id"></div>
	<script type="text/javascript">	
		var megredProducts  = {$megredProducts};
		var newProducts  = {$newProducts};
		var token  = "{$token}";
		var current  = "{$current}";
	</script>	
</div>
