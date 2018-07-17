<div>

   <!--$languages = Language::getLanguages();-->
	
	<b>Import: </b>
	<hr style="width:100%;">
	<form action="{$currentIndex}&token={$token}" method="post" id="import_form" name="import_form">
		<input type="hidden" name="csv" value="'.Tools::getValue('csv').'" />		
		<div style="text-align:left; margin-top:10px;">
			<!--<input name="submitImportClass" type="submit" value="'.'Yournewstyle'.'" class="button" />			-->
			<input name="submitImportClass" type="submit" value="ImportZerowy" class="button" />			
		</div>
	</form>
	<hr style="width:100%;">
</div>
