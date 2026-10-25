
<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "el_hangar_api";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$base_datos;charset=utf8",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "mensaje" => "Error al conectar con la base de datos"
    ]);
    exit;
}
?>
