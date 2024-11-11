<?php
require_once 'includes/db.inc.php';

// Lấy các tham số từ GET, nếu không có thì gán giá trị mặc định
$searchTerm = isset($_GET['search']) ? test_input($_GET['search']) : '';
$sort_by = $_GET['sort_by'] ?? 'id';
$order = $_GET['order'] ?? 'ASC';
$category = $_GET['category'] ?? 0;
$tag = $_GET['tag'] ?? 0;
$gallery = $_GET['gallery'] ?? '';  
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;
$price_from = $_GET['price_from'] ?? null;
$price_to = $_GET['price_to'] ?? null;

// Tham số phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;  // Trang hiện tại
$limit = 5;  // Số lượng bản ghi mỗi trang
$offset = ($page - 1) * $limit;  // Vị trí bắt đầu

// Bắt đầu xây dựng truy vấn SQL
$query = "
SELECT products.*, 
    GROUP_CONCAT(DISTINCT p_tags.name_ SEPARATOR ', ') AS tags, 
    GROUP_CONCAT(DISTINCT p_categories.name_ SEPARATOR ', ') AS categories,
    GROUP_CONCAT(DISTINCT g_images.name_ SEPARATOR ', ') AS gallery_images
FROM products
LEFT JOIN product_property pp_tags ON products.id = pp_tags.product_id
LEFT JOIN property p_tags ON pp_tags.property_id = p_tags.id AND p_tags.type_ = 'tag'
LEFT JOIN product_property pp_categories ON products.id = pp_categories.product_id
LEFT JOIN property p_categories ON pp_categories.property_id = p_categories.id AND p_categories.type_ = 'category'
LEFT JOIN product_property pp_gallery ON products.id = pp_gallery.product_id
LEFT JOIN property g_images ON pp_gallery.property_id = g_images.id AND g_images.type_ = 'gallery'
WHERE products.product_name LIKE :search_term
";

// Thêm các điều kiện nếu có
if ($category != 0) {
    $query .= " AND pp_categories.property_id = :category_id";
}

if ($tag != 0) {
    $query .= " AND pp_tags.property_id = :tag_id";
}

if (!empty($gallery)) {
    $query .= " AND g_images.name_ LIKE :gallery"; 
}

if (!empty($date_from)) {
    $query .= " AND products.date >= :date_from"; 
}

if (!empty($date_to)) {
    $query .= " AND products.date <= :date_to"; 
}

if (!empty($price_from)) {
    $query .= " AND products.price >= :price_from"; 
}

if (!empty($price_to)) {
    $query .= " AND products.price <= :price_to";
}

// Sắp xếp kết quả
$query .= " GROUP BY products.id ORDER BY $sort_by $order";

// Thêm LIMIT và OFFSET cho phân trang
$query .= " LIMIT :limit OFFSET :offset";

// Chuẩn bị và thực thi truy vấn
$stmt = $pdo->prepare($query);

$searchTermLike = "%$searchTerm%";
$stmt->bindParam(':search_term', $searchTermLike, PDO::PARAM_STR);

if ($category != 0) {
    $stmt->bindParam(':category_id', $category, PDO::PARAM_INT);
}

if ($tag != 0) {
    $stmt->bindParam(':tag_id', $tag, PDO::PARAM_INT);
}

if (!empty($gallery)) {
    $galleryLike = "%$gallery%";
    $stmt->bindParam(':gallery', $galleryLike, PDO::PARAM_STR);
}

if (!empty($date_from)) {
    $stmt->bindParam(':date_from', $date_from);
}

if (!empty($date_to)) {
    $stmt->bindParam(':date_to', $date_to);
}

if (!empty($price_from)) {
    $stmt->bindParam(':price_from', $price_from);
}

if (!empty($price_to)) {
    $stmt->bindParam(':price_to', $price_to);
}

// Bind tham số LIMIT và OFFSET
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hiển thị kết quả
echo "<div class='box_table'>";
echo "<table id='tableID' class='ui compact celled table'>";
echo "
<thead>
<tr>
  <th class='date'>Date</th>
  <th class='prd_name'>Product name</th>
  <th>SKU</th>
  <th>Price</th>
  <th>Feature Image</th>
  <th class='gallery_name'>Gallery</th>
  <th >Categories</th>
  <th class='tag_name'>Tags</th>
  <th>Action</th>
</tr>
</thead>
";
echo "<tbody>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['sku']) . "</td>";
    echo "<td>" . htmlspecialchars($row['price']) . "</td>";
    echo "<td><img height='30' src='./uploads/" . htmlspecialchars($row['featured_image']) . "'></td>";

    $galleryImages = $row['gallery_images'];
    if (!empty($galleryImages)) {
        $galleryImagesArray = explode(', ', $galleryImages);
        echo "<td>";
        foreach ($galleryImagesArray as $image) {
            echo "<img height='30' src='./uploads/" . htmlspecialchars($image) . "'>";
        }
        echo "</td>";
    } else {
        echo "<td>No gallery images</td>";
    }
    echo "<td>" . htmlspecialchars($row['categories']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tags']) . "</td>";

    echo "<td>
    <input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>
    <button  type='submit' value='" . htmlspecialchars($row['id']) . "' class='edit_button'>
        <i class='edit icon'></i>
    </button>
    <a class='delete_button' href=''>
        <i class='trash icon'></i>
    </a>
    </td>";

    echo "</tr>";
}
echo "</tbody>";
echo "</table>";

echo '</div>';

$query = "SELECT COUNT(*) ";
$count_stmt = $pdo->prepare($query);
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page_record);

echo "
<div id='paginationBox' class='pagination_box'>
    <div class='ui pagination menu'>
        <?php
   

            if ($page > 1) {
                echo "<a onclick='prev(event)' class='item' data-page='".($page - 1)."'>Prev</a>";
            } else {
                echo "<a class='item '>Prev</a>";
            }

            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i == $page) ? 'active' : '';
                echo "<a onclick='pagination_number(event)' class='item $active_class' data-page='$i'>$i</a>";
            }

            if ($page < $total_pages) {
                echo "<a onclick='next(event)' class='item' data-page='".($page + 1)."'>Next</a>";
            } else {
                echo "<a class='item disabled'>Next</a>";
            }
        ?>
    </div>
</div>
"
?>




