<?php

// Check post data to get the input file
if ( isset($_POST["submit"]) ) {
	if ( isset($_FILES["file"])) {
		//if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . "<br/>";
        } else {
        
            //if file already exists
             if (file_exists($_FILES["file"]["name"])) {
                echo $_FILES["file"]["name"] . " already exists. ";
             } else {
                //Store file in directory “upload” with the name of “products_test_ram.txt”
                $storagename = "products.csv";
                move_uploaded_file($_FILES["file"]["tmp_name"], $storagename);
                echo "Product list is generated<br/>";
				echo "<a href=\"product_1.csv\">Download</a><br/>";
            }
        }
     } else {
        echo "File is not selected <br/>";
     }
}

$file = file_get_contents("products.csv");
$data = array_map("str_getcsv", preg_split('/\r*\n+|\r+/', $file));
array_splice($data, 0, 1); // skip first line


// START

$csv = array();
$csv[] = headerRow();

$buildLastConfigurationValues = array();
$lastProduct = end($data);

foreach ($data as $item=>$val) {

    if (is_array($val)) {

		$name = $val[1];
		$category = $val[2];
		$attributesDictionary = array('size' => beautifyName($val[3], true), 'blend' => beautifyName($val[4], true), 'milk' => beautifyName($val[5], true));
		//$attributesDictionary = array('size' => $val[3], 'blend' => $val[4], 'milk' => $val[5]);
		$priceDictionary = array('' => $val[6], 'airport' => $val[7], 'rail' => $val[8]);

		// Add configurable variation to array
		$sku = sku($name, $attributesDictionary);
		$addtionalAttributes = additionalAttributes($attributesDictionary);

		$isThisFirstPrice = true;
		foreach ($priceDictionary as $key => $value) { 
			print_r("name: " .$name."|category: ".$category."<br/>");
			$row = productRow($val[0], $name, $category, $key, $value, $attributesDictionary, true, $isThisFirstPrice, NULL);

			// if new product detected
			$lastItem = end($csv);
			$lastItemName = $lastItem[19]; // lastItem[19] (meta-title) contains the name
			$lastItemCategory = $lastItem[11]; // lastItem[11] (categories)

			if (($lastItemName != "meta_title" && $name != $lastItemName) || $lastProduct == $val) {
				$isThisFirstPrice = true;
				
				$additionalAttributesString = $lastItem[24];
				if (!isSimpleProduct($additionalAttributesString)) {			
					//$category = $lastItem[4]
					print_r("CONFIGURABLE name: " .$lastItemName."|category: ".$lastItemCategory."<br/>");
					$lastRow = productRow(NULL, $lastItemName, $lastItemCategory, NULL, NULL, NULL, false, $isThisFirstPrice, $buildLastConfigurationValues);
		
					// reset the array for the new product and add this product's configuration
					$buildLastConfigurationValues = array();
					$buildLastConfigurationValues[] = "sku=" .skuNumber($lastItem[0]). "," .$addtionalAttributes. "|";

					$csv[] = $lastRow;
				}
			} else if($isThisFirstPrice) {
				$buildLastConfigurationValues[] = "sku=" .skuNumber($lastItem[0]). "," .$addtionalAttributes . "|";
			}

			$isThisFirstPrice = false;
			$csv[] = $row;
		}
    }

}

$fp = fopen('product_1.csv', 'w');

foreach ($csv as $fields) {
    fputcsv($fp, $fields);
}

function headerRow() {
	$headerrow = array();
	$headerrow[] = "sku";
	$headerrow[] = "store_view_code";
	$headerrow[] = "attribute_set_code";
	$headerrow[] = "product_type";
	$headerrow[] = "base_image";    
	$headerrow[] = "base_image_label";             
	$headerrow[] = "small_image";
	$headerrow[] = "small_image_label";
	$headerrow[] = "thumbnail_image"; 
	$headerrow[] = "thumbnail_image_label"; 
	$headerrow[] = "additional_images";
	$headerrow[] = "categories";
	$headerrow[] = "product_websites";
	$headerrow[] = "name";
	$headerrow[] = "product_online";
	$headerrow[] = "tax_class_name";
	$headerrow[] = "visibility";
	$headerrow[] = "price";
	$headerrow[] = "url_key";
	$headerrow[] = "meta_title";
	$headerrow[] = "meta_keywords";
	$headerrow[] = "meta_description";
	$headerrow[] = "display_product_options_in";
	$headerrow[] = "gift_message_available";
	$headerrow[] = "additional_attributes";
	$headerrow[] = "qty";
	$headerrow[] = "out_of_stock_qty";
	$headerrow[] = "use_config_min_qty";
	$headerrow[] = "is_qty_decimal";
	$headerrow[] = "allow_backorders";
	$headerrow[] = "use_config_backorders";
	$headerrow[] = "min_cart_qty";
	$headerrow[] = "use_config_min_sale_qty";
	$headerrow[] = "max_cart_qty";
	$headerrow[] = "use_config_max_sale_qty";
	$headerrow[] = "is_in_stock";
	$headerrow[] = "notify_on_stock_below";
	$headerrow[] = "use_config_notify_stock_qty";
	$headerrow[] = "manage_stock";
	$headerrow[] = "use_config_manage_stock";
	$headerrow[] = "use_config_qty_increments";
	$headerrow[] = "qty_increments";
	$headerrow[] = "use_config_enable_qty_inc";
	$headerrow[] = "enable_qty_increments";
	$headerrow[] = "is_decimal_divided";
	$headerrow[] = "website_id";
	$headerrow[] = "configurable_variations";
	return $headerrow;
}

function productRow($skuColumn, $name, $category, $storeViewCode, $price, $attributesDictionary, $isSimpleProduct, $isThisFirstPrice, $buildLastConfigurationValues) {

	$newCategory = beautifyCategory($category);
	//print_r("*name: " .$name."|category: ".$newCategory."<br/>");
	$row = array();
	if ($isSimpleProduct) {								// 0 sku
		$sku = $skuColumn; // sku($name, $attributesDictionary);   
	} else {
		$sku = $name;
	}
	$row[] = $sku;
	if ($isSimpleProduct) {								// 1 store_view_code
		$row[] = $storeViewCode;		
	} else {
		$row[] = "";	
	}
	//$row[] = "Default"; 								
	if ($isSimpleProduct) {								// 2 attribute_set_code
		if (isSimple($attributesDictionary)) {
			$row[] = "Default"; 
		} else {
			$row[] = "Drinks";
		}
	} else {
		$row[] = "Drinks";
	}
	
	if ($isSimpleProduct) {								// 3 product_type
		if (isSimple($attributesDictionary)) {
			$row[] = "simple";
		} else {
			$row[] = "virtual";
		}  							
	} else {
		$row[] = "configurable";					
	}
	
	$row[] = ""; // 4 base_image
	$row[] = ""; // 5 base_image_label
	$row[] = ""; // 6 small_image
	$row[] = ""; // 7 small_image_label
	$row[] = ""; // 8 thumbnail_image
	$row[] = ""; // 9 thumbnail_image_label
	$row[] = ""; // 10 additional_images
	
	// 11 categories
	// TODO - Bug which dupilicating the 'Default Category/' required to fixed
	$trimDupilicatedCategory = str_replace('Default Category/', '', $newCategory);
	if ($isSimpleProduct) {								
		if (isSimple($attributesDictionary)) {
			$row[] = "Default Category/".$trimDupilicatedCategory; 
			//print_r("----Default Category/".$trimDupilicatedCategory."<br/>");
		} else {
			$row[] = "Default Category/".$trimDupilicatedCategory; 
			//print_r("---<br/>");
		}
	} else {
		$row[] = "Default Category/".$trimDupilicatedCategory;
		//print_r("---Default Category/".$trimDupilicatedCategory."<br/>");
	}

	if ($isThisFirstPrice) {
		if ($isThisFirstPrice) {        	
			$row[] = "base,rail,airport";  					// 12 product_websites
		} else {
			$row[] = "";
		}
		$row[] = sku($name, $attributesDictionary); 		// 13 name
		$row[] = 1;											// 14 product_online
		$row[] = "Taxable Goods";					 		// 15 tax_class_name
		if ($isSimpleProduct) {								// 16 visibility
			if (isSimple($attributesDictionary)) {
				$row[] = "Catalog, Search";
			} else {
				$row[] = "Not Visible Individually";
			} 	 
		} else {
			$row[] = "Catalog, Search";
		}
		if ($isSimpleProduct) {								// 17 price
			$row[] = $price;		
		} else {
			$row[] = "";	
		}								
		if ($isSimpleProduct) {								// 18 url_key
			$urlKey = strtolower($sku);					 		
		} else {
			$urlKey = $name;
		}
		$row[] = str_replace(' ', '-', $urlKey);
		$row[] = $name;										// 19 meta_title
		$row[] = $name;										// 20 meta_keywords
		$row[] = $name;										// 21 meta_description
		$row[] = "Block after Info Column";					// 22 display_product_options_in
		if ($isSimpleProduct) {								// 23 gift_message_available
			$row[] = "No";					 		
		} else {
			$row[] = "Use config";
		}
		if ($isSimpleProduct) {								// 24 addtional_attributes			
			$addtionalAttributes = additionalAttributes($attributesDictionary);
			$row[] = $addtionalAttributes;
		} else {
			$row[] = "";
		}	
		if ($isSimpleProduct) {				
			$row[] = "";									// 25 qty
		} else {
			$row[] = "0.0000";
		}
		$row[] = "0.0000";									// 26 out_of_stock_qty
		$row[] = 1;											// 27 use_config_min_qty
		$row[] = 0;											// 28 is_qty_decimal
		$row[] = 0;											// 29 allow_backorders
		$row[] = 1;											// 30 use_config_backorders
		$row[] = "1.0000";									// 31 min_cart_qty
		$row[] = 1;											// 32 use_config_min_sale_qty
		$row[] = "10000.0000";								// 33 max_cart_qty
		$row[] = 1;											// 34 use_config_max_sale_qty
		$row[] = 1;											// 35 is_in_stock
		$row[] = "1.0000";									// 36 notify_on_stock_below
		$row[] = 1;											// 37 use_config_notify_stock_qty
		$row[] = 0;											// 38 manage_stock
		$row[] = 1;											// 39 use_config_manage_stock
		$row[] = 1;											// 40 use_config_qty_increments
		$row[] = "1.0000";									// 41 qty_increments
		$row[] = 1;											// 42 use_config_enable_qty_inc
		$row[] = 0;											// 43 enable_qty_increments
		$row[] = 0;											// 44 is_decimal_divided
		$row[] = 0;											// 45 website_id
		if ($isSimpleProduct) {								// 46 configurable_variations
			$row[] = "";										
		} else {
			$row[] = configurableVariations($buildLastConfigurationValues);
		}
	} else {
		//$row[] = "Default Category/".$trimDupilicatedCategory; // 4 categories
		$row[] = "";  										// 12 product_websites
		$row[] = ""; 										// 13 name
		$row[] = "";										// 14 product_online
		$row[] = "";					 					// 15 tax_class_name
		$row[] = "";			 							// 16 visibility
		$row[] = $price;									// 17 price
		$row[] = "";										// 18 url_key
		$row[] = $name;										// 19 meta_title
		$row[] = "";										// 20 meta_keywords
		$row[] = "";										// 21 meta_description
		$row[] = "";										// 22 display_product_options_in
		$row[] = "";					 					// 23 gift_message_available
		if ($isSimpleProduct) {								// 24 addtional_attributes			
			$addtionalAttributes = additionalAttributes($attributesDictionary);
			$row[] = $addtionalAttributes;
		} else {
			$row[] = "";
		}
		$row[] = "";										// 25 qty
		$row[] = "";										// 26 out_of_stock_qty
		$row[] = "";										// 27 use_config_min_qty
		$row[] = "";										// 28 is_qty_decimal
		$row[] = "";										// 29 allow_backorders
		$row[] = "";										// 30 use_config_backorders
		$row[] = "";										// 31 min_cart_qty
		$row[] = "";										// 32 use_config_min_sale_qty
		$row[] = "";										// 33 max_cart_qty
		$row[] = "";										// 34 use_config_max_sale_qty
		$row[] = "";										// 35 is_in_stock
		$row[] = "";										// 36 notify_on_stock_below
		$row[] = "";										// 37 use_config_notify_stock_qty
		$row[] = "";										// 38 manage_stock
		$row[] = "";										// 39 use_config_manage_stock
		$row[] = "";										// 40 use_config_qty_increments
		$row[] = "";										// 41 qty_increments
		$row[] = "";										// 42 use_config_enable_qty_inc
		$row[] = "";										// 43 enable_qty_increments
		$row[] = "";										// 44 is_decimal_divided
		$row[] = "";										// 45 website_id
		$row[] = "";										// 46 configurable_variations
	}
	
	return $row;
}

function sku($name, $attributesDictionary) {
	$sku = $name;
	foreach ($attributesDictionary as $value) {
		if (strlen($value) >= 1) {
			$sku .= "-".$value;
		}
	}
	return $sku;
}

function additionalAttributes($attributesArray) {
	$addtionalAttributesArray = array();
	foreach ($attributesArray as $key => $value) {
		if (strlen($value) >= 1 && $previousAttribute != $value) {
			$addtionalAttributesArray[] = $key."=".$value;
		}
	}
	return implode(',', $addtionalAttributesArray);
}

function configurableVariations($buildLastConfigurationValues) {
	$buildConfigurationString = "";
	foreach ($buildLastConfigurationValues as $item=>$configval) {
		$buildConfigurationString .= $configval;
	}

	$buildConfigurationString = substr_replace($buildConfigurationString, "", -1);

	return $buildConfigurationString;
}

function isSimpleProduct($additionalAttributesString) {
	if (strpos($additionalAttributesString, 'size=') !== false || 
		strpos($additionalAttributesString, 'blend=') !== false || 
		strpos($additionalAttributesString, 'milk=') !== false) {
        return false;
    } else {
    	return true;
    }
}

function isSimple($attributesDictionary) {
	$isSimple = true;
	foreach ($attributesDictionary as $value) {
		if (strlen($value) >= 1) {
			$isSimple = false;
			break;
		}
	}
	return $isSimple;
}

function beautifyName($string) {
	$str = str_replace('_', ' ', $string);
	$str = strtolower($str);
	$str = ucwords($str);
	
	if ($str == Ops) {
		$str = "Old Paradise Street";
	}

    return $str;
}

function beautifyCategory($category) {
	$str = str_replace('Hot Drink', 'Hot Drinks', $category);
	$str = str_replace('Hot Drinkss', 'Hot Drinks', $str);
	$str = str_replace('Cold drink', 'Cold Drinks', $str);
	$str = str_replace('Cold Drinkss', 'Cold Drinks', $str);
	$str = str_replace('Cold food', 'Food', $str);
	$str = str_replace('Hot food', 'Food', $str);
	
	return $str;
}

function skuNumber($sku) {
	$skuNumber = $sku;
	if ($skuNumber == "sku") { 
		return 0; 
	} else {
		return $skuNumber+1;
	}
}

?>
