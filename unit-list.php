<?php

// Define the shortcode and the function to execute when the shortcode is used.
function custom_depotrum_list_func()
{
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
            $relTypeId = getRelTypeId($id);
            $output = '<div class="depotrum-row">';
            $output .= '<div class="flex-container">';
            if (get_post_meta($relTypeId, 'm2', true) != null) {
                $output .= '<div class="m2-column vertical-center">';
                $output .= '<span class="m2size">' . get_post_meta($relTypeId, 'm2', true) . '</span>';
                $output .= '<span class="m2label"> m2</span>';
                $output .= '</div>';
            } else if (get_post_meta($relTypeId, 'm3', true) != null) {
                $output .= '<div class="m3-column vertical-center">';
                $output .= '<span class="m3size">' . get_post_meta($relTypeId, 'm3', true) . '</span>';
                $output .= '<span class="m3label"> m3</span>';
                $output .= '</div>';
            }


            /*$output .= '<div class="placement-column vertical-center">';
            $placement = get_post_meta($relTypeId, 'placement', true);

            if ($placement == 'indoor') {
                $output .= '<div class="img vertical-center">';
                $output .= '<img src="https://tjekdepot.dk/wp-content/uploads/2023/11/indoor.png" alt="Icon of an indoor storage facility" width="35" height="35">';
                $output .= '</div>';
                $output .= '<div class="placement-text-div">';
                $output .= '<span class="placement-text">Placering:</span>';
                $output .= '<p class="placement-heading">Indendørs</p>';
                $output .= '</div>';
            } elseif ($placement == 'container') {
                $output .= '<div class="img vertical-center">';
                $output .= '<img src="https://tjekdepot.dk/wp-content/uploads/2023/11/container.png" alt="Icon of a container" width="35" height="35">';
                $output .= '</div>';
                $output .= '<div class="placement-text-div">';
                $output .= '<span class="placement-text">Placering:</span>';
                $output .= '<p class="placement-heading">I container</p>';
                $output .= '</div>';
            } elseif ($placement == 'isolated_container') {
                $output .= '<div class="img vertical-center">';
                $output .= '<img src="https://tjekdepot.dk/wp-content/uploads/2023/11/container.png" alt="Icon of a container" width="35" height="35">';
                $output .= '</div>';
                $output .= '<div class="placement-text-div">';
                $output .= '<span class="placement-text">Placering:</span>';
                $output .= '<p class="placement-heading">Isoleret container</p>';
                $output .= '</div>';
            }
            $output .= '</div>';*/
            $output .= '</div>';

            $output .= '<div class="price-column vertical-center">';
            if (get_post_meta($id, 'price', true)) {
                $output .= '<span class="price">' . round(get_post_meta($id, 'price', true), 2) . ' kr.</span>';
                //$output .= '<span class="month">/måned</span>';
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

function generate_non_partner_text($finalOutput)
{
    $current_pod = pods();

    $statistics_data_fields = get_statistics_data_for_single_gd_place($current_pod->field("id"));
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
            trigger_error("Encountered depotrum with no price, sorting by m2 size instead. depotrum ID:" .  $id, E_USER_WARNING);
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
        $relTypeId = getRelTypeId($id);
        $arrayObject = (object) [
            'id' => $id,
            'm2size' => get_post_meta($relTypeId, 'm2', true)
        ];

        if ($arrayObject->m2size == null) {
            trigger_error("Encountered depotrum with no price or m2 size, sorting by m3 size instead. depotrum ID:" .  $id, E_USER_WARNING);
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
        $relTypeId = getRelTypeId($id);
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

function getRelTypeId($id)
{
    $relType = get_post_meta($id, 'rel_type', true);
    if (is_array($relType)) {
        trigger_error("Rel type is an array for depotrum with id: " . $id);
        return $relType['ID'];
    } else {
        return $relType;
    }
}
