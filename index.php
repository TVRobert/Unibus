<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BIENVENIDO A UNIBUS</title>
  <style>
    body {
      background: #e6f2f0;
      font-family: 'Roboto', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      flex-direction: column;
      text-align: center;
    }
    .container {
      max-width: 600px;
      width: 100%;
      padding: 10px;
    }
    .mensaje {
      font-size: 1.5rem;
      color: #2c7a7b;
      margin-top: 20px;
      font-weight: bold;
    }
    .alerta {
      display: none; /* AHORA ESTÁ OCULTO DESDE EL INICIO */
      background: #4CAF50;
      color: white;
      padding: 15px;
      border-radius: 10px;
      margin-top: 20px;
      font-size: 1.2rem;
    }
    .footer {
      margin-top: 50px;
      font-size: 1rem;
      color: #555;
    }
  </style>
</head>
<body>

  <div class="container">
    <h1>BIENVENIDO A UNIBUS</h1>
    <p class="mensaje">Por favor escanea tu código QR en la parte superior</p>

    <div id="alerta" class="alerta"></div> <!-- El mensaje no tiene contenido hasta que se escanee un QR -->

    <div class="footer">
      <p>Parte de Juventud San Pedro</p>
    </div>
  </div>

  <script>
    function verificarEscaneo() {
      fetch('visualizar.php?check=1') // Pedimos a visualizar.php si hubo escaneo
        .then(response => response.text())
        .then(data => {
          if (data === "1") { // Solo si se detecta un escaneo
            let alerta = document.getElementById('alerta');
            alerta.textContent = "¡Disfruta tu abordaje!";
            alerta.style.display = 'block';

            // Reiniciamos la sesión del escaneo para que no siga apareciendo
            fetch('visualizar.php?reset=1');

            // Recargar la página en 2 segundos para limpiar el escaneo
            setTimeout(() => {
              location.reload();
            }, 2000);
          }
        })
        .catch(error => console.error('Error:', error));
    }

    setInterval(verificarEscaneo, 3000); // Verifica cada 3 segundos
  </script>

</body>
</html>
