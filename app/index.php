<?php 
session_start();
require_once 'csrf.php'; 
$csrfToken = getToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Novelists App</title>
  
  <!-- MaterializeCSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  
  <!-- Include zxcvbn.js for password strength checking -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>

  <!-- reCAPTCHA -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <style>
    .main-container {
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    #form-container {
      margin-top: 20px;
    }
    #password-strength {
      margin-top: 10px;
      font-weight: bold;
    }
  </style>
</head>
<body class="grey lighten-4">
  <div class="container main-container">
    <h1 class="black-text">Welcome to Novelists</h1>
    <p class="grey-text">A platform for sharing and exploring creative novels!</p>
    
    <div class="row">
      <div class="col s12 m6">
        <button id="register-btn" class="waves-effect waves-light btn-large blue">Register</button>
      </div>
      <div class="col s12 m6">
        <button id="login-btn" class="waves-effect waves-light btn-large green">Login</button>
      </div>
    </div>

    <div id="form-container" class="row" style="display: none;"></div>
  </div>

  <script>
    const RECAPTCHA_SITE_KEY = "6LdAqcsqAAAAAIA_1xSmHxjA6CwOKXyUyrX5RGEY";
    const csrfToken = "<?php echo $csrfToken; ?>";
    document.getElementById("register-btn").addEventListener("click", () => {
      document.getElementById("form-container").innerHTML = `
        <form action="register.php" method="POST" class="col s12">
          <div class="input-field">
              <input type="hidden" name="token_csrf" value="${csrfToken}">
              <input id="username" name="username" type="text" required>
              <label for="username">Username</label>
          </div>
          <div class="input-field">
              <input id="email" name="email" type="email" required>
              <label for="email">Email</label>
          </div>
          <div class="input-field">
              <input id="password" name="password" type="password" required oninput="checkPasswordStrength(this.value)">
              <label for="password">Password</label>
              <div id="password-strength"></div>
          </div>

          <!-- reCAPTCHA -->
          <div id="recaptcha-register"></div>
          <br>

          <button type="submit" class="btn blue" id="registerBtn" disabled>Register</button>
        </form>
      `;
      document.getElementById("form-container").style.display = "block";
      loadRecaptcha("recaptcha-register", recaptchaRegisterVerified, recaptchaRegisterExpired);
    });

    document.getElementById("login-btn").addEventListener("click", () => {
      document.getElementById("form-container").innerHTML = `
        <form id="login-form" action="login.php" method="POST" class="col s12">
          <div class="input-field">
              <input type="hidden" name="token_csrf" value="${csrfToken}">
              <input id="username" name="username" type="text" required>
              <label for="username">Username</label>
          </div>
          <div class="input-field">
              <input id="password" name="password" type="password" required>
              <label for="password">Password</label>
          </div>
          <div id="recaptcha-login"></div>
          <br>
          <button type="submit" class="btn green" id="loginBtn" disabled>Login</button>
        </form>
      `;
      document.getElementById("form-container").style.display = "block";
      loadRecaptcha("recaptcha-login", recaptchaLoginVerified, recaptchaLoginExpired);
    });

    function loadRecaptcha(elementId, successCallback, expiredCallback) {
      if (document.getElementById(elementId).innerHTML.trim() === "") {
        grecaptcha.render(elementId, {
          sitekey: RECAPTCHA_SITE_KEY,
          callback: successCallback,
          'expired-callback': expiredCallback
        });
      }
    }

    function checkPasswordStrength(password) {
      const strengthText = document.getElementById("password-strength");
      if (password.length === 0) {
        strengthText.innerHTML = "";
        return;
      }

      const result = zxcvbn(password);
      const strength = result.score;
      let message = "";
      let color = "red";

      switch (strength) {
        case 0: message = "Very Weak"; break;
        case 1: message = "Weak"; break;
        case 2: message = "Moderate"; color = "orange"; break;
        case 3: message = "Strong"; color = "green"; break;
        case 4: message = "Very Strong"; color = "darkgreen"; break;
      }

      strengthText.innerHTML = `<span style="color: ${color};">${message}</span>`;
    }

    // reCAPTCHA verification callback functions for login
    function recaptchaLoginVerified() {
      document.getElementById("loginBtn").disabled = false;
    }

    function recaptchaLoginExpired() {
      document.getElementById("loginBtn").disabled = true;
    }

    // reCAPTCHA verification callback functions for registration
    function recaptchaRegisterVerified() {
      document.getElementById("registerBtn").disabled = false;
    }

    function recaptchaRegisterExpired() {
      document.getElementById("registerBtn").disabled = true;
    }
  </script>
</body>
</html>
