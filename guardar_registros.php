<?php
$host = "localhost";  // Cambia si tu servidor es diferente
$user = "root";  // Usuario de MySQL
$password = "";  // Contraseña de MySQL
$database = "unibus";  // Base de datos

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idqr = $_POST["idqr"];
    $nombre = $_POST["nombre"];
    $numero = $_POST["numero"];
    $correo = $_POST["correo"];
    
    date_default_timezone_set("America/Mexico_City");  // Ajusta según tu zona horaria
    $fecha_actual = date("Y-m-d");
    $hora_actual = date("H:i:s");

    // Verificar si el IDQR ya está en la base de datos
    $sql = "SELECT CONTADOR FROM registros WHERE IDQR = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idqr);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        // Si ya existe, actualizar contador
        $fila = $resultado->fetch_assoc();
        $contador = $fila["CONTADOR"] + 1;
        $sql = "UPDATE registros SET FECHA_ACTUAL = ?, HORA_ACTUAL = ?, CONTADOR = ? WHERE IDQR = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssds", $fecha_actual, $hora_actual, $contador, $idqr);
    } else {
        // Si no existe, insertar nuevo registro
        $contador = 1;
        $sql = "INSERT INTO registros (IDQR, NOMBRE_COMPLETO, NUMERO, CORREO, FECHA_ACTUAL, HORA_ACTUAL, CONTADOR) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssd", $idqr, $nombre, $numero, $correo, $fecha_actual, $hora_actual, $contador);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "contador" => $contador, "nombre" => $nombre]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar en la base de datos"]);
    }

    $stmt->close();
}

$conn->close();
?>
