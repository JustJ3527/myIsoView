<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="css/style.css">

    <script src="https://kit.fontawesome.com/2dd49550a9.js" crossorigin="anonymous"></script>

    <title>Sign up</title>
</head>
<body>
    <?php
        if(isset($_GET['login_err']))
        {
            $err = htmlspecialchars($_GET['login_err']);

            switch($err)
            {
                case 'password':
                ?>
                    <div class="alert alert-danger">
                        <strong>Error</strong> Wrong password
                    </div>
                <?php
                break; 

                case 'email':
                ?>
                    <div class="alert alert-danger">
                        <strong>Error</strong> Invalid Email Format Error
                    </div>
                <?php
                break;
            }
        }
    ?>

    <form action="./php_back/login_processing.php" method="POST">
        <input  type="text" name="email" placeholder="Email" id="email" aria-required="true" required="required" autocomplete="email">
        <span aria-hidden="true" id="show-password" class="btn btn-simple btn-default pull-right">
            <i class="fa fa-eye" id="toggle" onclick="showHide();"></i>
        </span>
        
        <input name="password" placeholder="Password" type="password" required="required" aria-required="true" required="required" autocomplete="email" id="password_login">
            
        <button  type="submit" >Login</button>
    </form>
    <br><br>
    <a href="./registration.php">Sign in</a>

    <script type="text/javascript">
		const password = document.getElementById('password_connexion');
		const toggle = document.getElementById('toogle');

		function showHide(){
			if(password.type === 'password'){
				password.setAttribute('type', 'text');
				toggle.classList.add('hide')
			}
			else{
				password.setAttribute('type', 'password');
				toggle.classList.remove('hide')	
			}
		}
	</script>
</body>
</html>