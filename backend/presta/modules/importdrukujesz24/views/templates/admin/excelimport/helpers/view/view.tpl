<div>
	<div id="upload_file_import" style="padding-left: 10px; background-color: #EBEDF4; border: 1px solid #CCCED7">
		<div class="clear">&nbsp;</div>
		<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
			<label class="clear" style="width:160px; text-align: left;">{l s='Proszę wybrać plik excela '} </label>	
			<div class="margin-form" style="padding-left:190px;">
				<input name="file" type="file" />
				Prowizja: 
				<input name="parserProvision" type="text"  value="{$parserProvision}" />
			</div>
			
			<div class="margin-form" style="padding-left:190px;">
				<input type="submit" name="submitFileUpload" value="{l s='Upload'}" class="button" />
			</div>
		</form>
	</div>
</div>
