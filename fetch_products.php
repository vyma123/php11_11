<?php
require_once 'includes/db.inc.php';

$per_page_record = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $per_page_record;

$query = "SELECT * FROM products LIMIT :start_from, :per_page";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
$stmt->bindParam(':per_page', $per_page_record, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = '';
if (count($results) > 0) {
    foreach ($results as $row) {
        $output .= "<tr>";
        $output .= "<td>" . htmlspecialchars($row['date']) . "</td>";
        $output .= "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        $output .= "<td>" . htmlspecialchars($row['sku']) . "</td>";
        $output .= "<td>" . htmlspecialchars($row['price']) . "</td>";
        $output .= "<td><img height='30' src='./uploads/" . htmlspecialchars($row['featured_image']) . "'></td>";
        
        $output .= "<td>";
        $output .= "<button class='edit_button' value='" . $row['id'] . "'><i class='edit icon'></i></button>";
        $output .= "<a class='delete_button' href=''><i class='trash icon'></i></a>";
        $output .= "</td>";
        $output .= "</tr>";
    }
} else {
    $output .= "<tr><td colspan='9' style='text-align: center;'>No products found</td></tr>";
}

$query = "SELECT COUNT(*) FROM products";
$count_stmt = $pdo->prepare($query);
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page_record);

$pagination = '';
if ($page > 1) {
    $pagination .= "<a  class='item' data-page='".($page - 1)."'>Prev</a>";
} else {
    $pagination .= "<a class='item disabled'>Prev</a>";
}

for ($i = 1; $i <= $total_pages; $i++) {
    $active_class = ($i == $page) ? 'active' : '';
    $pagination .= "<a  class='item $active_class' data-page='$i'>$i</a>";
}

if ($page < $total_pages) {
    $pagination .= "<a  class='item' data-page='".($page + 1)."'>Next</a>";
} else {
    $pagination .= "<a class='item disabled'>Next</a>";
}

echo json_encode([
    'products' => $output,
    'pagination' => $pagination
]);
?>
