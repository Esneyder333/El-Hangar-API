
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../db.php";

$metodo = $_SERVER["REQUEST_METHOD"];

try {
    switch ($metodo) {

        case "GET":
            $consulta = $conexion->query(
                "SELECT * FROM productos ORDER BY id"
            );

            echo json_encode($consulta->fetchAll(PDO::FETCH_ASSOC));
            break;

        case "POST":
            $datos = json_decode(file_get_contents("php://input"), true);

            if (
                !isset($datos["nombre"], $datos["categoria"],
                $datos["precio"], $datos["stock"])
            ) {
                http_response_code(400);
                echo json_encode(["mensaje" => "Faltan datos"]);
                break;
            }

            $sql = "INSERT INTO productos
                    (nombre, categoria, precio, stock)
                    VALUES (?, ?, ?, ?)";

            $consulta = $conexion->prepare($sql);
            $consulta->execute([
                $datos["nombre"],
                $datos["categoria"],
                $datos["precio"],
                $datos["stock"]
            ]);

            http_response_code(201);
            echo json_encode(["mensaje" => "Producto registrado correctamente"]);
            break;

        case "PUT":
            $datos = json_decode(file_get_contents("php://input"), true);

            if (
                !isset($datos["id"], $datos["nombre"],
                $datos["categoria"], $datos["precio"], $datos["stock"])
            ) {
                http_response_code(400);
                echo json_encode(["mensaje" => "Faltan datos"]);
                break;
            }

            $sql = "UPDATE productos
                    SET nombre=?, categoria=?, precio=?, stock=?
                    WHERE id=?";

            $consulta = $conexion->prepare($sql);
            $consulta->execute([
                $datos["nombre"],
                $datos["categoria"],
                $datos["precio"],
                $datos["stock"],
                $datos["id"]
            ]);

            echo json_encode([
                "mensaje" => $consulta->rowCount() > 0
                    ? "Producto actualizado correctamente"
                    : "No se encontró el producto o no hubo cambios"
            ]);
            break;

        case "DELETE":
            $id = $_GET["id"] ?? null;

            if (!$id || !ctype_digit((string)$id)) {
                http_response_code(400);
                echo json_encode(["mensaje" => "Debe indicar un ID válido"]);
                break;
            }

            $consulta = $conexion->prepare(
                "DELETE FROM productos WHERE id = ?"
            );
            $consulta->execute([$id]);

            echo json_encode([
                "mensaje" => $consulta->rowCount() > 0
                    ? "Producto eliminado correctamente"
                    : "Producto no encontrado"
            ]);
            break;

        default:
            header("Allow: GET, POST, PUT, DELETE");
            http_response_code(405);
            echo json_encode(["mensaje" => "Método no permitido"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error al procesar la solicitud"]);
}
?>
