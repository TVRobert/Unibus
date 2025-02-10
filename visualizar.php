<?php
session_start();

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'unibus';  // Nombre de la base de datos
$username = 'root';
$password = '';  // Ajusta según tu configuración
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Si index.php pregunta si hubo un escaneo
if (isset($_GET['check'])) {
    echo isset($_SESSION['escaneo']) ? $_SESSION['escaneo'] : "0";
    exit;
}

// Si index.php pide resetear la sesión
if (isset($_GET['reset'])) {
    $_SESSION['escaneo'] = "0"; // Reseteamos el estado del escaneo
    exit;
}

// Cuando se escanea un QR, guardamos el estado y recargamos la página
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['qr_data'])) {
    // Extraemos los datos del QR
    $datosQR = explode('|', $_POST['qr_data']);
    $user_id = $datosQR[0]; // El ID del usuario desde el QR
    $nombre_usuario = $datosQR[1]; // Nombre del usuario desde el QR
    $fecha = date('Y-m-d');  // Fecha actual
    $hora = date('H:i:s');   // Hora actual

    // Verificar si el usuario ya ha escaneado más de 2 veces hoy
    $query = "SELECT * FROM escaneos WHERE IDQR = :user_id AND FECHA_ACTUAL = :fecha";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id, 'fecha' => $fecha]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        // Si ya hay un registro para el usuario en ese día, incrementamos el contador
        if ($resultado['CONTADOR'] >= 2) {
            $_SESSION['escaneo'] = "expirado"; // Indicamos que el pasaje expiró
            echo "Has alcanzado el límite de escaneos para hoy. Expiró tu pasaje.";
            exit;
        } else {
            // Si no ha alcanzado el límite, incrementamos el contador
            $nuevoContador = $resultado['CONTADOR'] + 1;
            $queryUpdate = "UPDATE escaneos SET CONTADOR = :nuevoContador WHERE IDQR = :user_id AND FECHA_ACTUAL = :fecha";
            $stmtUpdate = $pdo->prepare($queryUpdate);
            $stmtUpdate->execute(['nuevoContador' => $nuevoContador, 'user_id' => $user_id, 'fecha' => $fecha]);
        }
    } else {
        // Si no hay registros, insertamos uno nuevo con contador 1
        $queryInsert = "INSERT INTO escaneos (IDQR, FECHA_ACTUAL, CONTADOR) VALUES (:user_id, :fecha, 1)";
        $stmtInsert = $pdo->prepare($queryInsert);
        $stmtInsert->execute(['user_id' => $user_id, 'fecha' => $fecha]);
    }

    // Guardamos el estado del escaneo en la sesión
    $_SESSION['escaneo'] = "1";

    echo "QR registrado";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Visualizador - Seguridad Mejorada</title>
  <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
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
      overflow: hidden;
      flex-direction: column;
      text-align: center;
    }
    .container {
      max-width: 600px;
      width: 100%;
      padding: 10px;
    }
    #camara {
      width: 100%;
      border-radius: 10px;
      border: 5px solid #2c7a7b;
      max-height: 300px;
    }
    .mensaje {
      font-size: 1.5rem;
      color: #ffffff;
      background-color: rgba(0, 0, 0, 0.7);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      text-align: center;
      padding: 20px;
      border-radius: 8px;
    }
    #datosQR {
      text-align: center;
      margin-top: 20px;
    }
    /* Ajuste para móviles */
    @media (max-width: 600px) {
      .container {
        padding: 5px;
      }
      .mensaje {
        font-size: 1.2rem;
      }
      #datosQR {
        font-size: 0.9rem;
      }
      #camara {
        max-height: 250px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Visualizador - Seguridad Mejorada</h1>
    <p>¡Escanea el código QR y visualiza los datos!</p>

    <video id="camara" autoplay></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <!-- Cuadro de mensaje que cubre toda la pantalla -->
    <div class="mensaje" id="mensaje">
      <p id="mensajeTexto"></p>
    </div>

    <div id="datosQR">
      <p><strong>Nombre del Usuario:</strong> <span id="nombreUsuario">-</span></p>
      <p><strong>Fecha:</strong> <span id="fechaUsuario">-</span></p>
      <p><strong>Hora:</strong> <span id="horaUsuario">-</span></p>
    </div>
  </div>

  <script>
    function hablar(mensaje) {
        const utterance = new SpeechSynthesisUtterance(mensaje);
        utterance.lang = 'es-ES'; // Configura el idioma en español
        speechSynthesis.speak(utterance);
    }

    function mostrarDatos(datos) {
        // Enviar los datos del QR al servidor
        fetch('visualizar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            qr_data: datos.join('|')
          })
        })
        .then(() => {
            document.getElementById('nombreUsuario').textContent = datos[1]; // Muestra solo el nombre
            document.getElementById('fechaUsuario').textContent = new Date().toLocaleDateString(); // Fecha actual
            document.getElementById('horaUsuario').textContent = new Date().toLocaleTimeString(); // Hora actual

            // Verifica si el pasaje ha expirado
            const mensajeExpirado = <?php echo isset($_SESSION['escaneo']) && $_SESSION['escaneo'] == 'expirado' ? 'true' : 'false'; ?>;
            if (mensajeExpirado) {
                const mensaje = "Lo sentimos, pero tu QR ha expirado. Has alcanzado el límite de escaneos para hoy.";
                document.getElementById('mensajeTexto').textContent = mensaje;
                document.getElementById('mensaje').style.backgroundColor = 'red'; // Fondo rojo
                document.getElementById('mensaje').style.display = 'flex';
                hablar(mensaje); // Hablar el mensaje de expiración
                setTimeout(() => location.reload(), 3000); // Recarga la página después de 3 segundos
            } else {
                const mensajeBienvenida = "¡Bienvenido a Unibus! Disfruta de tu viaje.";
                document.getElementById('mensajeTexto').textContent = mensajeBienvenida;
                document.getElementById('mensaje').style.backgroundColor = 'green'; // Fondo verde
                document.getElementById('mensaje').style.display = 'flex';
                hablar(mensajeBienvenida); // Hablar el mensaje de bienvenida
                setTimeout(() => location.reload(), 2000); // Recarga la página después de 2 segundos
            }
        })
        .catch(error => console.error('Error:', error));
    }

    const video = document.getElementById('camara');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    let escaneado = false;

    // Acceso a la cámara frontal
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
          const datos = qrCode.data.split('|');
          mostrarDatos(datos);
        }
      }
      requestAnimationFrame(scanQRCode);
    }
  </script>
</body>
</html>
