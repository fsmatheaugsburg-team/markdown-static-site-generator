<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
    <link href="../css/base.css" rel="stylesheet">
    <style>
#build-output>* {
  padding: 0px 16px;
}
#build-output > ul {
  padding: 0px 32px;
}
#build-output > h1 {
  border-top: 1px solid #ddd;
  padding-top: 16px;
}
    </style>
  </head>
  <body>
    <main>
      <header>
        <h1>MSSG v1.0.0</h1>
      </header>
      <article>
        <button id="build-button">build</button>
        <input type="text" id="auth" placeholder="Auth key"/>
      </article>
      <div id="build-output"></div>
    </main>
    <script>
      let out = document.getElementById('build-output'),
         auth = document.getElementById('auth');
      document.getElementById('build-button').addEventListener('click', () => {
        out.innerText = "Loading...";
        fetch('generator.php?build', {headers: {Authorization: "Bearer " + encodeURIComponent(auth.value)}}).then(r => r.text()).then(t => out.innerHTML=t);
      })
    </script>
  </body>

</html>
