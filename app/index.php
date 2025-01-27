<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Novelists App</title>
  <!-- MaterializeCSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
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
  </style>
</head>
<body class="grey lighten-4">
  <div class="container main-container">
    <h1 class="black-text">Welcome to Novelists</h1>
    <p class="grey-text">A platform for sharing and exploring creative novels!</p>

    <!-- Buttons for Register and Login -->
    <div class="row">
      <div class="col s12 m6">
        <button id="register-btn" class="waves-effect waves-light btn-large blue">
          <i class="material-icons left"></i> Register
        </button>
      </div>
      <div class="col s12 m6">
        <button id="login-btn" class="waves-effect waves-light btn-large green">
          <i class="material-icons left"></i> Login
        </button>
      </div>
    </div>

    <!-- Dynamic Form Container -->
    <div id="form-container" class="row" style="display: none;">
      <!-- Forms will be dynamically added here -->
    </div>
  </div>

  <script>
    // JavaScript for toggling between forms
    document.getElementById("register-btn").addEventListener("click", () => {
      document.getElementById("form-container").innerHTML = `
        <form action="register.php" method="POST" class="col s12">
        <div class="input-field">
            <input id="username" name="username" type="text" required>
            <label for="username">Username</label>
        </div>
        <div class="input-field">
            <input id="email" name="email" type="email" required>
            <label for="email">Email</label>
        </div>
        <div class="input-field">
            <input id="password" name="password" type="password" required>
            <label for="password">Password</label>
        </div>
        <button type="submit" class="btn blue">Register</button>
        </form>
      `;
      document.getElementById("form-container").style.display = "block";
    });

    document.getElementById("login-btn").addEventListener("click", () => {
      document.getElementById("form-container").innerHTML = `
        <form action="login.php" method="POST" class="col s12">
        <div class="input-field">
            <input id="username" name="username" type="text" required>
            <label for="username">Username</label>
        </div>
        <div class="input-field">
            <input id="password" name="password" type="password" required>
            <label for="password">Password</label>
        </div>
        <button type="submit" class="btn green">Login</button>
        </form>
      `;
      document.getElementById("form-container").style.display = "block";
    });
  </script>
</body>
</html>
