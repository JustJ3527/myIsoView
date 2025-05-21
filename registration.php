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
        if(isset($_GET['reg_err']))
        {
            $err = htmlspecialchars($_GET['reg_err']);

            switch($err)
            {
                case 'password':
                ?>
                    <div class="alert alert-danger">
                        <strong>Error</strong>  Different passwords
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

                case 'email_already_used':
                    ?>
                        <div class="alert alert-danger">
                            <strong>Error</strong> Email already used
                        </div>
                <?php
                break;

                case 'email_length':
                ?>
                    <div class="alert alert-danger">
                        <strong>Error</strong> Email too long
                    </div>
                <?php 
                break;

                case 'no_email':
                    ?>
                        <div class="alert alert-danger">
                            <strong>Error</strong> No account with this email, please sign in
                        </div>
                <?php 
                break;
            }
        }
    ?>
    <form method="post" action="./php_back/registration_processing.php">
        <input type="text" id="lname" name="lname" placeholder="Last name" aria-required="true" required="required" autocomplete="given-name">
        <br>
        <input type="text" id="fname" name="fname" placeholder="First name" aria-required="true" required="required" autocomplete="given-name">
        <br>
        <br>
        <input type="email" id="email" name="email" placeholder="Email" required="required" aria-required="true" autocomplete="email">
        <br>
        <br>
        <input type="password" id="password" name="password" placeholder="Your password" required="required" aria-required="true" autocomplete="password">
        <br>
        <input type="password" id="password_retype" name="password_retype" placeholder="Re-type your password" required="required" aria-required="true" autocomplete="password">
        <br>
        <br>
        <br>

        <button  type="submit">Sign in</button>

    </form>
    <br><br>
    <a href="./login.php">Login</a>
</body>
</html>