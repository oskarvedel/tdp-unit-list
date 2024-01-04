<?php


// Define the shortcode and the function to execute when the shortcode is used.
function custom_depotrum_list_func()
{
    //echo plugin_dir_path(__FILE__) . '../tdp-common/tdp-common-plugin.php';
    $current_pod = pods();

    // Check if the Pod object exists and the field "partner" is set
    if ($current_pod && $current_pod->exists()) {
        $depotrum_items = $current_pod->field("depotrum");
        $lokationId = $current_pod->field("id");
        $hide_units = $current_pod->field("hide_units");

        if ($depotrum_items && !empty($depotrum_items) && !$hide_units) {
            $partner = $current_pod->field("partner");

            $finalOutput = '<div class="depotrum-list">';
            if ($partner == 1) {
                $finalOutput .= generate_unit_list($finalOutput, $partner, $lokationId, $depotrum_items);
            } else {
                $finalOutput .= generate_non_partner_text($finalOutput);
            }
            $finalOutput .= "</div>";

            if ((geodir_is_page('post_type') || geodir_is_page('search')) && $partner == 1) {
                $finalOutput .= '<form action="' . get_permalink($lokationId) . '">';
                $finalOutput .= '<input type="submit" class="view-all-button" value="Se alle priser" />';
                $finalOutput .= '</form>';
            }
            return $finalOutput;
        }
    }
}

// Register the shortcode.
add_shortcode("custom_depotrum_list", "custom_depotrum_list_func");

function generate_unit_list($finalOutput, $partner, $lokationId, $depotrum_items)
{
    $sorted_ids = [];
    try {
        $sorted_ids = sort_depotrum_by_price($depotrum_items);
    } catch (Exception $e) {
        try {
            $sorted_ids = sort_depotrum_by_m2_size($depotrum_items);
        } catch (Exception $e) {
            $sorted_ids = sort_depotrum_by_m3_size($depotrum_items);
        }
    }

    if (geodir_is_page('post_type') || geodir_is_page('search')) {
        $sorted_ids = extract_evenly_spaced($sorted_ids, 4);
    }
    $OutputArray = [];
    $output = '';
    foreach ($sorted_ids as $depotrum) {
        $id = $depotrum->id;
        if (get_post_meta($id, 'available', true)) {
            $relTypeId = getRelTypeId_unitlist($id);
            $unit_type = get_post_meta($relTypeId, 'unit_type', true);
            $m2 = get_post_meta($relTypeId, 'm2', true);
            $m3 = get_post_meta($relTypeId, 'm3', true);
            $container_type = get_post_meta($relTypeId, 'container_type', true);
            $isolated_container = get_post_meta($relTypeId, 'isolated_container', true);
            $ventilated_container = get_post_meta($relTypeId, 'ventilated_container', true);
            $price = get_post_meta($id, 'price', true);

            $output = '<div class="depotrum-row">';
            $output .= '<div class="flex-container">';
            $output .= generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type);
            $output .= generate_unit_size_column($relTypeId, $unit_type, $m2, $m3, $container_type);

            $output .= '</div>';

            $output .= '<div class="price-column vertical-center">';
            if ($price) {
                $output .= '<span class="price">' . round(get_post_meta($id, 'price', true), 2) . ' kr.</span>';
            } else {
                $output .= '<span class="month">Pris ukendt</span>';
            }
            $output .= '</div>';

            $output .= '<div class="navigation-column vertical-center">';
            if ($partner && !geodir_is_page('post_type') && !geodir_is_page('search')) {
                $output .= do_shortcode('[gd_ninja_forms form_id="5" text="Fortsæt" post_contact="1" output="button" bg_color="#FF3369" txt_color="#ffffff" size="h5" css_class="ninja-forms-book-button"]');
            } else {
                $output .= '<a href="' . get_permalink($lokationId) . '">';
                $output .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25" height="25">';
                $output .= '<path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" />';
                $output .= '</svg>';
                $output .= '</a>';
            }
            $output .= '</div>';
            $output .= '</div>';

            array_push($OutputArray, $output);
        }
    }
    foreach ($OutputArray as $arrayItem) {
        $finalOutput .= $arrayItem;
    }
    return $finalOutput;
}

function generate_unit_size_column($relTypeId, $unit_type, $m2, $m3, $container_type)
{

    $output = '<div class="size-column vertical-center">';
    if ($m2) {
        $output .= '<span class="size">' . $m2 . '</span>';
        $output .= '<span class="sizelabel"> m²</span>';
    } else if ($m3) {
        $output .= '<span class="size">' . $m3 . '</span>';
        $output .= '<span class="sizelabel"> m³</span>';
    }

    $output .= '<div class="break"></div>';

    if ($unit_type == "container") {
        $output .= '<span class="type">Container</span>';
    } else if ($unit_type == "unit_in_container") {
        $output .= '<span class="type"> Depotrum i container</span>';
    } else if ($unit_type == "indoor") {
        $output .= '<span class="type"> Indendørs depotrum</span>';
    }
    $output .= '</div>';

    return $output;
}

function generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type)
{
    if ($unit_type == "container") {
        if ($container_type == "8 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        } else if ($container_type == "10 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        } else if ($container_type == "20 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        } else if ($container_type == "20 feet high cube") {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        } else if ($container_type == "40 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        } else if ($container_type == "40 feet high cube") {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        } else {
            $image_url = plugins_url('size-illustrations/10-feet-container.png', __FILE__);
        }
    } else if ($unit_type == "indoor") {
        if ($m2) {
            $m2 = floatval($m2); // Convert m2 to integer if it's a string
            if ($m2 <= 1) {
                $image_url = plugins_url('size-illustrations/micro.png', __FILE__);
            } elseif ($m2 > 1 && $m2 <= 2.5) {
                $image_url = plugins_url('size-illustrations/mini.png', __FILE__);
            } elseif ($m2 > 2.5 && $m2 <= 4) {
                $image_url = plugins_url('size-illustrations/small.png', __FILE__);
            } elseif ($m2 > 4 && $m2 <= 6) {
                $image_url = plugins_url('size-illustrations/mediums.png', __FILE__);
            } elseif ($m2 > 6 && $m2 <= 8) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            } elseif ($m2 > 8 && $m2 <= 12) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            } elseif ($m2 > 12 && $m2 <= 30) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            } elseif ($m2 > 30) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            }
        } else if ($m3) {
            $m3 = floatval($m3); // Convert m3 to integer if it's a string
            if ($m3 <= 2.5) {
                $image_url = plugins_url('size-illustrations/micro.png', __FILE__);
            } elseif ($m3 > 2.5 && $m3 <= 6.25) {
                $image_url = plugins_url('size-illustrations/mini.png', __FILE__);
            } elseif ($m3 > 6.25 && $m3 <= 10) {
                $image_url = plugins_url('size-illustrations/small.png', __FILE__);
            } elseif ($m3 > 10 && $m3 <= 15) {
                $image_url = plugins_url('size-illustrations/mediums.png', __FILE__);
            } elseif ($m3 > 15 && $m3 <= 20) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            } elseif ($m3 > 20 && $m3 <= 30) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            } elseif ($m3 > 30 && $m3 <= 75) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            } elseif ($m3 > 75) {
                $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
            }
        } else {
            $image_url = plugins_url('size-illustrations/stor.png', __FILE__);
        }
    } else {
        $image_url = plugins_url('size-illustrations/small.png', __FILE__);
    }



    $output = '<div class="image-column vertical-center">';
    $output .= '<img src="' . $image_url . '" width="60" height="60" alt="Illustration af depotrum" />';
    $output .= '</div>';

    return $output;
}


function generate_non_partner_text($finalOutput)
{
    $current_pod = pods();

    $statistics_data_fields = get_statistics_data_for_single_gd_place_unit_list($current_pod->field("id"));
    if (empty($statistics_data_fields)) {
        trigger_error("error in generate_non_partner_text: statistics_data_fields is empty for id: " . $current_pod->field("post_title"), E_USER_WARNING);
    }
    $finalOutput .= '<p class="unit_list_non_partner_text"><small>[location] tilbyder depotrum';

    if (($statistics_data_fields['smallest m2 size'] != null) && ($statistics_data_fields['largest m2 size'] != null)) {
        $finalOutput .= ' fra [smallest m2 size] m² til [largest m2 size] m²';
    }
    if (($statistics_data_fields['lowest price'] != null) && ($statistics_data_fields['highest price'] != null)) {
        $finalOutput .= ' i prislejet [lowest price] kr til [highest price] kr';
    }
    $finalOutput .= '. </small></p>';

    $gd_place_title = ($current_pod->field("post_title"));

    $finalOutput = str_replace("[location]", $gd_place_title, $finalOutput);

    foreach ($statistics_data_fields as $field => $value) {
        if (!empty($value)) {
            $rounded = floatval(round($value, 2));
            $numberformat = number_format($value, 0, ',', '.');
            $finalOutput = str_replace("[$field]", $numberformat, $finalOutput);
        }
    }
    return $finalOutput;
}

function sort_depotrum_by_price($depotrum_items)
{
    // Get all depotrum and sort by price
    $AllDepotrumArray = [];

    foreach ($depotrum_items as $depotrum) {

        $id = $depotrum['ID'];
        $arrayObject = (object) [
            'id' => $id,
            'price' => get_post_meta($id, 'price', true),
        ];

        if ($arrayObject->price == null) {
            // trigger_error("Encountered depotrum with no price, sorting by m2 size instead. depotrum ID:" .  $id, E_USER_NOTICE);
            throw new Exception('Encountered depotrum with no price, sorting by m2 size instead');
        }

        array_push($AllDepotrumArray, $arrayObject);
    }

    usort($AllDepotrumArray, function ($a, $b) {
        return $a->price > $b->price ? 1 : -1;
    });

    return $AllDepotrumArray;
}

function sort_depotrum_by_m2_size($depotrum_items)
{
    $AllDepotrumArray = [];

    foreach ($depotrum_items as $depotrum) {

        $id = $depotrum['ID'];
        $relTypeId = getRelTypeId_unitlist($id);
        $arrayObject = (object) [
            'id' => $id,
            'm2size' => get_post_meta($relTypeId, 'm2', true)
        ];

        if ($arrayObject->m2size == null) {
            // trigger_error("Encountered depotrum with no price or m2 size, sorting by m3 size instead. depotrum ID:" .  $id, E_USER_NOTICE);
            throw new Exception('Encountered depotrum with no price or m2 size, sorting by m3 size instead');
        }
        array_push($AllDepotrumArray, $arrayObject);
    }

    usort($AllDepotrumArray, function ($a, $b) {
        return $a->m2size > $b->m2size ? 1 : -1;
    });

    return $AllDepotrumArray;
}

function sort_depotrum_by_m3_size($depotrum_items)
{
    $AllDepotrumArray = [];

    foreach ($depotrum_items as $depotrum) {

        $id = $depotrum['ID'];
        $relTypeId = getRelTypeId_unitlist($id);
        $arrayObject = (object) [
            'id' => $id,
            'm3size' => get_post_meta($relTypeId, 'm3', true)
        ];

        if ($arrayObject->m3size == null) {
            trigger_error("Encountered depotrum with no price, m2 size or m3 size, giving up on sorting. depotrum ID:" .  $id, E_USER_WARNING);
        }

        array_push($AllDepotrumArray, $arrayObject);
    }

    usort($AllDepotrumArray, function ($a, $b) {
        return $a->m3size > $b->m3size ? 1 : -1;
    });

    return $AllDepotrumArray;
}

function extract_evenly_spaced($array, $num_values)
{
    $size = count($array);
    if ($num_values > $size) {
        return $array;
    }

    $step = intval($size / $num_values);
    $result = [];

    for ($i = 0; $i < $size; $i += $step) {
        $result[] = $array[$i];
    }

    return $result;
}

function getRelTypeId_unitlist($id)
{
    $relType = get_post_meta($id, 'rel_type', true);
    if (is_array($relType)) {
        // trigger_error("Rel type is an array for depotrum with id: " . $id);
        return $relType['ID'];
    } else {
        return $relType;
    }
}
