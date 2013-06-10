function submitForm(sorting) {
	var form   = $('localeform');
 	$('localeform').action = form.action + '&sorting=' + sorting;
 	
	form.submit();
}