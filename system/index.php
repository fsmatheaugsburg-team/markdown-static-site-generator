<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
    <h1>MSSG v1.0.0</h1>
    <button id="build-button">build</button>
    <pre id="build-output"></pre>
  </body>

  <script>
    let out = document.getElementById('build-output')
    document.getElementById('build-button').addEventListener('click', () => {
      out.innerText = "Loading...";
      fetch('generator.php?build').then(r => r.text()).then(t => out.innerText=t);
    })
  </script>
</html>
