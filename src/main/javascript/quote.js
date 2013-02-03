var url = 'http://qotd.ralph-schuster.eu/qotd.pl'; 

var soapMessage = 
	'<?xml version="1.0" encoding="UTF-8"?>' +
	'<soap:Envelope ' + 
	'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' +
	'xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" ' +
	'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' +
	'soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' +
	'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' +
	'<soap:Body>' +
		'<getquote  xmlns="http://qotd.ralph-schuster.eu/Quote" xsi:nil="true"/>' +
	'</soap:Body>' +
	'</soap:Envelope>';
 
function getquoteAjax() /*Add parameters and what not*/ { 

	$.ajax({ 
		url: url, 
		type: 'POST', 
		data: soapMessage,
		dataType: 'xml',
		cache: false, 
		processData: false,
		headers: { 
			SOAPAction: "\"http://qotd.ralph-schuster.eu/Quote#getquote\""  
		},
		success: function(data, status, req, xml, xmlHttpRequest, responseXML) { 
			var quote  = $(data).find('quote').text(); 
			var author = $(data).find('author').text(); 
			alert(quote+' ('+author+')');
		}, 
		error: function(data, status, req){ 
			alert(req.responseText + " " + status);
		}, 
		contentType: 'text/xml; charset="utf-8"', 
	}); 
} 

 
