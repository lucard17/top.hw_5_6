<?php
include_once('functions.php');
include_once('dbconnect.php');
session_start();
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    http_response_code(404);
}

$getNames = ['id', 'p_id'];
$updateNames = ['name', 'brand', 'description', 'price', 'p_id'];
$insertNames = ['name', 'p_id'];



if (!isset($_POST["action"])) {
    exit("action is not set");
}
if (!isset($_POST["type"])) {
    exit("type is not set");
}
$table;
switch ($_POST["type"]) {
    case "sector":
        $table = "sectors";
        break;
    case "category":
        $table = "categories";
        break;
    case "product":
        $table = "products";
        break;
}
if ($table == "products" && isset($_POST["deleteImages"])) {
    $productImagesNumber = removeProductImages($_POST['id'], json_decode($_POST["deleteImages"]));
}
if ($table == "products" && isset($_FILES['files'])) {
    $productImagesNumber = insertProductImages($_POST['id'], $_FILES);
}

try {
    switch ($_POST["action"]) {
        case "select":
            $query = "SELECT * FROM $table";
            $parameters = [];
            foreach ($getNames as $name) {
                if (isset($_POST[$name])) {
                    // $query .= " $name =:$name,";
                    $parameters[$name] = $_POST[$name];
                }
            }
            if (count($parameters) > 0) {
                $query .= " WHERE";
                foreach ($parameters as $name => $value) {
                    $query .= " $name =:$name,";
                }
                $query = preg_replace("/,$/", "", $query);
                $query .= " ORDER BY id";
                $stmt = pdo()->prepare($query);
                $stmt->execute($parameters);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $query .= " ORDER BY id";
                $stmt = pdo()->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($table == 'products' && isset($_POST['productForm'])) {
                for ($i = 0; $i < count($result); $i++) {
                    if ($result[$i]["images_number"] > 0) {
                        $result[$i]['images'] =  getProductImagesPathNames($result[$i]['id']);
                    } else {
                        $result[$i]['images'] = [];
                    }
                }
                // foreach ($result as $product) {
                //     if ($product["images_number"] > 0) {
                //         $product['images'] = getProductImagesPathNames($product['id']);
                //     }
                // }
            }
            exit(json_encode($result));
            break;
        case "insert":
            $query = "INSERT INTO $table ";
            $parameters = [];
            $query .= "(";
            foreach ($insertNames as $name) {
                if (isset($_POST[$name])) {
                    $parameters[$name] = $_POST[$name];
                }
            }
            foreach ($parameters as $name => $value) {
                $query .= "$name,";
            }
            $query = preg_replace("/,$/", "", $query);
            $query .= ") VALUES (";
            foreach ($parameters as $name => $value) {
                $query .= ":$name,";
            }
            $query = preg_replace("/,$/", "", $query);
            $query .= ")";
            $stmt = pdo()->prepare($query);
            $result = $stmt->execute($parameters);
            exit(json_encode($result));
            break;

        case "update":
            if (!isset($_POST['id'])) {
                exit("id is not set");
            }
            $query = "UPDATE $table SET";
            $parameters = [];

            foreach ($updateNames as $name) {
                if (isset($_POST[$name])) {
                    $query .= " $name =:$name,";
                    $parameters[$name] = $_POST[$name];
                }
            }
            if ($table = "products" && isset($_FILES['files'])) {
                $query .= " images_number =:images_number,";
                $parameters['images_number'] = $productImagesNumber;
            }
            $query = preg_replace("/,$/", "", $query);
            $query .= " WHERE id = :id";
            $parameters['id'] = $_POST["id"];
            $stmt = pdo()->prepare($query);
            $result = $stmt->execute($parameters);
            exit(json_encode($result));
            break;

        case "delete":
            if (!isset($_POST['id'])) {
                exit("id is not set");
            }
            $query = "DELETE FROM $table WHERE id=:id";
            $parameters['id'] = $_POST["id"];
            $stmt = pdo()->prepare($query);
            $result = $stmt->execute($parameters);
            exit(json_encode($result));
            break;
    }
} catch (PDOException $e) {
    echo json_encode(false);
    // echo json_encode($query);
    // echo json_encode($parameters);
    // echo json_encode($e);
}

