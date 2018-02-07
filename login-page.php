<html>
	<head>
		<title>Login</title>
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript" src="js/login.js?cache-breaker=<?= uniqid();?>"></script>
	</head>
<body>
	<form onsubmit="login();">
		<input type="text" id="user"/>
		<input type="password" id="password"/>
	</form>
	<button onclick="login();">login</button>
</body>
