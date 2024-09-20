<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
function database()
{
    $user_password = getenv("MYSQLPASSWORD");
    $user_name = getenv("MYSQLUSER");
    $databasename = getenv("MYSQLDB");
    $hostname = getenv("MYSQLSERVER");
    $database = new PDO("mysql:host=" . $hostname . ";dbname=" . $databasename, $user_name, $user_password);
    $database->query("set names utf8;");
    $database->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $database;
}

function add_cliente($identifier, $name_cliente, $email_cliente, $telefono, $direccion, $Altura, $ciudad, $observaciones, $status, $piso, $numero_de_piso)
{
    $bd = database();
    $sentence = $bd->prepare("INSERT INTO customers (tax_identifier, customer_name, email_customer, phone_customer, street, height, location, observaciones, id_status, floor, departament) VALUES (:identifier, :name_cliente, :email_cliente, :telefono, :direccion, :Altura, :ciudad, :observaciones, :status, :piso, :numero_de_piso)");

    $sentence->bindParam(':identifier', $identifier);
    $sentence->bindParam(':name_cliente', $name_cliente);
    $sentence->bindParam(':email_cliente', $email_cliente);
    $sentence->bindParam(':telefono', $telefono);
    $sentence->bindParam(':direccion', $direccion);
    $sentence->bindParam(':Altura', $Altura);
    $sentence->bindParam(':ciudad', $ciudad);
    $sentence->bindParam(':observaciones', $observaciones);
    $sentence->bindParam(':status', $status);
    $sentence->bindParam(':piso', $piso);
    $sentence->bindParam(':numero_de_piso', $numero_de_piso);

    return $sentence->execute();
}
function add_category($name_category, $status)
{
    $bd = database();
    $sentence = $bd->prepare("INSERT INTO categorys (detail, id_status) VALUES (:detail, :id_status)");

    $sentence->bindParam(':detail', $name_category);
    $sentence->bindParam(':id_status', $status);

    return $sentence->execute();
}
function getCustomer($id_customer)
{
    try {
        $bd = database();
        $query = "SELECT id_customer, tax_identifier, customer_name, email_customer, phone_customer, street, height, location, observaciones, id_status, floor, departament FROM customers WHERE id_customer = :id_customer";
        $statement = $bd->prepare($query);
        $statement->bindParam(':id_customer', $id_customer, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al obtener el cliente: " . $e->getMessage();
        return null;
    }
}

function obtenerclientes()
{
    $bd = database();
    $sentence = $bd->query("SELECT id_customer, tax_identifier, customer_name, email_customer, phone_customer, street, height, location, observaciones, id_status, floor, departament FROM customers  WHERE id_status=1");
    return $sentence->fetchAll(PDO::FETCH_ASSOC);
}
function obtenercategorys()
{
    $bd = database();
    $sentence = $bd->query("SELECT id_category, detail, id_status FROM categorys");
    return $sentence->fetchAll(PDO::FETCH_ASSOC);
}
function obtenerusuarios()
{
    $bd = database();
    $sentence = $bd->query("SELECT id_user,email_user,password,phone,date,id_status,id_rol FROM users");
    return $sentence->fetchAll(PDO::FETCH_ASSOC);
}
function obtenerroles()
{
    $bd = database();
    $sentence = $bd->query("SELECT id_rol,detail FROM roles");
    return $sentence->fetchAll(PDO::FETCH_ASSOC);
}
function Updatecliente($id, $name, $email, $cuil, $phone, $street, $height, $floor, $departament, $status, $location, $observaciones)
{
    try {
        $bd = database();

        // Obtener el email y el CUIL actuales del cliente
        $stmt = $bd->prepare("SELECT email_customer, tax_identifier FROM customers WHERE id_customer = ?");
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentEmail = $currentData['email_customer'];
        $currentCuil = $currentData['tax_identifier'];

        // Verificar si el email o el CUIL han cambiado antes de hacer la validación
        if ($email !== $currentEmail && emailExistsCliente($email, $id, $bd)) {
            return ['success' => false, 'message' => 'El email ya está en uso.'];
        }

        if ($cuil !== $currentCuil && cuilExistsCliente($cuil, $id, $bd)) {
            return ['success' => false, 'message' => 'El CUIL ya está en uso.'];
        }

        $query = $bd->prepare("UPDATE customers SET 
            tax_identifier = :tax_identifier, 
            customer_name = :customer_name, 
            email_customer = :email_customer, 
            phone_customer = :phone_customer, 
            street = :street, 
            height = :height, 
            location = :location, 
            observaciones = :observations, 
            floor = :floor, 
            departament = :departament,
            id_status = :id_status 
        WHERE id_customer = :id");

        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':tax_identifier', $cuil, PDO::PARAM_STR);
        $query->bindParam(':customer_name', $name, PDO::PARAM_STR);
        $query->bindParam(':email_customer', $email, PDO::PARAM_STR);
        $query->bindParam(':phone_customer', $phone, PDO::PARAM_STR);
        $query->bindParam(':street', $street, PDO::PARAM_STR);
        $query->bindParam(':height', $height, PDO::PARAM_INT);
        $query->bindParam(':floor', $floor, PDO::PARAM_STR);
        $query->bindParam(':departament', $departament, PDO::PARAM_STR);
        $query->bindParam(':location', $location, PDO::PARAM_STR);
        $query->bindParam(':observations', $observaciones, PDO::PARAM_STR);
        $query->bindParam(':id_status', $status, PDO::PARAM_INT);

        $result = $query->execute();

        if ($result) {
            return ['success' => true, 'message' => 'Cliente editado con éxito.'];
        } else {
            return ['success' => false, 'message' => 'Error al editar el cliente.'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al actualizar el cliente: ' . $e->getMessage()];
    }
}
// Verifica si el email ya está en uso por otro cliente
function emailExistsCliente($email, $id, $bd)
{
    $stmt = $bd->prepare("SELECT COUNT(*) FROM customers WHERE email_customer = ? AND id_customer != ?");
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->bindParam(2, $id, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

// Verifica si el CUIL ya está en uso por otro cliente
function cuilExistsCliente($cuil, $id, $bd)
{
    $stmt = $bd->prepare("SELECT COUNT(*) FROM customers WHERE tax_identifier = ? AND id_customer != ?");
    $stmt->bindParam(1, $cuil, PDO::PARAM_STR);
    $stmt->bindParam(2, $id, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}
function Updatecategory($id, $detail, $status)
{
    $bd = database();
    $query = $bd->prepare("UPDATE categorys SET 
           detail = :detail, 
           id_status = :id_status 
       WHERE id_category = :id");

    $query->bindParam(':id', $id);
    $query->bindParam(':detail', $detail);
    $query->bindParam(':id_status', $status);
    $query->execute();
}
function Updateusuario($id, $email, $phone, $status, $password, $id_rol)
{
    $bd = database();
    $query = $bd->prepare("UPDATE users 
                           SET email_user = :email, 
                               phone = :phone, 
                               id_status = :status,
                               password = :password,
                               id_rol = :id_rol
                           WHERE id_user = :id");

    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':phone', $phone, PDO::PARAM_STR);
    $query->bindParam(':status', $status, PDO::PARAM_INT);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);

    return $query->execute();
}

function deletecliente($id)
{
    $bd = database();
    $query = $bd->prepare("UPDATE customers SET id_status = 2 WHERE id_customer = :id");
    $query->bindParam(':id', $id);
    $query->execute();
}
function deletecategory($id)
{
    $bd = database();
    $query = $bd->prepare("UPDATE categorys SET id_status = 2 WHERE id_category = :id");
    $query->bindParam(':id', $id);
    $query->execute();
}
function deleteusuarios($id, $id_rol)
{
    if ($id_rol != 1) {
        $bd = database();
        $query = $bd->prepare("UPDATE users SET id_status = 2 WHERE id_user = :id");
        $query->bindParam(':id', $id);
        $query->execute();
    } else {
        echo "no";
    }
}

function addUsuario($email_user, $phone, $password, $id_status, $id_rol)
{
    $bd = database();
    $sql = "INSERT INTO users (email_user, phone, password,id_status,id_rol) VALUES (:email_user, :phone, :password, :id_status, :id_rol)";
    $stmt = $bd->prepare($sql);
    $stmt->bindParam(':email_user', $email_user);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':id_status', $id_status);
    $stmt->bindParam(':id_rol', $id_rol);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function login($email, $password)
{
    $bd = database();
    $sentence = $bd->prepare("SELECT email_user, password, id_rol, id_status FROM users WHERE email_user = :email");
    $sentence->execute([$email]);
    // Obtiene la fila asociada al correo electrónico proporcionado
    $row = $sentence->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if ($password == $row['password']) {
            return $row;
        }
    }
    return false;
}
function check_existing_supplier($cuil, $email_Proveedor)
{
    $bd = database();
    $sentence = $bd->prepare("SELECT COUNT(*) AS count FROM suppliers WHERE (tax_identifier = ? OR email_supplier = ?) AND id_status != 0");
    $sentence->execute([$cuil, $email_Proveedor]);
    $row = $sentence->fetch(PDO::FETCH_ASSOC);
    return $row['count'] > 0;
}
function insert_suppliers($name_Proveedor, $telefono, $email_Proveedor, $direccion, $altura, $piso, $numero_de_piso, $ciudad, $observaciones, $cuil)
{
    try {
        $bd = database();
        $sentence = $bd->prepare("INSERT INTO suppliers (name_supplier, phone_supplier, email_supplier, street, height, floor, departament, location, id_status, observations, tax_identifier) VALUES (:name_Proveedor, :telefono, :email_Proveedor, :direccion, :altura, :piso, :numero_de_piso, :ciudad, 1, :observaciones, :cuil)");

        $sentence->bindParam(':name_Proveedor', $name_Proveedor, PDO::PARAM_STR);
        $sentence->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $sentence->bindParam(':email_Proveedor', $email_Proveedor, PDO::PARAM_STR);
        $sentence->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        $sentence->bindParam(':altura', $altura, PDO::PARAM_STR);
        $sentence->bindParam(':piso', $piso, PDO::PARAM_STR);
        $sentence->bindParam(':numero_de_piso', $numero_de_piso, PDO::PARAM_STR);
        $sentence->bindParam(':ciudad', $ciudad, PDO::PARAM_STR);
        $sentence->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
        $sentence->bindParam(':cuil', $cuil, PDO::PARAM_STR);

        return $sentence->execute();
    } catch (PDOException $e) {
        echo "Error al insertar proveedor: " . $e->getMessage();
        return false;
    }
}

function show_state($table)
{
    $bd = database();
    $query = $bd->prepare("SELECT * FROM $table WHERE id_status = 1");
    $query->execute();
    $list_data = $query->fetchAll();

    return $list_data;
}

function getSupplier($id_supplier)
{
    try {
        $bd = database();
        $query = "SELECT * FROM suppliers WHERE id_supplier = :id_supplier and id_status=1";
        $statement = $bd->prepare($query);
        $statement->bindParam(':id_supplier', $id_supplier, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al obtener el proveedor: " . $e->getMessage();
        return null;
    }
}

// Verifica si el email ya está en uso por otro proveedor
function emailExists($email, $id_supplier, $bd)
{
    $stmt = $bd->prepare("SELECT COUNT(*) FROM suppliers WHERE email_supplier = ? AND id_supplier != ?");
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->bindParam(2, $id_supplier, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

// Verifica si el CUIL ya está en uso por otro proveedor
function cuilExists($cuil, $id_supplier, $bd)
{
    $stmt = $bd->prepare("SELECT COUNT(*) FROM suppliers WHERE tax_identifier = ? AND id_supplier != ?");
    $stmt->bindParam(1, $cuil, PDO::PARAM_STR);
    $stmt->bindParam(2, $id_supplier, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

// Función para actualizar los datos de un proveedor en la base de datos
function updateSupplier($id_supplier, $name, $phone, $email, $observation, $tax, $street, $height, $floor, $departament, $location)
{
    try {
        $bd = database();

        // Verificar si el email o el CUIL están en uso por otro proveedor
        if (emailExists($email, $id_supplier, $bd)) {
            return ['success' => false, 'message' => 'El email ya está en uso.'];
        }

        if (cuilExists($tax, $id_supplier, $bd)) {
            return ['success' => false, 'message' => 'El CUIL ya está en uso.'];
        }

        $query = "UPDATE suppliers SET
        name_supplier = :name_supplier, 
        phone_supplier = :phone_supplier, 
        email_supplier = :email_supplier,
        observations = :observations,
        tax_identifier = :tax_identifier,
        street = :street,
        height = :height,
        floor = :floor,
        departament = :departament,
        location = :location
        WHERE id_supplier = :id_supplier";

        $statement = $bd->prepare($query);
        $statement->bindParam(':id_supplier', $id_supplier, PDO::PARAM_INT);
        $statement->bindParam(':name_supplier', $name, PDO::PARAM_STR);
        $statement->bindParam(':phone_supplier', $phone, PDO::PARAM_STR); // Cambiado a STR
        $statement->bindParam(':email_supplier', $email, PDO::PARAM_STR);
        $statement->bindParam(':observations', $observation, PDO::PARAM_STR);
        $statement->bindParam(':tax_identifier', $tax, PDO::PARAM_STR);
        $statement->bindParam(':street', $street, PDO::PARAM_STR);
        $statement->bindParam(':height', $height, PDO::PARAM_INT);
        $statement->bindParam(':floor', $floor, PDO::PARAM_STR);
        $statement->bindParam(':departament', $departament, PDO::PARAM_STR);
        $statement->bindParam(':location', $location, PDO::PARAM_STR);

        $result = $statement->execute();

        if ($result) {
            return ['success' => true, 'message' => 'Proveedor editado con éxito.'];
        } else {
            return ['success' => false, 'message' => 'Error al editar al proveedor.'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al actualizar el proveedor: ' . $e->getMessage()];
    }
}



function eliminated_Suppliers($table, $id_user)
{
    try {

        $bd = database();

        $query = "UPDATE $table SET id_status = 0 WHERE id_supplier = :id_supplier";
        $updateStatement = $bd->prepare($query);
        $updateStatement->bindParam(':id_supplier', $id_user, PDO::PARAM_INT);
        $updateStatement->execute();
        $rowCount = $updateStatement->rowCount();

        return ($rowCount > 0);
    } catch (PDOException $e) {
        // Manejar errores de base de datos
        echo "Error al actualizar: " . $e->getMessage();
        return false;
    }
}

function getSuppliers($id_supplier)
{
    $bd = database();
    $query = "SELECT * FROM suppliers WHERE id_supplier = :id_supplier and id_status=1";
    $statement = $bd->prepare($query);
    $statement->bindParam(':id_supplier', $id_supplier, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetch(PDO::FETCH_ASSOC);
}

function insert_products($number_product, $name_product, $description, $id_brand, $id_category)
{
    $bd = database();
    $query = "INSERT INTO products (number_product,name_product, description, id_status, id_brand ,id_category) VALUES (:number_product,:name_product, :description, 1, :id_brand, :id_category)";

    $consulta = $bd->prepare($query);

    $consulta->bindParam(':number_product', $number_product, PDO::PARAM_STR);
    $consulta->bindParam(':name_product', $name_product, PDO::PARAM_STR);
    $consulta->bindParam(':description', $description, PDO::PARAM_STR);
    $consulta->bindParam(':id_brand', $id_brand, PDO::PARAM_INT);
    $consulta->bindParam(':id_category', $id_category, PDO::PARAM_INT);

    try {
        if ($consulta->execute()) {
            return true; // Devuelve verdadero si la inserción fue exitosa
        }
    } catch (PDOException $e) {
        echo "Error en la inserción: " . $e->getMessage();
        return false;
    }
}
function insert_sender($id_supplier, $number_remito, $date_remito, $number_invoice, $id_product, $quantity, $line_number = null)
{
    $bd = database();

    $query = "INSERT INTO purchases (id_supplier, remito_number, remito_date, invoice_number, id_product, qty, line_number) VALUES (:id_supplier, :remito_number, :remito_date, :invoice_number, :id_product, :qty, :line_number)";

    $consulta = $bd->prepare($query);
    $consulta->bindParam(':id_supplier', $id_supplier, PDO::PARAM_INT);
    $consulta->bindParam(':remito_number', $number_remito, PDO::PARAM_STR);
    $consulta->bindParam(':remito_date', $date_remito, PDO::PARAM_STR);
    $consulta->bindParam(':invoice_number', $number_invoice, PDO::PARAM_STR);
    $consulta->bindParam(':id_product', $id_product, PDO::PARAM_INT);
    $consulta->bindParam(':qty', $quantity, PDO::PARAM_INT);
    $consulta->bindParam(':line_number', $line_number, PDO::PARAM_INT);


    try {
        // Insertar en la tabla purchases
        $query = "INSERT INTO purchases (id_supplier, remito_number, remito_date, invoice_number, invoice_date, id_product, qty, line_number) 
                  VALUES (:id_supplier, :remito_number, :remito_date, :invoice_number, :invoice_date, :id_product, :qty, :line_number)";
        $consulta = $bd->prepare($query);
        $consulta->bindParam(':id_supplier', $id_supplier, PDO::PARAM_INT);
        $consulta->bindParam(':remito_number', $number_remito, PDO::PARAM_STR);
        $consulta->bindParam(':remito_date', $date_remito, PDO::PARAM_STR);
        $consulta->bindParam(':invoice_number', $number_invoice, PDO::PARAM_STR);
        $consulta->bindParam(':invoice_date', $date_invoice, PDO::PARAM_STR);
        $consulta->bindParam(':id_product', $id_product, PDO::PARAM_INT);
        $consulta->bindParam(':qty', $quantity, PDO::PARAM_INT);
        $consulta->bindParam(':line_number', $line_number, PDO::PARAM_INT);
        
        if (!$consulta->execute()) {
            throw new Exception("Error al insertar en purchases.");
        }

        $query_update = "UPDATE products SET stock = stock + :qty WHERE id_product = :id_product";
        $consulta_update = $bd->prepare($query_update);
        $consulta_update->bindParam(':qty', $quantity, PDO::PARAM_INT);
        $consulta_update->bindParam(':id_product', $id_product, PDO::PARAM_INT);
        
        if (!$consulta_update->execute()) {
            throw new Exception("Error al actualizar el stock en products.");
        }

        
        $bd->commit();
        return true; 

    } catch (Exception $e) {
      
        $bd->rollBack();
        echo "Error en la inserción/actualización: " . $e->getMessage();
        return false;
    }
}
function insert_date_sender($date_invoice)
{
    $bd = database();

    $query_sales = "SELECT id_purchase FROM purchases ORDER BY id_purchase DESC LIMIT 1"; 
    $consulta_sales = $bd->prepare($query_sales);
    $consulta_sales->execute();
    $id_purchase = $consulta_sales->fetchColumn();

    $query_type = "SELECT id_type FROM motions_type WHERE motion_type = 'Compra'";
    $consulta_type = $bd->prepare($query_type);
    $consulta_type->execute();
    $id_type = $consulta_type->fetchColumn();

    $query = "INSERT INTO motions (date_sales, id_type, id_purchase) 
              VALUES (:date_sales, :id_type, :id_purchase)";
    $consulta = $bd->prepare($query);
    $consulta->bindParam(':date_sales', $date_invoice, PDO::PARAM_STR);
    $consulta->bindParam(':id_type', $id_type, PDO::PARAM_INT);
    $consulta->bindParam(':id_purchase', $id_purchase, PDO::PARAM_INT);

    try {
        if ($consulta->execute()) {
            return true;
        }
    } catch (PDOException $e) {
        echo "Error en la inserción: " . $e->getMessage();
        return false;
    }
}
function getproducts($id_product)
{
    try {
        $bd = database();
        $query = "SELECT * FROM products WHERE id_product = :id_product and id_status=1";
        $statement = $bd->prepare($query);
        $statement->bindParam(':id_product', $id_product, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al obtener el proveedor: " . $e->getMessage();
        return null;
    }
}
function update_products($number_product, $id_product, $name_product, $description)
{
    try {
        $bd = database();
        $query = "UPDATE products SET
        
        number_product = :number_product,
        name_product = :name_product, 
        description = :description
        WHERE id_product = :id_product";

        $consulta = $bd->prepare($query);
        $consulta->bindParam(':id_product', $id_product, PDO::PARAM_INT);

        $consulta->bindParam(':number_product', $number_product, PDO::PARAM_STR);
        $consulta->bindParam(':name_product', $name_product, PDO::PARAM_STR);
        $consulta->bindParam(':description', $description, PDO::PARAM_STR);

        $result = $consulta->execute();

        return $result;
    } catch (PDOException $e) {
        echo "Error al actualizar el proveedor: " . $e->getMessage();
        return false;
    }
}
function eliminated_product($table, $id_user)
{
    try {


        $bd = database();
        $query = "UPDATE $table SET id_status = 0 WHERE id_product = :id_product";
        $updateStatement = $bd->prepare($query);
        $updateStatement->bindParam(':id_product', $id_user, PDO::PARAM_INT);
        $updateStatement->execute();
        $rowCount = $updateStatement->rowCount();

        return ($rowCount > 0);
    } catch (PDOException $e) {
        // Manejar errores de base de datos
        echo "Error al actualizar: " . $e->getMessage();
        return false;
    }
}
function insert_brand($detail)
{
    $bd = database();
    $query = "INSERT INTO brands (detail,id_status) VALUES (:detail, 1)";
    $consulta = $bd->prepare($query);
    $consulta->bindParam(':detail', $detail, PDO::PARAM_STR);

    try {
        if ($consulta->execute()) {
            return true; // Devuelve verdadero si la inserción fue exitosa
        }
    } catch (PDOException $e) {
        echo "Error en la inserción: " . $e->getMessage();
        return false;
    }
}
function getbrands($id_brand)
{
    try {
        $bd = database();
        $query = "SELECT * FROM brands WHERE id_brand = :id_brand and id_status=1";
        $statement = $bd->prepare($query);
        $statement->bindParam(':id_brand', $id_brand, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al obtener el proveedor: " . $e->getMessage();
        return null;
    }
}
function update_brands($id_brand, $detail)
{
    try {
        $bd = database();
        $query = "UPDATE brands SET
        detail = :detail
        WHERE id_brand = :id_brand";

        $consulta = $bd->prepare($query);
        $consulta->bindParam(':id_brand', $id_brand, PDO::PARAM_INT);
        $consulta->bindParam(':detail', $detail, PDO::PARAM_STR);


        $result = $consulta->execute();

        return $result;
    } catch (PDOException $e) {
        echo "Error al actualizar el proveedor: " . $e->getMessage();
        return false;
    }
}
function eliminated_brand($table, $id_brand)
{
    try {

        $bd = database();
        $query = "DELETE FROM $table WHERE id_brand = :id_brand";
        $deleteStatement = $bd->prepare($query);
        $deleteStatement->bindParam(':id_brand', $id_brand, PDO::PARAM_INT);
        $deleteStatement->execute();
        $rowCount = $deleteStatement->rowCount();

        return ($rowCount > 0);
    } catch (PDOException $e) {
        // Manejar errores de base de datos
        return false;
    }
}


function deletecategorys($table, $id_brands)
{
    try {

        $bd = database();

        $query = "DELETE FROM $table WHERE id_category = :id_category";
        $deleteStatement = $bd->prepare($query);
        $deleteStatement->bindParam(':id_category', $id_brands, PDO::PARAM_INT);

        $deleteStatement->execute();
        $rowCount = $deleteStatement->rowCount();

        return ($rowCount > 0);
    } catch (PDOException $e) {
        // Manejar errores de base de datos
        return false;
    }
}

function brand_exists($detail)
{
    try {
        $bd = database();
        $query = "SELECT COUNT(*) FROM brands WHERE detail = :detail";
        $statement = $bd->prepare($query);
        $statement->bindParam(':detail', $detail, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        echo "Error al verificar la marca: " . $e->getMessage();
        return false;
    }
}
function category_exists($name_category)
{
    try {
        $bd = database();
        $query = "SELECT COUNT(*) FROM categorys WHERE detail = :detail";
        $statement = $bd->prepare($query);
        $statement->bindParam(':detail', $name_category, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->fetchColumn();
        return $count > 0;
    } catch (PDOException $e) {
        echo "Error al verificar la categoría: " . $e->getMessage();
        return false;
    }
}



//Email-Config

function getConnection()
{
    $user_password = getenv("MYSQLPASSWORD");
    $user_name = getenv("MYSQLUSER");
    $databasename = getenv("MYSQLDB");
    $hostname = getenv("MYSQLSERVER");

    try {
        $database = new PDO("mysql:host=" . $hostname . ";dbname=" . $databasename, $user_name, $user_password);
        $database->query("set names utf8;");
        $database->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        return $database;
    } catch (PDOException $e) {
        echo "Error en la conexión: " . $e->getMessage();
        die();
    }
}

function saveConfig($email, $email_password, $email_receive, $smtp_address, $smtp_port)
{
    $db = getConnection();
    $config = getConfig();

    if ($config) {
        // Actualizar configuración existente
        $query = "UPDATE email_config SET email = :email, email_password = :email_password, email_receive= :email_receive, smtp_address = :smtp_address, smtp_port = :smtp_port WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $config->id);
    } else {
        // Insertar nueva configuración
        $query = "INSERT INTO email_config (email, email_password, email_receive, smtp_address, smtp_port) VALUES (:email, :email_password,:email_receive, :smtp_address, :smtp_port)";
        $stmt = $db->prepare($query);
    }

    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':email_password', $email_password);
    $stmt->bindParam(':email_receive', $email_receive);
    $stmt->bindParam(':smtp_address', $smtp_address);
    $stmt->bindParam(':smtp_port', $smtp_port);

    return $stmt->execute();
}

function getConfig()
{
    $db = getConnection();
    $query = "SELECT * FROM email_config ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch();
}


function clients_exists($email_cliente)
{
    try {
        $bd = database();
        $query = "SELECT COUNT(*) FROM customers WHERE email_customer = :detail";
        $statement = $bd->prepare($query);
        $statement->bindParam(':detail', $email_cliente, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->fetchColumn();
        return $count > 0;
    } catch (PDOException $e) {
        echo "Error al verificar al cliente: " . $e->getMessage();
        return false;
    }
}
function user_exists($email_user)
{
    try {
        $bd = database();
        $query = "SELECT COUNT(*) FROM users WHERE email_user = :detail";
        $statement = $bd->prepare($query);
        $statement->bindParam(':detail', $email_user, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->fetchColumn();
        return $count > 0;
    } catch (PDOException $e) {
        echo "Error al verificar el usuario: " . $e->getMessage();
        return false;
    }
}

function check_existing_cliente($cuil, $email_Proveedor)
{
    $bd = database();
    $sentence = $bd->prepare("SELECT COUNT(*) AS count FROM customers WHERE (tax_identifier = ? OR email_customer = ?) AND id_status != 0");
    $sentence->execute([$cuil, $email_Proveedor]);
    $row = $sentence->fetch(PDO::FETCH_ASSOC);
    return $row['count'] > 0;
}
function obtenerUsuarioPorEmail($email)
{
    $bd = database();
    $sentence = $bd->prepare("SELECT u.id_user, u.email_user, u.id_rol, r.detail as rol
                              FROM users u
                              JOIN roles r ON u.id_rol = r.id_rol
                              WHERE u.email_user = :email");
    $sentence->bindParam(':email', $email, PDO::PARAM_STR);
    $sentence->execute();
    return $sentence->fetch(PDO::FETCH_ASSOC);
}

function add_serial_number($id_product, $serial_number, $remito_number, $line_number, $id_supplier)
{
    $bd = database();
    $sentence = $bd->prepare("INSERT INTO serial_numbers (id_product, serial_number, remito_number, line_number, id_supplier)
        VALUES (:id_product, :serial_number, :remito_number, :line_number, :id_supplier)
    ");

    $sentence->bindParam(':id_product', $id_product);
    $sentence->bindParam(':serial_number', $serial_number);
    $sentence->bindParam(':remito_number', $remito_number);
    $sentence->bindParam(':line_number', $line_number);
    $sentence->bindParam(':id_supplier', $id_supplier);  // Agregar esta línea

    return $sentence->execute();
}

function get_serial_numbers($id_product, $remito_number, $id_supplier)
{
    $bd = database();
    $sentence = $bd->prepare("SELECT id_product, serial_number, remito_number, id_supplier, line_number
                              FROM serial_numbers
                              WHERE id_product = :id_product 
                              AND remito_number = :remito_number 
                              AND id_supplier = :id_supplier");

    $sentence->bindParam(':id_product', $id_product);
    $sentence->bindParam(':remito_number', $remito_number);
    $sentence->bindParam(':id_supplier', $id_supplier);

    $sentence->execute();

    // Verificar si se obtuvieron resultados
    $results = $sentence->fetchAll(PDO::FETCH_ASSOC);
    error_log(print_r($results, true));  // Imprimir en el log para verificar

    return $results;
}
function update_serial_number($id_product, $serial_number, $remito_number, $id_supplier, $line_number)
{
    $bd = database();
    $sentence = $bd->prepare("
        UPDATE serial_numbers 
        SET serial_number = :serial_number
        WHERE id_product = :id_product 
          AND remito_number = :remito_number 
          AND id_supplier = :id_supplier
          AND line_number = :line_number
    ");

    // Enlazar parámetros
    $sentence->bindParam(':id_product', $id_product);
    $sentence->bindParam(':serial_number', $serial_number);
    $sentence->bindParam(':remito_number', $remito_number);
    $sentence->bindParam(':id_supplier', $id_supplier);
    $sentence->bindParam(':line_number', $line_number);

    return $sentence->execute();
}
function obtenerFechasLimite()
{
    $today = date('Y-m-d');
    $minDate = date('Y-m-d', strtotime('-7 days'));
    $maxDate = date('Y-m-d', strtotime('+7 days'));

    return [
        'today' => $today,
        'maxDate' => $maxDate,
        'minDate' => $minDate
    ];
}
function insert_sales($id_customer, $sales_number, $id_product, $quantity)
{
    $bd = database();

    // Seleccionamos el id_status correspondiente al estado 'Despacho'
    $query_status = "SELECT id_status FROM status WHERE detail = 'Despacho'";
    $consulta_status = $bd->prepare($query_status);
    $consulta_status->execute();
    $id_status = $consulta_status->fetchColumn();

    if (!$id_status) {
        echo "Error: No se encontró el estado 'Despacho'";
        return false;
    }

    // Inserción en la tabla de ventas
    $query = "INSERT INTO sales (id_customer, sales_number, id_product, quantity, id_status) 
              VALUES (:id_customer, :sales_number, :id_product, :quantity, :id_status)";

    $consulta = $bd->prepare($query);
    $consulta->bindParam(':id_customer', $id_customer, PDO::PARAM_INT);
    $consulta->bindParam(':sales_number', $sales_number, PDO::PARAM_STR);
    $consulta->bindParam(':id_product', $id_product, PDO::PARAM_INT);
    $consulta->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $consulta->bindParam(':id_status', $id_status, PDO::PARAM_INT); // Se agrega el id_status

    try {
        if ($consulta->execute()) {
            return true; // Devuelve verdadero si la inserción fue exitosa
        }
    } catch (PDOException $e) {
        echo "Error en la inserción: " . $e->getMessage();
        return false;
    }
}

function check_remito_exists($number_remito) {

    $bd = database();
    $query = $bd->prepare("SELECT COUNT(*) FROM purchases WHERE remito_number = :remito_number");
    $query->bindParam(':remito_number', $number_remito);
    $query->execute();

    return $query->fetchColumn() > 0; 
}
function insert_date_sales($date_sales)
{
    $bd = database();
    $query_sales = "SELECT id_sales FROM sales ORDER BY id_sales DESC LIMIT 1"; 
    $consulta_sales = $bd->prepare($query_sales);
    $consulta_sales->execute();
    $id_sales = $consulta_sales->fetchColumn();

    $query_type = "SELECT id_type FROM motions_type WHERE motion_type = 'Venta'";
    $consulta_type = $bd->prepare($query_type);
    $consulta_type->execute();
    $id_type = $consulta_type->fetchColumn();

    $query = "INSERT INTO motions (date_sales, id_type, id_sales) 
              VALUES (:date_sales, :id_type, :id_sales)";
    $consulta = $bd->prepare($query);
    $consulta->bindParam(':date_sales', $date_sales, PDO::PARAM_STR);
    $consulta->bindParam(':id_type', $id_type, PDO::PARAM_INT);

    $consulta->bindParam(':id_sales', $id_sales, PDO::PARAM_INT);

    // Ejecutar la inserción
    try {
        if ($consulta->execute()) {
            return true;
        }
    } catch (PDOException $e) {
        echo "Error en la inserción: " . $e->getMessage();
        return false;
    }
}

function obtener_number_sales()
{
    $bd = database();

    $sentence = $bd->query("SELECT sales_number FROM sales ORDER BY sales_number DESC LIMIT 1");
    $result = $sentence->fetch(PDO::FETCH_ASSOC);

    if ($result && isset($result['sales_number'])) {
        return $result['sales_number'] + 1;
    } else {
        return 1; 
    }

}
function update_product_stock($id_product, $quantity_sold) {
    try {
        $bd = database();
        // convierto quantity_sold a entero por que en la bd esta en int 
        $quantity_sold = (int)$quantity_sold;
        $id_product = (int)$id_product;
        if (empty($id_product) || $quantity_sold <= 0) {
            throw new Exception("El ID del producto o la cantidad son inválidos.");
        }

        $query = "UPDATE products SET stock = stock - :quantity_sold WHERE id_product = :id_product AND stock >= :min_stock";
        $stmt = $bd->prepare($query);
        $stmt->bindValue(':quantity_sold', $quantity_sold, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $quantity_sold, PDO::PARAM_INT);
        $stmt->bindValue(':id_product', $id_product, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new PDOException("Error al ejecutar la consulta de actualización de stock: " . $errorInfo[2]);
        }
        return true;
    } catch (Exception $e) {
        echo "Error en la actualización del stock: " . $e->getMessage();
        return false;
    }
}
function add_custommer_sale($identifier, $name_cliente, $email_cliente, $telefono = null, $direccion = null, $altura = null, $ciudad = null, $piso = null, $observaciones = null, $status, $departamento = null) {
    $bd = database();

    $sentence = $bd->prepare("INSERT INTO customers (tax_identifier, customer_name, email_customer, phone_customer, street, height, location, floor, observaciones, id_status, departament) 
        VALUES (:identifier, :name_cliente, :email_cliente, :telefono, :direccion, :altura, :ciudad, :piso, :observaciones, :status, :departamento)");

    $sentence->bindParam(':identifier', $identifier);
    $sentence->bindParam(':name_cliente', $name_cliente);
    $sentence->bindParam(':email_cliente', $email_cliente);
    $sentence->bindValue(':telefono', !empty($telefono) ? $telefono : null, PDO::PARAM_STR);
    $sentence->bindValue(':direccion', !empty($direccion) ? $direccion : null, PDO::PARAM_STR);
    $sentence->bindValue(':altura', !empty($altura) ? $altura : null, PDO::PARAM_STR);
    $sentence->bindValue(':ciudad', !empty($ciudad) ? $ciudad : null, PDO::PARAM_STR);
    $sentence->bindValue(':piso', !empty($piso) ? $piso : null, PDO::PARAM_STR);
    $sentence->bindValue(':observaciones', !empty($observaciones) ? $observaciones : null, PDO::PARAM_STR);
    $sentence->bindValue(':status', !empty($status) ? (int)$status : 1, PDO::PARAM_INT);
    $sentence->bindValue(':departamento', !empty($departamento) ? $departamento : null, PDO::PARAM_STR);

    return $sentence->execute();
}
