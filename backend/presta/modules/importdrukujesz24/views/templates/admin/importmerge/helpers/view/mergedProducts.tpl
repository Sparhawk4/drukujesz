{if empty($newProducts)}
<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
	<input type="submit"  value= "Załaduj do sklepu"  name = "loadToPresta"  ></div>		
</form>		
<div class="separation"></div>
{/if}

Produkty obecne w sklepie :<br />
{$count= count($megredProducts)}
{if $count > 0 }	
	{$currentIdProduct = 0}
	{$curentIndex = 0}	
	<table style="width: 100%">
		<tbody>
			<tr>
				<td>Źródło</td>
				<td>id_xml</td>
				<td>URL dostawcy</td>
				<td>cena</td>
				<td>ilość</td>
				<td>id_product</td>
			</tr>					
			{foreach $megredProducts as $prouct}
				<tr {if $currentIdProduct != $prouct.id_product}class="first_in_section"{else}class="not_oprimal_price"{/if}>					
					<td>{$prouct.classname}</td>
					<td>{$prouct.id_xml}</td>
					<td><a href="{$prouct.sourceURL}">{$prouct.sourceURL}</a></td>
					<td>{$prouct.cena}</td>
					<td>{$prouct.quantity}</td>			
					<td>
						{* $prouct.id_product *}
						{include file="$tpl_dir./editOfferLine.tpl"}
						<form class="ajaxForm" action="{$current}&token={$token}" method="post" enctype="multipart/form-data">	
							<input type="hidden" value = "{$class}" name="classname" />
							<input type="hidden" value = "{$prouct.id_xml}" name="id_xml" />				
							<input type="submit" name="deleteXmlProductMap" value="Usuń powiązanie" class="button" />
						</form>
					</td>								
				</tr>
				{$currentIdProduct =  $prouct.id_product}
			{/foreach}			
		</tbody>
	</table>
{/if}	
{$count}	
