<?php 

function isValidInput($input){
    return preg_match('/^[\p{L}0-9 .,–\-]+$/u', $input);
}


function isValidNumberWithDotInput($input) {
    return preg_match('/^[0-9.]+$/', $input);
}

function insert_product(object $pdo, string $product_name, string $sku, string $price, string $featured_image){
    $data = [
        'product_name' => $product_name, 
        'sku' => $sku, 
        'price' => $price, 
        'featured_image' => $featured_image, 

        ];
        
        $query = "INSERT INTO products (product_name, sku, price,featured_image, date) VALUES (:product_name, :sku, :price,:featured_image, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":product_name", $product_name);
        $stmt->bindParam(":sku", $sku);
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":featured_image", $featured_image);
        $stmt->execute($data);
        return $pdo->lastInsertId();
}

function update_product(object $pdo, int $product_id, string $product_name, string $sku, string $price, string $featured_image){
    $data = [
        'product_id' => $product_id,
        'product_name' => $product_name, 
        'sku' => $sku, 
        'price' => $price, 
        'featured_image' => $featured_image, 
    ];
    
    // Update query
    $query = "UPDATE products 
              SET product_name = :product_name, 
                  sku = :sku, 
                  price = :price, 
                  featured_image = :featured_image 
              WHERE id = :product_id";
    
    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":product_name", $product_name);
    $stmt->bindParam(":sku", $sku);
    $stmt->bindParam(":price", $price);
    $stmt->bindParam(":featured_image", $featured_image);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->execute($data);

    return $stmt->rowCount(); // Returns the number of rows affected (should be 1 if updated successfully)
}

function select_featuredimage($pdo, $product_id){
    $query = "SELECT featured_image FROM products WHERE id = :product_id;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single row instead of all
    return $result['featured_image']; // Return the 'featured_image' column as a string
}






function add_product_property(PDO $pdo, int $product_id, int $property_id) {
    $query = "INSERT INTO product_property (product_id, property_id) VALUES (:product_id, :property_id);";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":product_id", $product_id);
    $stmt->bindParam(":property_id", $property_id);
    $stmt->execute();
}

function insert_property(object $pdo, string $type_, string $name_) {
    try {
        $data = [
            'type_' => $type_,
            'name_' => $name_
        ];

        $query = "INSERT INTO property (type_, name_) VALUES (:type_, :name_)";
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute($data)) {
            return $pdo->lastInsertId(); 
        } else {
            return false; 
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false; 
    }
}

function select_property($pdo, $type_){
    $query = "SELECT id,name_ FROM property WHERE type_ = :type_;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":type_", $type_);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function check_duplicate(object $pdo, string $type_, string $name_) {
    try {
        $query = "SELECT COUNT(*) FROM property WHERE type_ = :type_ AND name_ = :name_";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['type_' => $type_, 'name_' => $name_]);
        
        return $stmt->fetchColumn() > 0; 
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}


function getRecordCount($pdo, $searchTermLike, $category = null, $tag = null, $date_from = null, 
                        $date_to = null, $price_from = null, $price_to = null) {
    $query = "SELECT COUNT(DISTINCT products.id) FROM products";
    $conditions = ["product_name LIKE :search_term"];
    $params = [':search_term' => $searchTermLike];
    
    if ($category) {
        $query .= " JOIN product_property pp1 ON products.id = pp1.product_id AND pp1.property_id = :category";
        $params[':category'] = $category;
    }
    if ($tag) {
        $query .= " JOIN product_property pp2 ON products.id = pp2.product_id AND pp2.property_id = :tag";
        $params[':tag'] = $tag;
    }

    if ($date_from && $date_to) {
        $conditions[] = "date BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $date_from;
        $params[':date_to'] = $date_to;
    }

    if ($price_from && $price_to) {
        $conditions[] = "price BETWEEN :price_from AND :price_to";
        $params[':price_from'] = $price_from;
        $params[':price_to'] = $price_to;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchColumn();
}

function select_all_products(object $pdo)  {
    $query = "SELECT * FROM products";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdo=null;
    $stmt=null;
    return $results;
}



?>