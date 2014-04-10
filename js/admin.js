var advanced_modernizr_scripts_per_screen_offset = 0;
jQuery(document).ready(function ($) {
    am_update_alts($);
    window.advanced_modernizr_scripts_per_screen_offset = 0;
    am_show_rows($);

    $("#scripts_filter").keyup(function () {
        am_reset_pages($, $("#scripts_filter_select :selected").data("filter"));
        am_show_rows($);
    });
    $("#scripts_filter_close").click(function() {
        $("#scripts_filter").val("");
        am_reset_pages($, $("#scripts_filter_select :selected").data("filter"));
        am_show_rows($);
    });
    $("#scripts_filter_select").change(function () {
        am_reset_pages($, $("#scripts_filter_select :selected").data("filter"));
        am_show_rows($);
    });
    $("#am-screen-nav .current-page").keyup(function () {
        am_show_rows($);
    });
    $("#scripts_filter").mouseup(function(e) {
        am_reset_pages($, $("#scripts_filter_select :selected").data("filter"));
        am_show_rows($);
    });
    $("#am-screen-nav a").click(function() {
        if (!$(this).hasClass("disabled")) {
            total_pages = $('#am-screen-nav .total-pages').text();
            cur_offset = parseInt($("#am-screen-nav .current-page"))-1;
            cur_page = parseInt($("#am-screen-nav .current-page").val());
            new_page = cur_page;
            if ($(this).hasClass("prev-page")) {
                $("#am-screen-nav .current-page").val(cur_page-1);
                new_page = cur_page-1;
            }
            if ($(this).hasClass("next-page")) {
                $("#am-screen-nav .current-page").val(cur_page+1);
                new_page = cur_page+1;
            }
            if ($(this).hasClass("last-page")) {
                $("#am-screen-nav .current-page").val(total_pages);
                new_page = total_pages;
            }
            if ($(this).hasClass("first-page")) {
                $("#am-screen-nav .current-page").val(1);
                new_page = 1;
            }
            am_update_buttons($, new_page, total_pages);
            am_show_rows($);
        }
    });

    am_sh_custom($);
    $("#load_via_cdn :input").change(function(){
        am_sh_custom($);
    });

    var am_custom_frame;
    $('#upload_custom').click(function(e){
        e.preventDefault();
        if ( am_custom_frame ) {
            am_custom_frame.open();
            return;
        }
        am_custom_frame = wp.media.frames.am_custom_frame = wp.media({
            title: "Custom Modernizr Upload",
            button: { text:  "Select Custom JS" },
            multiple: false
        });
        am_custom_frame.on('select', function(){
            var media_attachment = am_custom_frame.state().get('selection').first().toJSON();
            $(this).siblings(":input").val(media_attachment.url);
        });
        am_custom_frame.open();
    });

});
function am_sh_custom($) {
    if ($("#local-custom").prop("checked")) {
        $("#upload_custom").parents("tr").show();
    } else {
        $("#upload_custom").parents("tr").hide();
    }
}

function am_update_alts($) {
    $("#scripts_table tr").removeClass("alternate");
    $("#scripts_table tr").filter(":visible").filter(":odd").addClass("alternate");
}
function am_show_rows($) {
    var rows_to_show = parseInt(window.advanced_modernizr_scripts_per_screen);
    var page = parseInt($("#am-screen-nav .current-page").val());
    var start_row = (page-1)*rows_to_show;
    $("#scripts_table tbody tr").hide();
    $(am_filtered_rows($)).slice(start_row, start_row+rows_to_show).show();
    am_update_alts($);
}
function am_update_buttons($, page, total_pages) {
    if (page > 1 && page < total_pages) {
        $("#am-screen-nav a").removeClass("disabled");
    }
    if (page == 1) {
        $("#am-screen-nav a.first-page, #am-screen-nav a.prev-page").addClass("disabled");
        if (page != total_pages) {
            $("#am-screen-nav a.last-page, #am-screen-nav a.next-page").removeClass("disabled");
        }
    }
    if (page == total_pages) {
        $("#am-screen-nav a.last-page, #am-screen-nav a.next-page").addClass("disabled");
        if (page != 1) {
            $("#am-screen-nav a.first-page, #am-screen-nav a.prev-page").removeClass("disabled");
        }
    }
}
function am_reset_pages($, type) {
    var rows_to_show = parseInt(window.advanced_modernizr_scripts_per_screen);
    if (type == "all") { type = "script"; }
    rows = am_filtered_rows($);
    window.advanced_modernizr_scripts_per_screen_offset = 0;
    total_scripts = rows.length;
    text = $("#am-screen-nav .displaying-num").text();
    updated_text = text.replace(/([0-9]*)/, total_scripts);
    $("#am-screen-nav .displaying-num").text(updated_text);
    $("#am-screen-nav .current-page").val(1);
    total_pages = parseInt(total_scripts/rows_to_show)+1;
    if (total_pages == 0) { total_pages = 1; }
    $('#am-screen-nav .total-pages').text(total_pages);
    am_update_buttons($, 1, total_pages);
}
function am_filtered_rows($) {
    var all_rows = $("#scripts_table tbody tr.script");
    var filtered_rows = '';
    //filter by select box first
    type = $("#scripts_filter_select :selected").data('filter');
    if (type != "all") {
        filtered_rows = $(all_rows).filter("."+type);
    } else {
        filtered_rows = all_rows;
    }
    //filter by text filter next
    var filter_text = $("#scripts_filter").val();
    if (filter_text != "") {
        $(filtered_rows).each(function(i, v) {
            scriptname = $(this).first("td").first("abbr").text();
            if (scriptname.indexOf(filter_text) >= 0) {

            } else {
                filtered_rows = $(filtered_rows).not($(this));
            }
        });
    }
    return filtered_rows;
}