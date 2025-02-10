<?php
$servername = "localhost";  // Servidor de base de datos (generalmente localhost)
$username = "root";         // Usuario de MySQL (por defecto en XAMPP es root)
$password = "";             // Contraseña de MySQL (por defecto vacía)
$dbname = "unibus";         // Nombre de la base de datos

// Crear conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idqr = $_POST['idqr'] ?? 'Desconocido';
    $nombre_completo = $_POST['nombre_completo'] ?? 'Desconocido';
    $numero = $_POST['numero'] ?? 'No registrado';
    $correo = $_POST['correo'] ?? 'No registrado';

    // Verificar si el QR ya fue escaneado hoy
    $fecha_hoy = date("Y-m-d");
    $sql_check = "SELECT CONTADOR FROM registros WHERE IDQR = ? AND FECHA_ACTUAL = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("ss", $idqr, $fecha_hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        // Si ya existe el registro, aumentar el contador
        if ($row['CONTADOR'] >= 2) {
            echo "EXPIRO SU PASAJE";
        } else {
            $contador_nuevo = $row['CONTADOR'] + 1;
            $sql_update = "UPDATE registros SET CONTADOR = ? WHERE IDQR = ? AND FECHA_ACTUAL = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("iss", $contador_nuevo, $idqr, $fecha_hoy);
            $stmt_update->execute();
            echo "QR ESCANEADO. VECES: " . $contador_nuevo;
        }
    } else {
        // Insertar nuevo registro
        $sql_insert = "INSERT INTO registros (IDQR, NOMBRE_COMPLETO, NUMERO, CORREO, FECHA_ACTUAL, HORA_ACTUAL, CONTADOR) 
                       VALUES (?, ?, ?, ?, CURRENT_DATE, CURRENT_TIME, 1)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ssss", $idqr, $nombre_completo, $numero, $correo);
        $stmt_insert->execute();
        echo "QR ESCANEADO POR PRIMERA VEZ";
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
} else {
    echo "Acceso denegado.";
}
?>
