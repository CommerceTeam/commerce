function submitForm(sorting) {
	var form = $('.localeform');
	form.action = form.action + '&sorting=' + sorting;
	form.submit();
}