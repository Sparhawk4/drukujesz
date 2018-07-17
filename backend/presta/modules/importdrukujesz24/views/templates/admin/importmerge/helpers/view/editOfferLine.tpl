<form class="ajaxForm" action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
	<input type="hidden" value = "{$class}" name="classname" />
	<input type="hidden" value = "{$prouct.id_xml}" name="id_xml" />				
	<input type="text" value = "{$prouct.id_product}" name="id_product" />
	<input type="submit" name="changeXmlProductMap" value="Zapisz" class="button" />
</form>
