<?php
session_start();
$error = null;
$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
if (empty (!$_SESSION['authorized'])) {
  header('Location: ' . $protocol . $_SERVER['HTTP_HOST'] . '/');
  exit;
}

require_once (__DIR__ . '/../vendor/autoload.php');

$dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  $authorized = (function () {
    if ($_POST['username'] !== getenv('USERNAME')) {
      return false;
    }
    
    if ($_POST['password'] !== getenv('PASSWORD')) {
      return false;
    }

    return true;
  })();

  if ($authorized) {
    $_SESSION['authorized'] = true;
    header('Location: ' . $protocol . $_SERVER['HTTP_HOST'] . '/');
  } else {
    $error = 'Bad credentials';
  }
}

?><!doctype html>
<html data-ng-app="notesApp">
  <head>
    <link rel="stylesheet" href="/css/page.css" />
  </head>
  <body>
    <div>
      <aside>
      </aside>

      <main>
        <?php if ($error): ?><h1><?php echo $error ?></h1><?php endif ?>
        <form method="post" action="/login.php">
          <label>
            <span style="display: block">Username:</span>
            <input type="text" name="username" />
          </label>

          <label>
            <span style="display: block">Password:</span>
            <input type="password" name="password" />
          </label>
          
          <p>
            <input type="submit" value="Login" />
          </p>
        </form>
      </main>
  </body>
</html>

