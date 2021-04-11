// Output maybe previous detected font size
var detectedFontSize = document.cookie.match(/detectedFontSize=(\d+.\d+)/);
var detectedBodyWidth = document.cookie.match(/detectedBodyWidth=(\d+.\d+)/);
if (detectedFontSize && detectedBodyWidth) {
	var css = "body, pre, span.textfilltest { font-size: "+detectedFontSize[1]+"px; }\n";
	css +=  "#pages { opacity: 1; }\n";
	css +=  "ul.inpage-pages, div.alert, .DISABLED-ad--below { width: " + detectedBodyWidth[1] +"px; }\n";
	var style = document.createElement('style');
	style.type = 'text/css';
	style.appendChild(document.createTextNode(css));
	document.head.appendChild(style);
}
