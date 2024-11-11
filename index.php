<?php
require_once 'includes/db.inc.php';
require_once './includes/functions.php';

$results = select_all_products($pdo);

$searchTerm = isset($_GET['search']) ? test_input($_GET['search']) : '';
$per_page_record = 5;
$page = isset($_GET["page"]) ? $_GET["page"] : 1;
$page = filter_var($page, FILTER_VALIDATE_INT) !== false ? (int)$page : 1;

$start_from = ($page - 1) * $per_page_record;

$query = "SELECT * FROM products LIMIT :start_from, :per_page";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
$stmt->bindParam(':per_page', $per_page_record, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="styles/style.css" type="text/css">

    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
   <style>
    .box_gallery{
    display: flex ;
    align-items: center;
    justify-content: space-between;
    height: 80px !important;
    }

    body::-webkit-scrollbar {
    display: none;

    }
    #galleryPreviewContainer {
    overflow-x: auto; 
    white-space: nowrap; 
}

#galleryPreviewContainer img {
    height: 80px;
    display: inline-block; 
}

    .form_add_products{
        padding: 1rem;
    }

    .img_box{
        overflow: hidden;
        width: 500px;
    }

    .edit_button {
    border: none;
    cursor: pointer;
    border-radius: 5px; 
    background-color: #fff;
}

.box_button_add{
    margin-top: 1rem;
}

#editProductButton{
    
}

#addProductButton{
}

.tag_name{
    width: 190px;
}

.prd_name{
    width: 206px;
}

table thead .date{
    width: 100px;
}

#tableID{
    text-align: center;
}

.select_tag{
    width: 100px;
}
.box_table{

    height: 400px;
    overflow-y: auto;  
    margin-bottom: 20px; 
}





   </style>
    <!-- link semantic ui -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.css"  />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>

    <title>PHP1</title>
</head>

<body>

<?php include('model_add_product.php');?>
<?php include('model_add_property.php');?>


    <section class="container">
        <h1>PHP1</h1>
        <div class="product_header">
            <div class="product_header_top">
                <div>
                    <button id="add_product" class="ui primary button">Add product</button>
                    <button id="add_property" class="ui button">Add property</button>
                    <a href="#" class="ui button">Sync online</a>
                </div>
                <div class="ui icon input">
                    <input id="search" type="text" placeholder="Search product..." value="">
                    <i onclick="applyFilters(event)"  class="inverted circular search link icon"></i>
                </div>
            </div>
            <div class="product_header_bottom">
                <select class="ui dropdown" id="sort_by">
                    <option value="date">Date</option>
                    <option value="product_name">Product name</option>
                    <option value="price">Price</option>
                </select>
                <select class="ui dropdown" id="order">
                    <option value="ASC">ASC</option>
                    <option value="DESC">DESC</option>
                </select>
                <select class="ui dropdown" id="category">
                    <option value="0">Category</option>
                    <?php
                    $query = "SELECT p.id, p.name_ FROM property p WHERE p.type_ = 'category'";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $selectedCategory = $_GET['category'] ?? 0;
                    foreach ($categories as $category) {
                        $selected = ($category['id'] == $selectedCategory) ? 'selected' : '';
                        echo "<option $selected value=\"{$category['id']}\">" . htmlspecialchars($category['name_']) . "</option>";
                    }
                    ?>
                </select>
                <select class="ui dropdown select_tag" id="tag">
                    <option value="0">Select Tag</option>
                    <?php
                    $query = "SELECT p.id, p.name_ FROM property p WHERE p.type_ = 'tag'";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $selectedTag = $_GET['tag'] ?? 0;
                    foreach ($tags as $tag) {
                        $selected = ($tag['id'] == $selectedTag) ? 'selected' : '';
                        echo "<option $selected value=\"{$tag['id']}\">" . htmlspecialchars($tag['name_']) . "</option>";
                    }
                    ?>
                </select>
                <div class="ui input"><input type="date" id="date_from"></div>
                <div class="ui input"><input type="date" id="date_to"></div>
                <div class="ui input"><input  onkeypress="return isNumber(event)" type="number" id="price_from" placeholder="price from"></div>
                <div class="ui input"><input  onkeypress="return isNumber(event)" type="number" id="price_to" placeholder="price to"></div>
                <button onclick="applyFilters(event)" class="ui button">Filter</button>
            </div>
        </div>
     
        <!-- table -->
         <div id="productTableBody"></div>

         <div id="box_table" class="box_table">

<table id="tableID" class="ui compact celled table">
<thead>
<tr>
  <th class="date">Date</th>
  <th class="prd_name">Product name</th>
  <th>SKU</th>
  <th>Price</th>
  <th>Feature Image</th>
  <th class="gallery_name">Gallery</th>
  <th >Categories</th>
  <th class="tag_name">Tags</th>
  <th>Action</th>
</tr>
</thead>
<tbody id="productTableBody">
<?php 
    if (isset($_GET["page"])) {    
        $page  = $_GET["page"];    
    } else {    
        $page=1;    
      }    
    
      
      if (count($results) > 0) {
        foreach ($results as $row){
          $product_id = $row['id']; ?>


 <tr>
 <td><?php echo htmlspecialchars($row['date'])?></td>
  <td class="product_name"><?php echo htmlspecialchars($row['product_name'])?></td>
  <td class="sku"><?php echo htmlspecialchars($row['sku'])?></td>
  <td><?php echo htmlspecialchars($row['price'])?></td>
  <td>
      <img height="30" src="./uploads/<?php echo $row['featured_image']; ?>">
  </td>
  <td class="gallery_images">
          <?php 
        $query = "SELECT p.name_ FROM product_property pp
                JOIN property p ON pp.property_id = p.id
                WHERE pp.product_id = :product_id AND p.type_ = 'gallery'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $galleryImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($galleryImages as $image) {?> 
    <img  height="40" src="./uploads/<?= $image['name_'] ?>">
  <?php }?>
  </td>
  <td>
  <?php 
        $query = "SELECT p.name_ FROM product_property pp
                JOIN property p ON pp.property_id = p.id
                WHERE pp.product_id = :product_id AND p.type_ = 'category'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalCategories = count($categories);
        foreach ($categories as $index => $category) {?> 
        <span><?php echo htmlspecialchars($category['name_']);
                if($index < $totalCategories -1 ){
                    echo ', ';
                }
               ?></span>
        <?php }?>
  </td>
  <td>
  <?php 
        $query = "SELECT p.name_ FROM product_property pp
                JOIN property p ON pp.property_id = p.id
                WHERE pp.product_id = :product_id AND p.type_ = 'tag'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalTags = count($tags);
        foreach ($tags as $index => $tag) {?> 
        <span><?php echo htmlspecialchars($tag['name_']);
                if($index < $totalTags -1 ){
                    echo ', ';
                }
               ?></span>
        <?php }?>
  </td>
  <td>
  <input  type="hidden" name="id" id="id">


    <button type="submit"   value="<?= $row['id']?>" class="edit_button" >
    <i class="edit icon"></i>
    </button>
  
    <a  class="delete_button" href="">
    <i class="trash icon"></i>
    </a>
  </td>
</tr>
<?php }}else {?>
    <tr>
        <td colspan="9" style="text-align: center;">Product not found</td>
    </tr>
    <?php }?>
</tbody>
</table>
</div>
         
        

<!-- pagination -->
<div id="paginationBox" class="pagination_box">
    <div class="ui pagination menu">
        <?php
            $query = "SELECT COUNT(*) FROM products";
            $count_stmt = $pdo->prepare($query);
            $count_stmt->execute();
            $total_records = $count_stmt->fetchColumn();
            $total_pages = ceil($total_records / $per_page_record);

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



</section>
<script src="./jquery/my_jquery_functions.js">

   

</script>
<script>
$(document).ready(function() {
    $('#paginationBox').on('click', '.item', function(e) {
        e.preventDefault();
        const page = $(this).data('page');

        if (page) {
            $.ajax({
                url: 'fetch_products.php',
                type: 'GET',
                data: { page: page },
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    // Update product table
                    $('#productTableBody').html(response.products);

                    // Update pagination links
                    $('#paginationBox .ui.pagination.menu').html(response.pagination);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data: ", error);
                }
            });
        }
    });
});


</script>


</body>
</html>