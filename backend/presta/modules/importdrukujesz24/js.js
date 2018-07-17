$( document ).ready(function() {
	return;
	if(products.length != 0)
		productsF();
});




function productsF(){
	var result = true; 
	var t = $("#productsTableId"); 		
	$.each( products, function(index, value) {	

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





function doOnClick(t){
		var val = $(t).attr('name')
		//alert(val);
		doAjax(val, null);
		$(t).blur();
}

function deleteOfferSource(t){
	var val = $(t).attr('name');
	var d  = doAjax('deleteOfferSource', val);
	$.each(d  , function(id, val) { 	
		$('#'+id).html(val);
	});
	$(t).blur();
}


    /*
var bar = $('.bar');
var percent = $('.percent');
var status = $('#status');
   */
   

function updateDisplay(json){
	$.each(json  , function(id, val) { 	
		$('#'+id).html('');
		$('#'+id).html(val);
	});	
}



function doAjax(apiFunc, data){
	var ret = new Object ();
	var sendData = new Object(); 
	sendData.ajax  = 1; 
	sendData.apiFunc = apiFunc;  	
	sendData.data  = data; 
//	sendData.token = globalAjaxToken; 
//	sendData = {ajax: 1 , val: apiFunc};
	//sendData.classname = classname;
	$.ajax({
		type: 'GET',
		//url: '/modules/importdrukujesz24/ajaxApi.php',
		async: false,
		cache: false,
		dataType : "json",
		data: sendData,		
			success: function (jsonData)
			{				
				ret = jsonData;
				return jsonData;
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
