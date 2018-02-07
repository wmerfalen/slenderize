function login(){
	alert($('#user').val());
	$.ajax({type: 'post',
		data: {
			'user': $('#user').val(),
			'password': $('#password').val(),
			'login': true
		}})
		.done(function(msg){
			console.log(msg);
			if(msg.login){
				location.href='home.php';
			}else{
				alert('Could not login');
			}
		});
}
