{if !empty($newProducts)}
	Nowe produkty, których jeszcze w sklepie nie było.<br />
	Produkty te muszą zostać powiązane z produktami w sklepie by było możan ustalić najkorzystniejszą ofertę<br />
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
			{foreach $newProducts as $prouct}
				<tr>
					<td>{$prouct.classname}</td>
					<td>{$prouct.id_xml}</td>
					<td><a href="{$prouct.sourceURL}">{$prouct.sourceURL}</a></td>
					<td>{$prouct.cena}</td>
					<td>{$prouct.quantity}</td>			
					<td></td>			
					
				</tr>
			{/foreach}
			
			
			
		</tbody>
	</table>
	<div>
		<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
			<input type="submit"  value= "Połącz nowe produkty"  name = "mergeNewProducts" {*onClick="doOnClick(this)"*} ></div>		
		</form>		
	</div>	
	<div>
		<form action="{$current}&token={$token}" method="post" enctype="multipart/form-data">
			<input type="submit"  value= "Wczytaj nowe produkty"  name = "loadNewProduct" {*onClick="doOnClick(this)"*} ></div>		
		</form>				
	</div>	
{/if}
