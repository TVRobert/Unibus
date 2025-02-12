<?php 
session_start();
date_default_timezone_set('America/Mexico_City'); // Ajusta según tu zona horaria

// Conexión a la base de datos
$servername = "localhost"; // Cambia si es necesario
$username = "root"; // Usuario de la BD
$password = ""; // Contraseña de la BD
$dbname = "unibus"; // Base de datos

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Procesar el QR escaneado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $datos = explode('|', $_POST['qrData']);

    if (count($datos) >= 8) {
        $idQR = $datos[0];
        $nombreCompleto = $datos[1];
        $telefono = $datos[4];
        $correo = $datos[5];
        $fechaActual = date("Y-m-d");
        $horaActual = date("H:i:s");

        // Verificar cuántas veces ha escaneado hoy
        $sql = "SELECT CONTADOR FROM registros WHERE IDQR = ? AND FECHA_ACTUAL = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $idQR, $fechaActual);
        $stmt->execute();
        $result = $stmt->get_result();
        $contador = 0;

        if ($row = $result->fetch_assoc()) {
            $contador = $row["CONTADOR"];
        }

        if ($contador < 2) {
            $contador++;

            if ($contador == 1) {
                $mensaje = "¡Bienvenido a Unibus, $nombreCompleto! Disfruta de tu viaje.";
            } else {
                $mensaje = "¡Disfruta tu viaje a casa, $nombreCompleto!";
            }

            if ($contador == 1) {
                $sql = "INSERT INTO registros (IDQR, NOMBRE_COMPLETO, NUMERO, CORREO, FECHA_ACTUAL, HORA_ACTUAL, CONTADOR) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            } else {
                $sql = "UPDATE registros SET HORA_ACTUAL = ?, CONTADOR = ? WHERE IDQR = ? AND FECHA_ACTUAL = ?";
            }

            $stmt = $conn->prepare($sql);
            if ($contador == 1) {
                $stmt->bind_param("ssssssi", $idQR, $nombreCompleto, $telefono, $correo, $fechaActual, $horaActual, $contador);
            } else {
                $stmt->bind_param("siss", $horaActual, $contador, $idQR, $fechaActual);
            }
            $stmt->execute();

            echo json_encode(["mensaje" => $mensaje, "estado" => "ok"]);
        } else {
            echo json_encode(["mensaje" => "¡Ya has registrado los dos escaneos de hoy!", "estado" => "error"]);
        }
    } else {
        echo json_encode(["mensaje" => "Código QR inválido", "estado" => "error"]);
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unibus - Escáner QR</title>
  <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
  <style>
    body {
      background: #e6f2f0;
      font-family: 'Roboto', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      text-align: center;
      overflow-y: auto; /* Permite hacer scroll si es necesario */
    }

    .container {
      width: 90%;
      max-width: 450px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
    }

    .contenedor-cuadro {
      background: #ffffff;
      padding: 15px;
      border-radius: 10px;
      border: 2px solid #2c7a7b;
      box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
    }

    #camara {
      width: 100%;
      max-width: 300px;
      aspect-ratio: 16/9;
      border-radius: 8px;
      border: 3px solid #2c7a7b;
      object-fit: cover;
      margin: 10px 0;
    }

    .logo-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      gap: 5px;
    }

    #logo {
      max-width: 140px;
    }

    #logo3 {
      max-width: 160px;
      margin-top: -10px;
    }

    .mensaje {
      background-color: green;
      padding: 15px;
      color: white;
      font-weight: bold;
      border-radius: 8px;
      margin-top: 15px;
      display: none;
      width: 90%;
    }

  </style>
</head>
<body>
  <div class="container">
    <div class="logo-container">
      <img id="logo" src="imagenes/logo.png" alt="Logo de la empresa" />
    </div>
    <div class="contenedor-cuadro">
      <p><strong>¡Escanea tu código QR y prepárate para un viaje seguro y cómodo!</strong></p>
      <video id="camara" autoplay></video>
      <canvas id="canvas" style="display:none;"></canvas>
      <p><strong>¡Solo escanea el código QR y accede a toda la información de tu viaje!</strong></p>
      <div class="mensaje" id="mensaje">
        <p id="mensajeTexto"></p>
      </div>
    </div>
    <div class="logo-container">
      <img id="logo3" src="imagenes/logo3.png" alt="Otra imagen" />
    </div>
  </div>

  <script>
    function hablar(mensaje) {
      const utterance = new SpeechSynthesisUtterance(mensaje);
      utterance.lang = 'es-ES';
      speechSynthesis.speak(utterance);
    }

    function mostrarMensaje(mensaje, estado) {
      document.getElementById('mensajeTexto').textContent = mensaje;
      const mensajeBox = document.getElementById('mensaje');
      mensajeBox.style.backgroundColor = estado === "ok" ? "green" : "red";
      mensajeBox.style.display = 'block';
      hablar(mensaje);
      setTimeout(() => location.reload(), 3000);
    }

    const video = document.getElementById('camara');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    let escaneado = false;

    navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
      .then(stream => {
        video.srcObject = stream;
        requestAnimationFrame(scanQRCode);
      })
      .catch(error => alert("No se pudo acceder a la cámara frontal."));

    function scanQRCode() {
      if (video.readyState === video.HAVE_ENOUGH_DATA && !escaneado) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);

        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

        if (qrCode) {
          escaneado = true;
          fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "qrData=" + encodeURIComponent(qrCode.data)
          })
          .then(response => response.json())
          .then(data => mostrarMensaje(data.mensaje, data.estado))
          .catch(error => console.error("Error:", error));
        }
      }
      requestAnimationFrame(scanQRCode);
    }
  </script>
</body>
</html>
