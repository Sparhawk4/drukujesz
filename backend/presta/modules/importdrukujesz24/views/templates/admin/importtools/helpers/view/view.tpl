<div>
	<div id="buttonsContainer">
		<div>
			<input id="genassoc" type="button" name="submitGenerateAssociatedProducts" value="{l s='Wygeneruj powiązane produkty'}" class="button" />
			<div>
				generuje powiązane produkty dla produktów drukarkowych - czyli tych, które mają ustawiony kod_produktu<br .>Należy urchomić tą akcje po wyedytowaniu danych dziedzinowych<br />Ta akcja nie mogła być wywoływana autoamtycznie bo byłoby zapętlenie 
			</div>
			<div class="separation"></div>
		</div>
		<div>
			<input type="submit" name="submitProductsDescription" value="{l s='Wygeneruj opisy produktów'}" class="button" />
			<div>
				generuje opisy produktów dla produktów drukarkowych  - po wyedytowaniu danych w bazie dziedzinowej oryginalne opisy magą być nieaktualne np. zmieniono listę drukarek. <br />  po zaaplikowaniu tej akcji wygenerują się nowe opisy których treść jest adekwatna do danych zapisanych w bazie dziedzinowej<br /> akcja dotyczy tylko produktów drukarkowych - czyli tych, które mają ustawiony kod_produktu
			</div>
			<div class="separation"></div>
		</div>				
		<div>
			<form action="{$currentIndex}&token={$token}">
			<input type="submit" name="submitImportPisaki" value="{l s='importPisaki'}" class="button" />
			<input type="hidden" name="controller" value="AdminImporttools" />
			<input type="hidden" name="token" value="{$token}" />
			</form>
			<div>
				importuje pisaki 
			</div>
			<div class="separation"></div>
		</div>						
	</div>
	<div id="consoleContainer">
	</div>
</div>

<script type="text/javascript">
			var globalAjaxToken = "{$token}";
</script>
<script type="text/javascript" src="../modules/importdrukujesz24/importpstools.js"></script>
