<div>
	<div id="upload_file_import" style="padding-left: 10px; background-color: #EBEDF4; border: 1px solid #CCCED7">
		<div class="clear">&nbsp;</div>
		<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
			<label class="clear" style="width:160px; text-align: left;">{l s='Select your CSV file'} </label>	
			<div class="margin-form" style="padding-left:190px;">
				<input name="file" type="file" />
				<p class="preference_description">
					{l s='You can also upload your file via FTP to the following directory:'} {$path_import}.
				</p>
			</div>
			
			<div class="margin-form" style="padding-left:190px;">
				<input type="submit" name="submitFileUpload" value="{l s='Upload'}" class="button" />
				<p class="preference_description">
					{l s='Only UTF-8 and ISO-8859-1 encoding are allowed'}
				</p>
			</div>
		</form>
	</div>
</div>
