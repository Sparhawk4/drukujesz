var htmlIdsMap;
$( document ).ready(function() {
	var irow =0;
	var body = $('#productsTableId');
	body = $('tbody' , body);
	//body = $('tr' , body);
	//var p = new Array();
	htmlIdsMap = new Object(); 
	var htmlId = 0;
	$.each( products, function(key, product) {
		htmlIdsMap[key] = htmlId;  
		
		
		ret= '<tr'+ ( irow % 2 ? ' style="background-color:#D1EAEF"' : ' ' )+'>'; 
		ret += '<td style="text-align: right;"   >' + (irow+1) + '</td>';
		ret+= '<td>' + '<input  id="active_id_'+htmlIdsMap[key]+'" type="checkbox" name="active" checked="checked">' + '</td>';
		ret+= '<td>' + product['prestaId'] + '</td>';
		ret+= '<td>' + product['name'][1] + '</td>';
		ret+= '<td style="text-align: right;" >' +  Number(product['wholesale_price']).toFixed(2)+ '</td>';	
		//bprice =  (ceil($product['wholesale_price'] * $provision * 100))/100;
		
		product['price'] =  (product['wholesale_price'] * provision[product['parserClass']] );
		//product['price'] = product['wholesale_price']; 
		ret+= '<td style="text-align: right;" >' + Number(product['price']).toFixed(2)  + '</td>';
		ret+= '<td style="text-align: right;" >' + ((prodPriceMap[key] !== undefined )?  Number(prodPriceMap[key]).toFixed(2) : "") + '</td>';
		ret+= '<td>' + '<input id="price_id_'+htmlIdsMap[key]+'"  style="text-align: right;" type="text" size="7"  maxlength = "7" name="final_price"  value="'+Number(product['price']).toFixed(2)+'">' + '</td>';
	ret+= '</tr>';
		irow++;
		htmlId++;
		$(body).append(ret);	
	}); 	
	
	try{
		if(afteRreadyCallbadck !== undefined){			
				afteRreadyCallbadck();
			}
	}
	catch(err){  }
	
	
	
	//var ret  = ajaxCall('ccc', p); 
	
	//alert(p);
});



function onActiveChange(c){
	var ch = $(c).attr('checked');	
	var ch = c.checked;	
	var t = $("#productsTableId"); 		
	$.each( products, function(index, value) { 	
		$("#active_id_" + htmlIdsMap[index] , t).attr("checked" ,  ch) ;
	});
}


function onAdd(){
	var t = $("#productsTableId"); 		
	var input = $("#add_price_id" , t); 
	var val = input.val();
	var add = Number(val); 	
	if(isNaN(add)){	
		input.focus();
		input.css("color" , "Red");	
		alert("wpisz poprawną warość");	
		return false; 		
	}
	input.css("color" , "Black");	
	$.each( products, function(index, value) {
		value.price = parseFloat(value.price);
		value.price += add;		
		$("#price_id_" + htmlIdsMap[index] , t).val(value.price.toFixed(2));
		
	});
}


function validateForm(){
	var t = $("#productsTableId"); 		
	var errorCount= 0; 
	var firstError; 
	$.each( products, function(index, value){ 	
		var input = $("#price_id_" +htmlIdsMap[index] , t); 
		var val = input.val();
		var price = Number(val); 
		if(isNaN(price)){	
			input.css("color" , "Red");		
			if(!errorCount)  firstError = input;
			errorCount++;
			
		}else{
			input.css("color" , "black");				
		}
	});
	if(errorCount){
		firstError.focus();
		alert("wpisz poprawne ceny");	
		return false; 
	}
	return true;
}

function addAColors(){
	var ret  = ajaxCall('addAColors', featuresGroup['Kolor wkładu']); 
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}
	var colors = ret.data.ret;
	$.each( products, function(index, prod) { 
		if(prod.features['Kolor wkładu'] != undefined){
			prod['a_color_id'] = colors[prod.features['Kolor wkładu']];
		}		
		
	});		
	return true;			
}
 

function productFeatures(){
	var ret  = ajaxCall('addProductFeatures', featuresGroup); 
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}
	features = ret.data.ret.features;	
	var featuresVals = ret.data.ret.featuresVals;	
	$.each( products, function(index, prod) { 		
		prod.featuresIds = new Array();		
		$.each( prod.features, function(featureName, featureValue) {
			//if(featuresVals[featureName][featureValue] == undefined){
			//	alert('brak');
			//}	
			prod.featuresIds.push({id_feature: features[featureName] , id_feature_value:  featuresVals[featureName][featureValue] });
		});
	});				
	return true;			
}	

function printermanufactuers(){
	var ret  = ajaxCall('addPrinterManufactuers', printerManufactuers); 
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}
	$.each( products, function(index, prod) { 
		$.each( prod.printers, function(i, pri) {
			if(pri.producerName != undefined){
				pri['producer_id'] = ret.data.ret[pri.producerName];
			}/*else{
				pri['producer_id'] = '';
			}*/
			//pri['id'] = 

		});
	});				
			
	
	return true;		
			  		
}

function importPs(){	
	var result  = 1 ;// validateForm();
	if(!result) return;
		result  = manufactures();
	if(!result) return;
		result  = addAColors();			
	if(!result) return;
		result  = productFeatures();		
	if(!result) return;
		result  = printermanufactuers();		
	if(!result) return;
		result  = attributes();	
	if(!result) return;
		result  = productsF();
	if(!result) return;
		result  = updateActive();
	if(!result) return;
		result  = updateActiveManufactuers(); 		
	if(!result) return;
		result  = updateActiveCategory(); 				
	if(result) 	
		$("#info_id").text('koniec');	
}


function loadNewProducts(){
	var result = validateForm();
	if(!result) return;
		result  = manufactures();
	if(!result) return;
		result  = attributes();			
	if(!result) return;	
		result  = productsPacks();
	if(result){ 
		$("#info_id").text('koniec');	
		window.location.href  = afteRready;		
	}	
		//alert(window.location.pathname);
		//window.location.reload(true);
		//alert('koniec');
}



function importPsSynch(){
	var result = validateForm();
	/*if(!result) return;
		result  = manufactures();
	if(!result) return;
		result  = attributes();			
	if(!result) return;	
		result  = disableDomainProducts();
	if(!result) return;	
	*/ 
	result  = productsPacks();
	if(!result) return;
		result  = updateActive();
		
	if(!result) return;
	//	result  = updateActiveManufactuers(); 		
	if(!result) return;
		result  = updateActiveCategory(); 				
		
	if(result) 		 
		$("#info_id").text('koniec');	
}
function updateSupliers(){
		
}

function disableDomainProducts(){
	var result = true; 
	var ret  = ajaxCall('disableDomainProducts', null);
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}		  
	return result; 
}	

function manufactures(){
	var result = true; 
	$.each( manufacturers, function(index, value) { 
		var obj  = new Object(); 
		obj.name = index; 
		obj.value =  value; 
		$("#info_id").text(index);
		var ret  = ajaxCall('addManufactuer', obj); 			
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}		  
	});
	if(result) 
		$("#info_id").text('Ok');
	return result; 
}


function attributes(){
	var result = true; 
	var len  = attributesGroups.length;
	if(len <= 0) 
		return result; 
	var ret  = ajaxCall('addAttributesGroups', attributesGroups); 			
	if(ret.status){
		$("#errorinfo_id").text(ret.data);
		result = false; 
		return false;	
	}else{
		$("#errorinfo_id").text('');		
	}
	if(result){ 
		$("#info_id").text('Ok attributes group');
		attributesGroups = ret.data;
		$.each( products, function(index, value) { 
			$.each( value.attributesCombination, function(index, combination) { 			
				combination.attrs_ids = new Array();
				$.each( combination.attributes, function(groupName, attrValueName) { 
					var v = attributesGroups[groupName]['attributes'][attrValueName];
					combination.attrs_ids.push(v);				
				});						
			});
		});		
		
	}	
		// 
		
	return result; 
}
function updateActive(){
	var t = $("#productsTableId"); 		
	var active = Array(); 
	$.each( products, function(index, value) {			
		if(!($("#active_id_" + htmlIdsMap[index] , t).is(':checked')))  return true; 
		active.push(products[index].prestaId); 		
	});
	var obj  = new Object(); 
	obj.active = active;
	obj.defaultSupliers = defaultSupliers;
	obj.supliers = supliers;
	obj.dummy = "dummy";
	var jsonPack = JSON.stringify(obj);				
	var ret  = ajaxCall('updateActive', jsonPack); 	
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}	
	return true;	
}


function updateActiveManufactuers(){
	var obj  = new Object(); 
	obj.dummy = "dummy";
	var ret  = ajaxCall('updateActiveManufactuers', obj); 
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}	
	return true;	
}


function updateActiveCategory(){
	var obj  = new Object(); 
	obj.dummy = "dummy";
	var ret  = ajaxCall('updateActiveCategory', obj); 
		if(ret.status){
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
		}	
	return true;	
}

function productsPacks(){
	var result = true; 
	var t = $("#productsTableId");
	var packSize = 0;
	var packLimit = 100;
	var packs= new Array();
	var pack= new Array();
	var packLabels='';
	$.each( products, function(index, value) {				
		if(($("#active_id_" + htmlIdsMap[index] , t).is(':checked'))){
			value.price = $("#price_id_" +htmlIdsMap[index] , t).val();
			var obj  = new Object();
			packLabels +=  index+', ';
			obj.name = index; 
			obj.value =  value;						
			pack.push(obj);
			packSize++;
			if(packLimit == packSize){
				packs.push({pack: pack , labels: packLabels});
				pack= new Array();
				packSize = 0;
				packLabels='';
			}
		}
	});	
	if(pack.length >0){
		packs.push({pack: pack , labels: packLabels});
	}		
	
	$.each( packs, function(index, pack) {
		var jsonPack = JSON.stringify(pack.pack);				
		var ret  = ajaxCall('addProductPack', jsonPack);
		if(ret.status){
			$("#info_id").html(pack.labels);
			$("#errorinfo_id").html(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").html('');		
			$("#info_id").html(pack.labels +'<br />' +ret.data.data);
		}
	});
	if(result) 
		$("#info_id").text('Ok');
	return result; 	

}

function productsF(){
	var result = true; 
	var t = $("#productsTableId"); 		
	$.each( products, function(index, value) {	

	//	index = "Q2610A";
	//	value = products["Q2610A"]; 
	//if(!($("#active_id_" + index , t).is(':checked')))  return true; 
	//	value.id_category = categories[value.category]['id_category'];
	//	value.price = $("#price_id_" + index , t).val();
		//value.id_category = 2;
		value.price = value.wholesale_price;
		
		
		var obj  = new Object(); 
		obj.name = index; 
		obj.value =  value; 
		//$("#info_id").text(index);
		var ret  = ajaxCall('addProduct', obj); 			
		if(ret.status){
			$("#info_id").text(index);
			$("#errorinfo_id").text(ret.data);
			result = false; 
			return false;	
		}else{
			$("#errorinfo_id").text('');		
			$("#info_id").text(index + ret.data.data);
		}
	});
	if(result) 
		$("#info_id").text('Ok');
	return result; 	

}

function ajaxCall(apiFunc, data){
	var ret = new Object ();
	var sendData = new Object(); 
	sendData.ajax  = 1; 
	sendData.apiFunc = apiFunc;  	
	sendData.data  = data; 
	sendData.token = globalAjaxToken; 
	sendData.classname = classname;
	$.ajax({
		type: 'POST',
		url: '../modules/importdrukujesz24/ajax.php',
		async: false,
		cache: false,
		dataType : "json",
		data: sendData,		
			success: function (jsonData)
			{
				if (jsonData.hasError)
				{
					ret.status = 1;
					ret.data = 'json error'; 
					return ret;
				}
				else{
					ret.status = 0;
					ret.data = jsonData; 
					return ret;				


				}

			},
			error: function (XMLHttpRequest, textStatus/*, errorThrown*/) 
			{
					ret.status = 2;
					ret.data = XMLHttpRequest.responseText;
					return ret;
			}
   });
   return ret;
};

