		var currentDialogLink;
		function queryOnclick(y , id){		
			currentDialogLink = y;
			enabledoModal();
			var c = id;				
			//alert(static_token);						
			//alert(baseUri);
			var sendData = new Object(); 
			sendData.ajax  = 1; 
			sendData.apiFunc = 'getOueryDialog';  	
			sendData.data = 'dummy';
			sendData.token = static_token; 			
			$.ajax({
					type: 'POST',
					url: '/modules/importdrukujesz24/ajaxApi.php',
					async: false,
					cache: false,
					dataType: 'json',
					data: sendData,
					success: function(jsonData)
					{
						var dialog  = $('#price_query')[0];
						if(jsonData['id'] != null){
							$( "input[name='queryPriceEmail']"  , dialog).val(jsonData['email']);
							$( "input[name='queryPricePhone']"  , dialog).val(jsonData['phone']);
							$( "input[name='queryPriceName']"  , dialog).val(jsonData['name']);							
							//alert(jsonData['email'])								
						}else{
							$( "input[name='queryPriceEmail']"  , dialog).val('');
							$( "input[name='queryPricePhone']"  , dialog).val('');
							$( "input[name='queryPriceName']"  , dialog).val('');							
							//alert(jsonData)	
						}									
						$( "input[name='queryPriceProductLink']"  , dialog).val(c);
						var pos = $(y).position();
						var offset = $(y).offset();
						
						var xwindowSize  = $(window ).width();
						var ywindowSize  = $(window ).height();
						var xDialog = $(dialog).width();
						var yDialog = $(dialog).height();
						$('.error' , dialog).hide();
						//alert(ywindowSize);
						//alert(pos);		
						$(dialog).css('left',   xwindowSize/2  - xDialog/2);			
						$(dialog).css('top',   offset.top - pos.top);			
						//$(dialog).css('width', "100%");
						//$(dialog).css('height', "100%");
						$(dialog).show();												
					}
				});									
		}
		function enabledoModal(){
			disableModal();
			$('body').append('<div id="queryPriceModal" style="position:fixed; width: 100%;  height: 100%; top:0; left:0; z-index: 200;  background: rgba( 255, 255, 255, .8 ) 50% 50%    no-repeat; "></div>')
		}	
		function disableModal(){
			var d = $("div#queryPriceModal");
			if(d.length !=0)
				$(d).remove();
		}			
		
		function onCloseDialog(){
			var dialog  = $('#price_query')[0];
			$(dialog).hide();
			disableModal();
		}
		function onQueryPrice(){
			var dialog  = $('#price_query')[0];
			var sendData = new Object(); 
			var data  =  new Object();
			data.email = $( "input[name='queryPriceEmail']"  , dialog).val();
			data.phone = $( "input[name='queryPricePhone']"  , dialog).val();
			data.link  = $( "input[name='queryPriceProductLink']"  , dialog).val();			
			data.name  = $( "input[name='queryPriceName']"  , dialog).val();			
			sendData.ajax  = 1; 
			sendData.apiFunc = 'sendOueryDialog'; 
			sendData.data = data,
			sendData.token = static_token						
			$.ajax({
					type: 'POST',
					url: '/modules/importdrukujesz24/ajaxApi.php',
					async: false,
					cache: false,
					dataType: 'json',
					data: sendData,
					success: function(jsonData)
					{
						$('.error' , dialog).hide();
						if(jsonData.status == 0){
							if(jsonData.error!=undefined){
								var e  = $('#queryPriceError', dialog).html(jsonData.error).show()
								/*$(e).html(jsonData.error);
								$(e).show();*/
							}
							if(jsonData.phoneError!=undefined){
								var e  = $('#phoneError', dialog).html(jsonData.phoneError).show()
							}							
							if(jsonData.emailError!=undefined){
								var e  = $('#emailError', dialog).html(jsonData.emailError).show()
							}																				
						}else{
							$(currentDialogLink).remove();
							onCloseDialog();			
						}
						//alert(jsonData);									
					}
				});										
		}
	$(function(){
		//$('.queryPriceClass').css('background' ,  'url("/modules/importdrukujesz24/img/gnome-help48.png") no-repeat scroll center center');
		 //background: url("../img/add_to_cart_off.png") no-repeat scroll center center #5984ae !

				
		$('body').append('\
	<div id="price_query"  title = "zapytaj o cenę" style=" border-style: solid;\
			border-width: 1px;\
			 display:none;\
			 z-index: 10000; \
			 position:absolute; \
			 background-color: white;\
			 /*width: 300px;\
			 height: 200px;*/\
			 font-size: large;\
			 line-height: 2em;\
			 margin: 5px 5px 5px 5px;\
			 " >\
		<div style="background-color: #5984ae; color:white; font-family: bold; text-align: center; margin-bottom: 10px;">\
			Zapytaj o dostępność\
		</div>\
		<div id="queryPriceError" class="error" style="display:none"></div>\
		<div id="emailError" class="error" style="display:none"></div>		\
		<div style="margin: 5px 5px 5px 5px;">\
		<span>Email: </span><input type="text" name="queryPriceEmail" style="float:right">		\
		<input type="hidden" name="queryPriceProductLink">\
		</div>\
		\
		<div id="phoneError" class="error" style="display:none"></div>\
		<div style="margin: 5px 5px 5px 5px;">\
		<span>Telefon: </span><input type="text" name="queryPricePhone" style="float: right">			\
		</div>\
		<div style="margin: 5px 5px 5px 5px;">\
		<span>Imię nazwisko: </span><input type="text" name="queryPriceName" style="float: right">			\
		</div>\
		<input type="button" value="Zapytaj o dostępność" onclick="onQueryPrice()"  style="float:left; margin: 5px 5px 5px 5px;">		\
		<input type="button" value="Anuluj" onclick="onCloseDialog()" style="float:right; margin: 5px 5px 5px 5px;">\
	</div>');
	});			
