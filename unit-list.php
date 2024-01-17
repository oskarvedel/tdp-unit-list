<?php


function generate_default_unit_list_for_all_gd_places()
{
    $gd_places = get_posts(array(
        'post_type' => 'gd_place',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    foreach ($gd_places as $gd_place) {
        $gd_place_id = $gd_place->ID;
        $default_archive_page_unit_list = generate_default_unit_list_for_single_gd_place($gd_place_id, 1);
        if ($default_archive_page_unit_list) {
            update_post_meta($gd_place_id, 'default_archive_page_unit_list', $default_archive_page_unit_list);
        }
        $default_department_page_unit_list = generate_default_unit_list_for_single_gd_place($gd_place_id, 0);
        if ($default_department_page_unit_list) {
            update_post_meta($gd_place_id, 'default_department_page_unit_list', $default_department_page_unit_list);
        }
    }
    trigger_error("Default unit lists updated for all gd_places", E_USER_NOTICE);
}

function generate_default_unit_list_for_single_gd_place($gd_place_id, $isArchivePage)
{
    // if ($gd_place_id == 1758) {
    //     xdebug_break();
    // }
    $show_units = get_post_meta($gd_place_id, 'show_units', true);
    if (!$show_units) {
        return '';
    }
    $unit_items = get_post_meta($gd_place_id, 'depotrum', false);
    if (!$unit_items) {
        return '';
    }

    // check if each unit item is avaliable
    $available_unit_items = [];
    foreach ($unit_items as $unit_item) {
        if (get_post_meta($unit_item['ID'], 'available', true)) {
            array_push($available_unit_items, $unit_item);
        }
    }

    $enable_booking = get_post_meta($gd_place_id, 'enable_booking', true);
    if ($available_unit_items && !empty($available_unit_items) && $show_units) {
        $partner = get_post_meta($gd_place_id, 'partner', true);
        $permalink = get_permalink($lokationId);

        $finalOutput = '';
        $finalOutput .= generate_unit_list($finalOutput, $partner, $gd_place_id, $available_unit_items, $permalink, $enable_booking, $isArchivePage);

        $finalOutput .= generate_view_all_button($permalink, $partner, $isArchivePage);
        return $finalOutput;
    }
}

// Kind of depreceated since introducing default unit list
function custom_depotrum_list_func()
{

    $current_pod = pods();

    // Check if the Pod object exists and the field "partner" is set
    if ($current_pod && $current_pod->exists()) {
        $show_units = $current_pod->field("show_units");
        if (!$show_units) {
            return '';
        }
        $unit_items = $current_pod->field("depotrum");
        if (!$unit_items) {
            return '';
        }
        // check if each unit item is avaliable
        $available_unit_items = [];
        foreach ($unit_items as $unit_item) {
            if (get_post_meta($unit_item['ID'], 'available', true)) {
                array_push($available_unit_items, $unit_item);
            }
        }

        $enable_booking = $current_pod->field("enable_booking");
        if ($available_unit_items && !empty($available_unit_items) && $show_units) {
            $partner = $current_pod->field("partner");
            $lokationId = $current_pod->field("id");
            $permalink = get_permalink($lokationId);

            $finalOutput = '';
            $finalOutput .= generate_unit_list($finalOutput, $partner, $lokationId, $available_unit_items, $permalink, $enable_booking);

            $finalOutput .= generate_view_all_button($permalink, $partner, 0);
            return $finalOutput;
        }
    }
}

// Register the shortcode.
add_shortcode("custom_depotrum_list", "custom_depotrum_list_func");

function generate_unit_list($finalOutput, $partner, $lokationId, $available_unit_items, $permalink, $enable_booking, $isArchivePage = 0)
{
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

    if ($isArchivePage) {
        $sorted_ids = extract_evenly_spaced($sorted_ids, 4);
    }

    if ($isArchivePage) {
        $finalOutput .= '<div class="depotrum-list no-border">';
    } else {
        $finalOutput .= '<div class="depotrum-list">';
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

        $output = '<div class="outer-depotrum-row">';

        if ($isArchivePage) {
            if ($partner) {
                if ($depotrum === $lastElement) {
                    $output .= '<a href="' . $permalink . '" class="depotrum-row yellowhover partner last">';
                } else {
                    $output .= '<a href="' . $permalink . '" class="depotrum-row yellowhover partner">';
                }
            } else {
                if ($depotrum === $lastElement) {
                    $output .= '<div class="depotrum-row non-partner last">';
                } else {
                    $output .= '<div class="depotrum-row non-partner">';
                }
            }
        } else {
            if ($partner) {
                if ($depotrum === $lastElement) {
                    $output .= '<div class="depotrum-row partner yellowhover last">';
                } else {
                    $output .= '<div class="depotrum-row yellowhover partner">';
                }
            } else {
                if ($depotrum === $lastElement) {
                    $output .= '<div class="depotrum-row non-partner last">';
                } else {
                    $output .= '<div class="depotrum-row non-partner">';
                }
            }
        }

        $output .= '<div class="flex-container">';
        $output .= generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type, $partner);
        $output .= generate_unit_desc_column($relTypeId, $unit_type, $m2, $m3, $container_type, $partner);

        $output .= '</div>';

        $output .= generate_price_column($price, $partner);

        $output .= generate_navigation_column($partner, $id, $enable_booking, $isArchivePage);

        if ($isArchivePage) {
            if ($partner) {
                $output .= '</a>';
            } else {
                $output .= '</div>';
            }
        } else {
            $output .= '</div>';
        }

        if ($partner && !$isArchivePage) {
            $output .=  generate_booking_form($id);
        }

        $output .= '</div>';

        array_push($OutputArray, $output);
    }
    foreach ($OutputArray as $arrayItem) {
        $finalOutput .= $arrayItem;
    }
    $finalOutput .= "</div>";
    return $finalOutput;
}

function generate_view_all_button($permalink, $partner, $isArchivePage = 0)
{
    if ($isArchivePage && $partner) {
        $finalOutput = '<form action="' . $permalink . '">';
        $finalOutput .= '<input type="submit" class="view-all-button" value="Se alle priser" />';
        $finalOutput .= '</form>';
        return $finalOutput;
    } else {
        return '';
    }
}

function generate_navigation_column($partner, $unitId, $enable_booking, $isArchivePage)
{
    $output = '<div class="navigation-column vertical-center" onclick="toggleFold(' . $unitId . ')">';
    if ($isArchivePage && $partner) { //search or archive page + partner
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="25" height="25">';
        $output .= '<path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" />';
        $output .= '</svg>';
    } else if ((!geodir_is_page('post_type') || !geodir_is_page('search')) && $partner && $enable_booking) { //listing page + partner
        $output .=  '<div class="continue-button" id="continue-button-' .  $unitId . '">Fortsæt</div>';
    }
    $output .= '</div>';
    return $output;
}

function generate_price_column($price, $partner)
{
    $output = '<div class="price-column vertical-center">';
    if ($price && $partner) {
        $output .= '<span class="price partner">' . round($price, 2) . ' kr.</span>';
    } else if ($price && $partner != 1) {
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
    if ($partner != 1) {
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
    if ($partner != 1) {
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


function generate_booking_form($unitId)
{
    $form = '
    <div class="foldableDiv" id="foldableDiv-' . $unitId . '" style="max-height: 0px;">

    <div class="lock-in-rate">
    <img src="' . esc_url(plugins_url('img/clock.svg', __FILE__)) . '" alt="Clock Icon" class="clock-icon" />
    Reserver nu til denne pris. Ingen binding.
  </div>


    <form method="post" id="booking_form" class="booking_form">

    <div class="form-row">
      <input type="text" id="first-name" name="first_name" placeholder="Fornavn" required>

      <input type="text" id="last-name" name="last_name" placeholder="Efternavn" required>
    </div>
    
    <div class="form-row">
      <input type="email" id="email" name="email" placeholder="Email adresse" required>
      <input type="tel" id="phone" name="phone" placeholder="Telefon" required>
    </div>

    <div class="form-row">
<div class="custom-select-wrapper">
    <div class="custom-select">
        <select class="custom-select__trigger">Indflytningsdato<span></span>
        </select>
        <div class="custom-options">
            <!-- JavaScript will populate this area with date options -->
        </div>
        <div class="dropdown-icon"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="10" viewBox="0 0 15 10" class="svg chevron-down"><path d="M7.5 10a1.5 1.5 0 0 1-1.14-.52l-6-7a1.5 1.5 0 1 1 2.28-2L7.5 6.2 12.36.52a1.5 1.5 0 1 1 2.28 2l-6 7A1.5 1.5 0 0 1 7.5 10z"></path></svg></div>
    </div>
</div>
</div>

<div class="form-row">
<p class="instruction">
Hvis du er usikker på din indflytningsdato, så vælg en cirkadato. Du binder dig ikke til denne dato.
</p>
</div>

    <div class="form-row center-items">
    <input type="submit" value="Reservér">
    </div>

    <p class="instruction full-width">
    Hvis du er usikker på din indflytningsdato, så vælg en cirkadato. Du binder dig ikke til denne dato.
    </p>
    
  </form>
  </div>';
    return $form;
}
