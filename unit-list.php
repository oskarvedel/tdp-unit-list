<?php


// Define the shortcode and the function to execute when the shortcode is used.
function custom_depotrum_list_func()
{
    xdebug_break();
    //echo plugin_dir_path(__FILE__) . '../tdp-common/tdp-common-plugin.php';
    $current_pod = pods();

    // Check if the Pod object exists and the field "partner" is set
    if ($current_pod && $current_pod->exists()) {
        $unit_items = $current_pod->field("depotrum");
        // check if each unit item is avaliable
        $available_unit_items = [];
        foreach ($unit_items as $unit_item) {
            if (get_post_meta($unit_item['ID'], 'available', true)) {
                array_push($available_unit_items, $unit_item);
            }
        }
        $hide_units = $current_pod->field("hide_units");

        if ($available_unit_items && !empty($available_unit_items) && !$hide_units) {
            $partner = $current_pod->field("partner");
            $lokationId = $current_pod->field("id");
            $permalink = get_permalink($lokationId);

            $finalOutput = '<div class="depotrum-list">';
            $finalOutput .= generate_unit_list($finalOutput, $partner, $lokationId, $available_unit_items, $permalink);
            $finalOutput .= "</div>";

            $finalOutput .= generate_view_all_button($permalink, $partner);
            return $finalOutput;
        }
    }
}

// Register the shortcode.
add_shortcode("custom_depotrum_list", "custom_depotrum_list_func");

function generate_unit_list($finalOutput, $partner, $lokationId, $available_unit_items, $permalink)
{
    $isArchivePage = 0;
    if (geodir_is_page('post_type') || geodir_is_page('search')) {
        $isArchivePage = 1;
    }
    $sorted_ids = [];
    try {
        $sorted_ids = sort_depotrum_by_price($available_unit_items);
    } catch (Exception $e) {
        try {
            $sorted_ids = sort_depotrum_by_m2_size($available_unit_items);
        } catch (Exception $e) {
            $sorted_ids = sort_depotrum_by_m3_size($available_unit_items);
        }
    }

    if (geodir_is_page('post_type') || geodir_is_page('search')) {
        $sorted_ids = extract_evenly_spaced($sorted_ids, 4);
    }
    $OutputArray = [];
    $output = '';
    $lastElement = end($sorted_ids);
    foreach ($sorted_ids as $depotrum) {
        $id = $depotrum->id;
        $relTypeId = getRelTypeId_unitlist($id);
        $unit_type = get_post_meta($relTypeId, 'unit_type', true);
        $m2 = get_post_meta($relTypeId, 'm2', true);
        $m3 = get_post_meta($relTypeId, 'm3', true);

        $container_type = get_post_meta($relTypeId, 'container_type', true);
        $isolated_container = get_post_meta($relTypeId, 'isolated_container', true);
        $ventilated_container = get_post_meta($relTypeId, 'ventilated_container', true);
        $price = get_post_meta($id, 'price', true);

        if ($isArchivePage) {
            if ($partner) {
                if ($depotrum === $lastElement) {
                    $output = '<a href="' . $permalink . '" class="depotrum-row partner last">';
                } else {
                    $output = '<a href="' . $permalink . '" class="depotrum-row partner">';
                }
            } else {
                if ($depotrum === $lastElement) {
                    $output = '<div class="depotrum-row non-partner last">';
                } else {
                    $output = '<div class="depotrum-row non-partner">';
                }
            }
        } else {
            if ($depotrum === $lastElement) {
                $output = '<div class="depotrum-row non-partner last">';
            } else {
                $output = '<div class="depotrum-row non-partner">';
            }
        }


        $output .= '<div class="flex-container">';
        $output .= generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type, $partner);
        $output .= generate_unit_desc_column($relTypeId, $unit_type, $m2, $m3, $container_type, $partner);

        $output .= '</div>';

        $output .= generate_price_column($price, $partner);

        $output .= generate_navigation_column($partner);

        // if ($partner) {
        //     $output .= '</a>';
        // } else {
        //     $output .= '</div>';
        // }

        if ($isArchivePage) {
            if ($partner) {
                $output .= '</a>';
            } else {
                $output .= '</div>';
            }
        } else {
            $output .= '</div>';
        }

        array_push($OutputArray, $output);
    }
    foreach ($OutputArray as $arrayItem) {
        $finalOutput .= $arrayItem;
    }
    return $finalOutput;
}

function generate_view_all_button($permalink, $partner)
{
    if ((geodir_is_page('post_type') || geodir_is_page('search')) && $partner == 1) {
        $finalOutput = '<form action="' . $permalink . '">';
        $finalOutput .= '<input type="submit" class="view-all-button" value="Se alle priser" />';
        $finalOutput .= '</form>';
        return $finalOutput;
    } else {
        return '';
    }
}

function generate_navigation_column($partner)
{
    $output = '<div class="navigation-column vertical-center">';
    if ((geodir_is_page('post_type') || geodir_is_page('search')) && $partner) { //search or archive page + partner
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25" height="25">';
        $output .= '<path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" />';
        $output .= '</svg>';
    } else if (geodir_is_page('post_type') || geodir_is_page('search') && !$partner) { //search or archive page + non-partner
        $output .= '';
    } else if (!geodir_is_page('post_type') || !geodir_is_page('search') && $partner) { //department page + partner
        //booking btn (to be developed)
        // $output .= do_shortcode('[gd_ninja_forms form_id="5" text="Fortsæt" post_contact="1" output="button" bg_color="#FF3369" txt_color="#ffffff" size="h5" css_class="ninja-forms-book-button"]');
    } else if (!geodir_is_page('post_type') || !geodir_is_page('search') && !$partner) { //department page + non-partner
        $output .= '';
    }
    $output .= '</div>';
    return $output;
}

function generate_price_column($price, $partner)
{
    $output = '<div class="price-column vertical-center">';
    if ($price && $partner) {
        $output .= '<span class="price partner">' . round($price, 2) . ' kr.</span>';
    } else if ($price && !$partner) {
        $output .= '<span class="price non-partner">' . round($price, 2) . ' kr.</span>';
    } else {
        $output .= '<span class="month">Pris ukendt</span>';
    }
    $output .= '</div>';
    return $output;
}

function generate_unit_desc_column($relTypeId, $unit_type, $m2, $m3, $container_type, $partner)
{
    $output = '<div class="size-column vertical-center">';

    if ($unit_type == "container") {
        if ($container_type == "8 feet") {
            $output .= '<span class="big">8-fods container</span>';
            $output .= '<div class="break"></div>';
            if ($m2 && $m3) {
                $output .= '<span class="detaileddesc">8-fods container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else if ($m2) {
                $output .= '<span class="detaileddesc">8-fods container på ' . number_format($m2, 1, ',', '') . ' m²</span>';
            } else if ($m3) {
                $output .= '<span class="detaileddesc">8-fods container på ' . number_format($m3, 1, ',', '') . 'm³</span>';
            }
        } else if ($container_type == "10 feet") {
            $output .= '<span class="big">10-fods container</span>';
            $output .= '<div class="break"></div>';
            if ($m2 && $m3) {
                $output .= '<span class="detaileddesc">10-fods container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else if ($m2) {
                $output .= '<span class="detaileddesc">10-fods container på ' . number_format($m2, 1, ',', '') . ' m²</span>';
            } else if ($m3) {
                $output .= '<span class="detaileddesc">10-fods container på ' . number_format($m3, 1, ',', '') . 'm³</span>';
            }
        } else if ($container_type == "20 feet") {
            $output .= '<span class="big">20-fods container</span>';
            $output .= '<div class="break"></div>';
            if ($m2 && $m3) {
                $output .= '<span class="detaileddesc">20-fods container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else if ($m2) {
                $output .= '<span class="detaileddesc">20-fods container på ' . number_format($m2, 1, ',', '') . 'm²</span>';
            } else if ($m3) {
                $output .= '<span class="detaileddesc">20-fods container på ' . number_format($m3, 1, ',', '') . 'm³</span>';
            }
        } else if ($container_type == "20 feet high cube") {
            $output .= '<span class="big">20-fods container</span>';
            $output .= '<div class="break"></div>';
            if ($m2 && $m3) {
                $output .= '<span class="detaileddesc">20-fods container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else if ($m2) {
                $output .= '<span class="detaileddesc">20-fods container på ' . number_format($m2, 1, ',', '') . 'm²</span>';
            } else if ($m3) {
                $output .= '<span class="detaileddesc">20-fods container på ' . number_format($m3, 1, ',', '') . 'm³</span>';
            }
        } else if ($container_type == "40 feet") {
            $output .= '<span class="big">40-fods container</span>';
            $output .= '<div class="break"></div>';
            if ($m2 && $m3) {
                $output .= '<span class="detaileddesc">40-fods container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else if ($m2) {
                $output .= '<span class="detaileddesc">40-fods container på ' . number_format($m2, 1, ',', '') . 'm²</span>';
            } else if ($m3) {
                $output .= '<span class="detaileddesc">40-fods container på ' . number_format($m3, 1, ',', '') . 'm³</span>';
            }
        } else if ($container_type == "40 feet high cube") {
            $output .= '<div class="big vertical-center">';
            $output .= '<span class="big">40-fods container</span>';
            $output .= '</div>';
            $output .= '<div class="break"></div>';
            if ($m2 && $m3) {
                $output .= '<span class="detaileddesc">40-fods container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else if ($m2) {
                $output .= '<span class="detaileddesc">40-fods container på ' . number_format($m2, 1, ',', '') . 'm²</span>';
            } else if ($m3) {
                $output .= '<span class="detaileddesc">40-fods container på ' . number_format($m3, 1, ',', '') . 'm³</span>';
            }
        }
    } else if ($unit_type == "unit_in_container") {
        if ($m2 && $m3) {
            $output .= '<span class="smallbold">Depotrum</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="bigbold">' . number_format($m2, 1, ',', '') . ' m²</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc">Depotrum i container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
        } else if ($m2) {
            $output .= '<span class="smallbold">Depotrum</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="bigbold">' . number_format($m2, 1, ',', '') . ' m²</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc">Depotrum i container på ' . number_format($m2, 1, ',', '') . ' m²</span>';
        } else if ($m3) {
            $output .= '<span class="smallbold">Depotrum</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="bigbold">' . number_format($m3, 1, ',', '') . ' m³</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc">Depotrum i container på ' . number_format($m3, 1, ',', '') . ' m³</span>';
        }
    } else if ($unit_type == "indoor") {
        if ($m2 && $m3) {
            $output .= generate_unit_size_smallbold_text($m2, $m3, $partner);
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="bigbold">' . number_format($m2, 1, ',', '') . ' m²</span>';
            } else {
                $output .= '<span class="normal">' . number_format($m2, 1, ',', '') . ' m²</span>';
            }
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="detaileddesc">Indendørs depotrum på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            }
        } else if ($m2) {
            $output .= generate_unit_size_smallbold_text($m2, $m3, $partner);
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="bigbold">' . number_format($m2, 1, ',', '') . ' m²</span>';
            } else {
                $output .= '<span class="big">' . number_format($m2, 1, ',', '') . ' m²</span>';
            }
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="detaileddesc">Indendørs depotrum på ' . number_format($m2, 1, ',', '') . ' m²</span>';
            }
        } else if ($m3) {
            $output .= generate_unit_size_smallbold_text($m2, $m3, $partner);
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="bigbold">' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else {
                $output .= '<span class="big">' . number_format($m3, 1, ',', '') . ' m³</span>';
            }
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="detaileddesc">Indendørs depotrum på ' . number_format($m3, 1, ',', '') . ' m³</span>';
            }
        }
    }

    $output .= '</div>';

    return $output;
}

function generate_unit_size_smallbold_text($m2, $m3, $partner)
{
    if (!$partner) {
        return '';
    }
    if ($m2) {
        $m2 = floatval($m2); // Convert m2 to integer if it's a string
        if ($m2 <= 1) {
            $output = '<span class="smallbold">Mikro depotrum</span>';
        } elseif ($m2 > 1 && $m2 <= 2.5) {
            $output = '<span class="smallbold">Mini depotrum</span>';
        } elseif ($m2 > 2.5 && $m2 <= 4) {
            $output = '<span class="smallbold">Lille depotrum</span>';
        } elseif ($m2 > 4 && $m2 <= 6) {
            $output = '<span class="smallbold">Mellem depotrum</span>';
        } elseif ($m2 > 6 && $m2 <= 8) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m2 > 8 && $m2 <= 12) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m2 > 12 && $m2 <= 30) {
            $output = '<span class="smallbold">Meget stort depotrum</span>';
        } elseif ($m2 > 30) {
            $output = '<span class="smallbold">Meget stort depotrum</span>';
        }
    } else if ($m3) {
        $m3 = floatval($m3); // Convert m3 to integer if it's a string
        if ($m3 <= 2.5) {
            $output = '<span class="smallbold">Mikro depotrum</span>';
        } elseif ($m3 > 2.5 && $m3 <= 6.25) {
            $output = '<span class="smallbold">Mini depotrum</span>';
        } elseif ($m3 > 6.25 && $m3 <= 10) {
            $output = '<span class="smallbold">Lille depotrum</span>';
        } elseif ($m3 > 10 && $m3 <= 15) {
            $output = '<span class="smallbold">Mellem depotrum</span>';
        } elseif ($m3 > 15 && $m3 <= 20) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m3 > 20 && $m3 <= 30) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m3 > 30 && $m3 <= 75) {
            $output = '<span class="smallbold">Meget stort depotrum</span>';
        } elseif ($m3 > 75) {
            $output = '<span class="smallbold">Meget stort depotrum</span>';
        }
    } else {
        $output = '<span class="smallbold">Depotrum</span>';
    }

    return $output;
}

function generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type, $partner)
{
    if (!$partner) {
        return '';
    }
    if ($unit_type == "container") {
        if ($container_type == "8 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
        } else if ($container_type == "10 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
        } else if ($container_type == "20 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
        } else if ($container_type == "20 feet high cube") {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
        } else if ($container_type == "40 feet") {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
        } else if ($container_type == "40 feet high cube") {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
        } else {
            $image_url = plugins_url('size-illustrations/10-feet-container-orig.png', __FILE__);
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
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m2 > 8 && $m2 <= 12) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m2 > 12 && $m2 <= 30) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m2 > 30) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
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
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m3 > 20 && $m3 <= 30) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m3 > 30 && $m3 <= 75) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m3 > 75) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            }
        } else {
            $image_url = plugins_url('size-illustrations/large.png', __FILE__);
        }
    } else {
        $image_url = plugins_url('size-illustrations/small.png', __FILE__);
    }



    $output = '<div class="image-column vertical-center">';
    $output .= '<img src="' . $image_url . '" width="70" height="70" alt="Illustration af depotrum" />';
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

function sort_depotrum_by_price($available_unit_items)
{
    // Get all depotrum and sort by price
    $AllDepotrumArray = [];

    foreach ($available_unit_items as $depotrum) {

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

function sort_depotrum_by_m2_size($available_unit_items)
{
    $AllDepotrumArray = [];

    foreach ($available_unit_items as $depotrum) {

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

function sort_depotrum_by_m3_size($available_unit_items)
{
    $AllDepotrumArray = [];

    foreach ($available_unit_items as $depotrum) {

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
