
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../db.php";

$metodo = $_SERVER["REQUEST_METHOD"];

try {
    switch ($metodo) {

        case "GET":
            $sql = "SELECT * FROM ventas ORDER BY id DESC";
            $consulta = $conexion->query($sql);

            echo json_encode(
                $consulta->fetchAll(PDO::FETCH_ASSOC)
            );
            break;

        case "POST":
            $datos = json_decode(
                file_get_contents("php://input"), true
            );

            if (
                !isset($datos["productos"]) ||
                !is_array($datos["productos"]) ||
                count($datos["productos"]) == 0
            ) {
                http_response_code(400);
                echo json_encode([
                    "mensaje" => "Debe enviar al menos un producto"
                ]);
                break;
            }

            $conexion->beginTransaction();

            $total = 0;
            $detalles = [];

            foreach ($datos["productos"] as $producto) {
                if (
                    !isset($producto["producto_id"], $producto["cantidad"]) ||
                    !is_numeric($producto["producto_id"]) ||
                    !ctype_digit((string)$producto["cantidad"]) ||
                    (int)$producto["producto_id"] < 1 ||
                    (int)$producto["cantidad"] < 1
                ) {
                    throw new InvalidArgumentException(
                        "Producto o cantidad inválidos"
                    );
                }

                $consulta = $conexion->prepare(
                    "SELECT precio, stock FROM productos WHERE id = ?"
                );
                $consulta->execute([$producto["producto_id"]]);
                $item = $consulta->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new InvalidArgumentException(
                        "No existe uno de los productos enviados"
                    );
                }

                if ((int)$item["stock"] < (int)$producto["cantidad"]) {
                    throw new InvalidArgumentException(
                        "No hay suficiente stock para un producto"
                    );
                }

                $cantidad = (int)$producto["cantidad"];
                $precio = (float)$item["precio"];
                $total += $precio * $cantidad;

                $detalles[] = [
                    "producto_id" => (int)$producto["producto_id"],
                    "cantidad" => $cantidad,
                    "precio" => $precio
                ];
            }

            $consulta = $conexion->prepare(
                "INSERT INTO ventas (total) VALUES (?)"
            );
            $consulta->execute([$total]);

            $ventaId = $conexion->lastInsertId();

            foreach ($detalles as $detalle) {
                $consulta = $conexion->prepare(
                    "INSERT INTO detalle_venta
                    (venta_id, producto_id, cantidad, precio)
                    VALUES (?, ?, ?, ?)"
                );

                $consulta->execute([
                    $ventaId,
                    $detalle["producto_id"],
                    $detalle["cantidad"],
                    $detalle["precio"]
                ]);

                $consulta = $conexion->prepare(
                    "UPDATE productos SET stock = stock - ? WHERE id = ?"
                );

                $consulta->execute([
                    $detalle["cantidad"],
                    $detalle["producto_id"]
                ]);
            }

            $conexion->commit();

            http_response_code(201);
            echo json_encode([
                "mensaje" => "Venta registrada correctamente",
                "venta_id" => $ventaId,
                "total" => $total
            ]);
            break;

        default:
            header("Allow: GET, POST");
            http_response_code(405);
            echo json_encode([
                "mensaje" => "Método no permitido"
            ]);
    }

} catch (InvalidArgumentException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    http_response_code(400);
    echo json_encode(["mensaje" => $e->getMessage()]);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "mensaje" => "Error al procesar la venta"
    ]);
}
?>
