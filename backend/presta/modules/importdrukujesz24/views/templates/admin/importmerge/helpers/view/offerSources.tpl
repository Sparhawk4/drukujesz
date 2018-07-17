Aktualnla oferta sklepu została wygenerowana na podstawie następujących źródeł:<br />
{foreach $curentOferSources as $row}
	<form action="{$current}&token={$token}" method="post">
		<input name="className" type="hidden" value = "{$row.classname}" />		
	<div>{$row.classname}  Plik: {$row.filename}  <input type="submit" name="deleteOfferSource" value="Usuń {$row.classname} z oferty" class="submit" /></div>
	</form>
{/foreach}
