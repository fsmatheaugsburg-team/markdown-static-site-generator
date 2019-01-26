<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
    <h1>MSSG v1.0.0</h1>
    <button id="build-button">build</button>
    <input type="text" id="auth" placeholder="Auth key"/>
    <pre id="build-output"></pre>
    <script>
      let out = document.getElementById('build-output'),
         auth = document.getElementById('auth');
      document.getElementById('build-button').addEventListener('click', () => {
        out.innerText = "Loading...";
        fetch('generator.php?build', {headers: {Authorization: "Bearer " + encodeURIComponent(auth.value)}}).then(r => r.text()).then(t => out.innerText=t);
      })
    </script>
  </body>

</html>
