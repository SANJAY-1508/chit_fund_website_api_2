<?php

include 'db/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d H:i:s');



if (isset($obj->search_text)) {
    // <<<<<<<<<<===================== LIST REGISTRATIONS =====================>>>>>>>>>>
    $search_text = $obj->search_text;
    $sql = "SELECT `id`, `reg_id`, `name`, `phone_number`, `alternate_phone_number`, `address`, `create_at`, `delete_at` 
            FROM `registration` 
            WHERE `delete_at` = 0 AND (`name` LIKE '%$search_text%' OR `phone_number` LIKE '%$search_text%') 
            ORDER BY `id` DESC";
    
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "Success";
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $output["body"]["registration"][$count] = $row;
            $count++;
        }
    } else {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "No Registrations Found";
        $output["body"]["registration"] = [];
    }

} else if (isset($obj->name) && isset($obj->reg_id) && isset($obj->phone_number)) {
    // <<<<<<<<<<===================== CREATE AND EDIT (No Role) =====================>>>>>>>>>>
    $name = $obj->name;
    $reg_id = $obj->reg_id;
    $phone_number = $obj->phone_number;
    $alt_phone = isset($obj->alternate_phone_number) ? $obj->alternate_phone_number : '';
    $address = isset($obj->address) ? $obj->address : '';

    if (!empty($name) && !empty($reg_id) && !empty($phone_number)) {
        if (numericCheck($phone_number) && strlen($phone_number) >= 10) {

            if (isset($obj->edit_id)) {
                // UPDATE Logic
                $edit_id = $obj->edit_id;
                $updateSql = "UPDATE `registration` SET 
                              `name`='$name', 
                              `reg_id`='$reg_id', 
                              `phone_number`='$phone_number', 
                              `alternate_phone_number`='$alt_phone', 
                              `address`='$address' 
                              WHERE `id`='$edit_id'";

                if ($conn->query($updateSql)) {
                    $output["head"]["code"] = 200;
                    $output["head"]["msg"] = "Registration Updated Successfully";
                } else {
                    $output["head"]["code"] = 400;
                    $output["head"]["msg"] = "Update Failed: " . $conn->error;
                }
            } else {
                // CREATE Logic (ID is auto-incremented by DB)
                $check = $conn->query("SELECT `id` FROM `registration` WHERE `reg_id`='$reg_id' AND `delete_at` = 0");
                if ($check->num_rows == 0) {
                    $createSql = "INSERT INTO `registration` (`reg_id`, `name`, `phone_number`, `alternate_phone_number`, `address`, `create_at`, `delete_at`) 
                                  VALUES ('$reg_id', '$name', '$phone_number', '$alt_phone', '$address', '$timestamp', 0)";

                    if ($conn->query($createSql)) {
                        $output["head"]["code"] = 200;
                        $output["head"]["msg"] = "Successfully Registered";
                        $output["body"]["id"] = $conn->insert_id; // Returns the new Auto-ID
                    } else {
                        $output["head"]["code"] = 400;
                        $output["head"]["msg"] = "Registration Failed: " . $conn->error;
                    }
                } else {
                    $output["head"]["code"] = 400;
                    $output["head"]["msg"] = "Registration ID already exists.";
                }
            }
        } else {
            $output["head"]["code"] = 400;
            $output["head"]["msg"] = "Invalid Phone Number.";
        }
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please fill all required fields.";
    }

} else if (isset($obj->delete_id)) {
    // SOFT DELETE
    $delete_id = $obj->delete_id;
    $deleteSql = "UPDATE `registration` SET `delete_at` = 1 WHERE `id` = '$delete_id'";
    if ($conn->query($deleteSql)) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "Deleted Successfully.";
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to delete.";
    }
}

echo json_encode($output, JSON_NUMERIC_CHECK);