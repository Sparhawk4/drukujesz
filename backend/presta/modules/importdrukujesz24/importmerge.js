$( document ).ready(function() { 	
	newProductsDisplay(); 
	megredProductsDisplay();	
	$(window).scroll(function() {
		onInViewportMerged();	
		onInViewportNew();	
	});	
});


function newProductsDisplay(){	
	var node  = $('#newProducts'); 	
	$(node).html('');
	if(newProducts.length ==0)
		return;
	var str = '<h1 style="color: red;">Nowe produkty, których jeszcze w sklepie nie było!</h1>.<br />'+
	'Produkty te muszą zostać powiązane z produktami w sklepie by było możan ustalić najkorzystniejszą ofertę<br />'+
	'Na początek wcisnąć <strong>Połącz nowe produkty</strong>  poniżej<br />'+
	'Spowoduje to próbę ustalenia co to za produkty na podstawie danych z katalogu<br />'+
	'Jeśli nie wszystkie produkty się połączą  i nadal będzie widzoczne powiadomienie o nowych produktach:<br />'+
	'Należy przejrzeć co to za produkty<br />'+
	'Przykład:<br />'+
	'Po połączeniu nowego produktu widziny że produkt o kodzie 	TN-2220 nie jest połączony.<br />'+
	'Jednocześnie wiemy że mamy już produkt  o kodzie TN2220, oraz że to te same produkty. Czyli to oferty na ten sam produkt<br /><br />'+
	'Rozwiązanie ZALECANE:<br />'+
	'W już istniejącym produkcie o kodzie TN2220 dodajemy synonim kodu produktu TN-2220.(Opcjonalnie można takze dodac synonimy OEM) Po czym ponownie nacisnać<strong>Połącz nowe produkty</strong><br />'+	
	'Dzięki temu wyszukiwarka będzie wiedziała że jak klient szuka TN-2220 to należy zaproponowac mu TN2220.<br /><br />'+
	'Rozwiązanie anternatywne:<br />'+
	'Jeśli niepołączony produkt ma kod np: q2610#44, którt jest produktem q2610 dostarczonym pod inną nazwą.<br />'+
	'Ustalamy jakie id ma produkt q2610 obecnie. Wpisujemy  to id w  pole 	id_product w wierszu opisującym q2610#44. Dajemy zapisz. Po czym ponownie nacisnać<strong>Połącz nowe produkty</strong><br />'+
	'Katalog wie że oferta na q2610#44 dotyczy tak naprawdę q2610. Jednocześnie  wyszukiwarka nie rozpoznaje niepoprawnego kodu q2610#44, a dane katalogu są zgone z rzeczywistością (Fizycznie produkt q2610#44 nie istniej - to tylko wymysł polcanu)<br /><br /><br />'+
	'<div>'+
		'<form action="'+current+'&token='+token+'" method="post" enctype="multipart/form-data">'+
			'<input type="submit"  value= "Połącz nowe produkty"  name = "mergeNewProducts">  Nastąpi próba ustalenia co to za produkty na podstawie danych z katalogu'+
		'</form>'+
	'</div>'+
	'<div>'+
		'<form action="'+current+'&token='+token+'" method="post" enctype="multipart/form-data">'+
			'<input type="submit"  value= "Wczytaj nowe produkty"  name = "loadNewProduct"> Ponownie wczytuje nowe produkty po połączeniu jakie mogło nastąpić w poprzednim kroku'+
		'</form>'+
	'</div>'+		
	'<table style="width: 100%">'+
		'<tbody>'+
			'<tr>'+
				'<td>Źródło</td>'+
				'<td>id_xml</td>'+
				'<td>URL dostawcy</td>'+
				'<td>cena</td>'+
				'<td>ilość</td>'+
				'<td>id_product</td><td></td><td></td><td></td>'+
			'</tr>';
	$.each(newProducts  , function(i, product) { 	
		str += '<tr id="new_tr_id_'+i+'" class="inview"></tr>';
	});							
	str +='</tbody>'+
	'</table>';
	$(node).html(str);
	onInViewportNew();
}

function onInViewportNew(){
		//var c = $('#newProducts tr.inview::in-viewport');
		var c = $("#newProducts  tr.inview:in-viewport");		
		$.each(c  , function(i, that) {
			var i = $(that).attr('id').substr(10);
			var product  = newProducts[i];					
			var str  = '<td>'+product.classname+'</td>'+
						'<td>'+product.id_xml+'</td>'+
						'<td><a href="'+product.sourceURL+'">'+product.sourceURL+'</a></td>'+
						'<td>'+product.cena+'</td>'+
						'<td>'+product.quantity+'</td>'+
						'<td></td>'+
						'<td><input type="text" maxlength="10"  size="10"  value=""></td>'+
						'<td><button onclick="editProductId(this)">Zapisz</button></td><td></td>';	
												
			$(that).append(str);
			$(that).after('<tr><td></td><td colspan="2">'+product.xmlName+'</td><td colspan="5"></td><td></td></tr>');
			$(that).removeClass('inview');				
		});						

}

function onInViewportMerged(){
		// var c = $('#megredProducts tr.inview::in-viewport');
		var c = $('#megredProducts  tr.inview:in-viewport'); 
		$.each(c  , function(i, that) {
			var i = $(that).attr('id').substr(13);
			var product  = megredProducts[i];					
			var str  = '<td>'+product.classname+'</td>'+
				'<td>'+product.id_xml+'</td>'+
				'<td><a href="'+product.sourceURL+'">'+product.sourceURL+'</a></td>'+
				'<td>'+product.cena+'</td>'+
				'<td>'+product.quantity+'</td>'+	
				'<td>'+product.id_product+'</td>'+
				'<td><input type="text" maxlength="10"  size="10"    value="'+product.id_product+'"></td>'+
				'<td><button onclick="editProductId(this)">Zapisz</button></td><td><button onclick="dMP('+i+')">Usuń powiązanie</button></td>';
			$(that).html(str);
			$(that).after('<tr><td></td><td colspan="2">'+product.xmlName+'</td><td colspan="5">'+product.shopName+'</td><td></td></tr>');
			
			$(that).removeClass('inview');				
		});						

}


function megredProductsDisplay(){
	var time =  new Date().getTime();		
	var node  = $('#megredProducts'); 		
	var time1 =  new Date().getTime();
	var str = '';
	if(newProducts.length ==0){				
		str+='<form action="'+current+'&token='+token+'" method="post" enctype="multipart/form-data">\
				<input type="submit"  value= "Załaduj do sklepu"  name = "loadToPresta"  ></div>\
			</form>\
			<div class="separation"></div>';
	}	
	var count  = megredProducts.length;	
	str+= 'Produkty obecne w sklepie :<br />'+count;
	if( count > 0 ){	
		var currentIdProduct = 0;
		var curentIndex = 0;	
		str += '<table style="width: 100%">'+
			'<tbody>'+
				'<tr>'+
					'<td>Źródło</td>'+
					'<td>id_xml</td>'+
					'<td>URL dostawcy</td>'+
					'<td>cena</td>'+
					'<td>ilość</td>'+
					'<td>id_product</td><td></td><td></td><td></td>'+
				'</tr>';		
		$.each(megredProducts  , function(i, product) {
			str += '<tr id="merged_tr_id_'+i+'" '+ (currentIdProduct != product.id_product ? 'class="first_in_section inview"' : 'class="not_oprimal_price inview"')+'></tr>';						
			currentIdProduct =  product.id_product;
		});				
		str +='</tbody></table>';	
	}
	$(node).html(str)	
	onInViewportMerged();
	
	//var c = $('#megredProducts tr.inview::in-viewport');
	//$('#megredProducts tr.inview::in-viewport').do(function(){onInViewportMerged(this);});	
}


function dMP(index){	
	var time =  new Date().getTime();		
	var product = megredProducts[index];
	var url = current+'&token='+token;		
	$.ajax({
		type: 'POST',
		url: url,
		async: false,
		cache: false,
		dataType: 'json',
		data: {classname:product.classname,id_xml:product.id_xml,deleteXmlProductMap:1},
			success: function (jsonData)
			{
				var time1 =  new Date().getTime();
				megredProducts=jsonData.megredProducts;
				newProducts=jsonData.newProducts
				
				newProductsDisplay(); 
				megredProductsDisplay();			
			},
			error: function (XMLHttpRequest, textStatus/*, errorThrown*/) 
			{					
					alert(XMLHttpRequest.responseText);
			}
   });	
}
function editProductId(that){	
	var tds = $(that).parent().parent().children();
	var val = $('input',tds[6]).val();
	var id_xml = $(tds[1]).text();
	var classname = $(tds[0]).text();
	var url = current+'&token='+token;		
	$.ajax({
		type: 'POST',
		url: url,
		async: false,
		cache: false,
		dataType: 'json',
		data: {classname:classname,id_xml:id_xml,id_product:val,changeXmlProductMap:1},
			success: function (jsonData)
			{				
				if(jsonData.error){
					alert(jsonData.error)					
				}else{
					megredProducts=jsonData.megredProducts;
					newProducts=jsonData.newProducts					
					newProductsDisplay(); 
					megredProductsDisplay();		
				}	
			},
			error: function (XMLHttpRequest, textStatus/*, errorThrown*/) 
			{					
				alert(XMLHttpRequest.responseText);
			}
   });	
}

function gotoNode(that){
	alert(that);
}

function updateDisplay(){
	var time =  new Date().getTime();	
	newProductsDisplay(); 
	megredProductsDisplay();			
	var time1 =  new Date().getTime();	
$('form.ajaxForm').ajaxForm({
	dataType : 'json',
    beforeSend: function() {
      /*  status.empty();
        var percentVal = '0%';
        bar.width(percentVal)
        percent.html(percentVal);
        */ 
    },
    uploadProgress: function(event, position, total, percentComplete) {
        /*var percentVal = percentComplete + '%';
        bar.width(percentVal)
        percent.html(percentVal);
        */ 
    },
    success: function(jsonData) {
		megredProducts=jsonData.megredProducts;
		newProducts=jsonData.newProducts
		updateDisplay(jsonData)		
        /*var percentVal = '100%';
        bar.width(percentVal)
        percent.html(percentVal);
        */ 
    },
    complete: function(xhr) {
			
          // status.html(xhr.responseText);
    }
}); 		
	//alert( ((time1 - time)/1000)+"\n"+  (((new Date().getTime()) - time1)/1000) +"\n"+  (((new Date().getTime()) - time)/1000) );
}
