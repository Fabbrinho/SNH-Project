<?php 
session_start();
require_once 'csrf.php'; 
$csrfToken = getToken();

function showMessage($message, $type = "error") {
    $color = $type === "success" ? "#28a745" : "#dc3545"; 
    echo "<div id='error' style='padding: 10px; margin: 10px 0; border-radius: 5px; background: $color; color: white; text-align: center; font-weight: bold;'>
            $message <span id='countdown-timer'></span>
          </div>";
}

$errorMessage = $_SESSION['error_message'] ?? null;
$source = $_SESSION['source'] ?? null;
$unlockDate = $_SESSION['unlock_date'] ?? null;

if($errorMessage !== null)
  showMessage($errorMessage);
unset($_SESSION['error_message']);
unset($_SESSION['source']); 
unset($_SESSION['unlock_date']);


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
    function createInputField(id, name, type, labelText) {
        const fieldContainer = document.createElement("div");
        fieldContainer.classList.add("input-field");

        const input = document.createElement("input");
        input.id = id;
        input.name = name;
        input.type = type;
        input.required = true;

        const label = document.createElement("label");
        label.htmlFor = id;
        label.textContent = labelText;

        fieldContainer.appendChild(input);
        fieldContainer.appendChild(label);

        return fieldContainer;
    }
    function createInputToken(type, name, value) {
        const fieldContainer = document.createElement("div");
        fieldContainer.classList.add("input-field");

        const input = document.createElement("input");
        input.name = name;
        input.type = type;
        input.value = value;
        input.required = true;

        fieldContainer.appendChild(input);

        return fieldContainer;
    }

    function showRegisterForm() {
        const formContainer = document.getElementById("form-container");
        formContainer.innerHTML = ""; 
        formContainer.style.display = "block";

        const form = document.createElement("form");
        form.action = "register.php";
        form.method = "POST";
        form.classList.add("col", "s12");

        form.appendChild(createInputField("username", "username", "text", "Username"));
        form.appendChild(createInputToken( "hidden", "token_csrf",csrfToken));
        form.appendChild(createInputField("email", "email", "email", "Email"));
        
        // Password Field
        const passwordField = createInputField("password", "password", "password", "Password");
        passwordField.firstChild.setAttribute("oninput", "checkPasswordStrength(this.value)");
        form.appendChild(passwordField);
        
        const passwordStrength = document.createElement("div");
        passwordStrength.id = "password-strength";
        form.appendChild(passwordStrength);

        // reCAPTCHA
        const recaptchaContainer = document.createElement("div");
        recaptchaContainer.id = "recaptcha-register";
        form.appendChild(recaptchaContainer);
        
        const submitButton = document.createElement("button");
        submitButton.type = "submit";
        submitButton.classList.add("btn", "blue");
        submitButton.id = "registerBtn";
        submitButton.textContent = "Register";
        submitButton.disabled = true;

        form.appendChild(submitButton);
        formContainer.appendChild(form);

        if (!recaptchaContainer.hasChildNodes()) {
            grecaptcha.render("recaptcha-register", {
                sitekey: RECAPTCHA_SITE_KEY,
                callback: recaptchaRegisterVerified,
                'expired-callback': recaptchaRegisterExpired
            });
        }
    }

    function showLoginForm() {
        const formContainer = document.getElementById("form-container");
        formContainer.innerHTML = ""; 
        formContainer.style.display = "block";

        const form = document.createElement("form");
        form.action = "login.php";
        form.method = "POST";
        form.classList.add("col", "s12");

        form.appendChild(createInputField("email", "email", "text", "Email"));
        form.appendChild(createInputToken( "hidden", "token_csrf",csrfToken));
        form.appendChild(createInputField("password", "password", "password", "Password"));

        // reCAPTCHA
        const recaptchaContainer = document.createElement("div");
        recaptchaContainer.id = "recaptcha-login";
        form.appendChild(recaptchaContainer);
        
        const submitButton = document.createElement("button");
        submitButton.type = "submit";
        submitButton.classList.add("btn", "green");
        submitButton.id = "loginBtn";
        submitButton.textContent = "Login";
        submitButton.disabled = true;

        form.appendChild(submitButton);
        formContainer.appendChild(form);

        if (!recaptchaContainer.hasChildNodes()) {
            grecaptcha.render("recaptcha-login", {
                sitekey: RECAPTCHA_SITE_KEY,
                callback: recaptchaLoginVerified,
                'expired-callback': recaptchaLoginExpired
            });
        }
    }

    document.getElementById("register-btn").addEventListener("click", showRegisterForm);
    document.getElementById("login-btn").addEventListener("click", showLoginForm);
    <?php if ($source === "LOGIN"): ?>
      window.onload = function() { showLoginForm(); };
    <?php endif; ?>
    <?php if ($source === "REGISTER"): ?>
      window.onload = function() { showRegisterForm(); };
    <?php endif; ?>
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

    function recaptchaLoginVerified() {
        document.getElementById("loginBtn").disabled = false;
    }

    function recaptchaLoginExpired() {
        document.getElementById("loginBtn").disabled = true;
    }

    function recaptchaRegisterVerified() {
        document.getElementById("registerBtn").disabled = false;
    }

    function recaptchaRegisterExpired() {
        document.getElementById("registerBtn").disabled = true;
    }

    <?php if ($unlockDate): ?>
      const unlockTimestamp = <?php echo $unlockDate * 1000; ?>;
      const countdownElement = document.getElementById("countdown-timer");
      const countdownContainer = document.getElementById("error");

      function updateCountdown() {
          const now = new Date().getTime();
          const timeLeft = unlockTimestamp - now;

          if (timeLeft <= 0) {
              countdownContainer.style.display = "none";
              return;
          }

          const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
          const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

          countdownElement.textContent = `${minutes}m ${seconds}s`;
          countdownContainer.style.display = "block";

          setTimeout(updateCountdown, 1000);
      }

      updateCountdown();
    <?php endif; ?>

  </script>
</body>
</html>
