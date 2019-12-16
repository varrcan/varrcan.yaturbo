jQuery(document).ready(function () {

    // false для checkbox
    $(":checkbox").on("change", function () {
        if (this.checked) {
            $(this).val("1");
        } else {
            $(this).val("0");
        }
    });

    // Переключение табов
    var tab_block = $(".adm-detail-tabs-block span");
    tab_block.click(function () {
        var click_id = $(this).attr("id");
        if (click_id !== $(".adm-detail-tabs-block span.adm-detail-tab-active").attr("id")) {
            tab_block.removeClass("adm-detail-tab-active");
            $(this).addClass("adm-detail-tab-active");
            $(".adm-detail-content").fadeOut(0);
            $("#wrap-" + click_id).fadeIn(500);
        }
    });

    // Сохранение настроек
    var settings_button = $("#turbo-settings");
    settings_button.submit(function (event) {
        event.preventDefault();
        var btn = this;

        $(btn).fadeTo(0, 0.5);
        $.ajax({
            url: "/bitrix/admin/yandex_turbo_settings.php",
            type: "POST",
            dataType: "json",
            data: settings_button.serialize()
        }).done(function (data) {
            if (data.message) {
                $(".yandex-turbo-response").html(data.message).fadeIn(500);
            }
        }).fail(function (data) {
            console.log(data);
            alert("Error. Please, refresh page!");
        }).always(function () {
            $(btn).fadeTo(0, 1);
        });
    });

    // Сохранение формы
    var item_button = $("#turbo-item");
    item_button.submit(function (event) {
        event.preventDefault();
        var btn = this;

        $.ajax({
            url: "/bitrix/admin/yandex_turbo_item.php",
            type: "POST",
            dataType: "json",
            data: item_button.serialize()
        }).done(function (response) {
            if (response.error) {
                $(".yandex-turbo-response").html(response.error).fadeIn(500);
                $("html, body").animate({ scrollTop: 0 }, 500);
                return;
            }

            var redirect_link = "/bitrix/admin/yandex_turbo_list.php";

            if (response.message) {
                redirect_link += "?message=" + response.message + "";
            }
            window.location.href = redirect_link;

        }).fail(function (data) {
            console.log(data);
            alert("Error. Please, refresh page!");
        }).always();
    });

    // Выбранный инфоблок
    $("#select_target").change(function () {
        var iblock_id = $(this).val();
        $("#selected_iblock_id").val(iblock_id);
        setLinkTemplate(iblock_id);
    });

    // добавить тип содержимого
    $("#add_block").click(function (event) {
        event.preventDefault();
        var btn = this;
        var add_block = $("#count_block");
        var count_block = +add_block.val() + 1;
        var sort_block = +count_block + 1;

        $(btn).fadeTo(0, 0.5);

        $(".new_block").before(
            "<tr id=\"block_" + count_block + "\">\n" +
            "<td>\n" +
            "<select name=\"content_config[" + count_block + "][block_type]\" onchange=\"getProperty(" + count_block + ");\">\n" +
            "<option>Выберите тип</option>\n" +
            "<option value=\"element\">Поле элемента</option>\n" +
            "<option value=\"property\">Свойство элемента</option>\n" +
            // "<option value=\"tag\">Мета-тег</option>\n" +
            "</select>\n" +
            "</td>\n" +
            "<td><select name=\"content_config[" + count_block + "][block_property]\" style=\"display: none\"></select></td>" +
            "<td><input type=\"text\" name=\"content_config[" + count_block + "][block_sort]\" value=\"" + sort_block + "0\" style=\"width: 55px\"></td>\n" +
            "<td><input value=\"Удалить\" type=\"button\" onclick=\"deleteBlock(" + count_block + ");\"></td>\n" +
            "</tr>"
        );

        add_block.val(count_block);

        $(btn).fadeTo(0, 1);
    });

    // добавить пункт меню
    $("#add_menu").click(function (event) {
        event.preventDefault();
        var btn = this;
        var menu_block = $("#count_menu");
        var count_menu = +menu_block.val() + 1;
        var sort_menu = +count_menu + 1;

        $(btn).fadeTo(0, 0.5);

        $(".new_menu").before(
            "<tr id=\"menu_" + count_menu + "\">\n" +
            "<td><input type=\"text\" name=\"menu[" + count_menu + "][menu_name]\" value=\"\"></td>\n" +
            "<td><input type=\"text\" name=\"menu[" + count_menu + "][menu_path]\" value=\"\"></td>\n" +
            "<td><input type=\"text\" name=\"menu[" + count_menu + "][menu_sort]\" value=\"" + sort_menu + "0\" style=\"width: 55px\"></td>\n" +
            "<td><input value=\"Удалить\" type=\"button\" onclick=\"deleteMenu(" + count_menu + ");\"></td>\n" +
            "</tr>"
        );

        menu_block.val(count_menu);

        $(btn).fadeTo(0, 1);
    });

    // добавить тип обратной связи
    $("#add_feedback_block").click(function (event) {
        event.preventDefault();
        var btn = this;
        var feedback = $("#count_feedback_block");
        var count_feedback = +feedback.val() + 1;

        $(btn).fadeTo(0, 0.5);

        $(".new_feedback").before(
            "<tr id=\"feedback_" + count_feedback + "\">\n" +
            "<td>\n" +
            "<select name=\"feedback_block[" + count_feedback + "][type]\">\n" +
            "<option value=\"call\">Номер телефона</option>\n" +
            "<option value=\"chat\">Чат для бизнеса</option>\n" +
            "<option value=\"mail\">Электронная почта</option>\n" +
            "<option value=\"callback\">Форма обратной связи</option>\n" +
            "<option value=\"facebook\">Facebook</option>\n" +
            "<option value=\"google\">Google</option>\n" +
            "<option value=\"odnoklassniki\">Одноклассники</option>\n" +
            "<option value=\"telegram\">Telegram</option>\n" +
            "<option value=\"twitter\">Twitter</option>\n" +
            "<option value=\"viber\">Viber</option>\n" +
            "<option value=\"vkontakte\">Вконтакте</option>\n" +
            "<option value=\"whatsapp\">WhatsApp</option>\n" +
            "</select>\n" +
            "</td>\n" +
            "<td><input type=\"text\" name=\"feedback_block[" + count_feedback + "][value]\" value=\"\"></td>\n" +
            "<td><input value=\"Удалить\" type=\"button\" onclick=\"deleteFeedback(" + count_feedback + ");\"></td>\n" +
            "</tr>"
        );

        feedback.val(count_feedback);

        $(btn).fadeTo(0, 1);
    });

    // добавить аналитику
    $("#add_analytics").click(function (event) {
        event.preventDefault();
        var btn = this;
        var analytics = $("#count_analytics");
        var count_analytics = +analytics.val() + 1;

        $(btn).fadeTo(0, 0.5);

        $(".new_analytics").before(
            "<tr id=\"analytics_" + count_analytics + "\">\n" +
            "<td>\n" +
            "<select name=\"analytics[" + count_analytics + "][type]\">\n" +
            "<option value=\"Yandex\">Yandex</option>\n" +
            "<option value=\"Google\">Google</option>\n" +
            "<option value=\"MailRu\">MailRu</option>\n" +
            "<option value=\"Rambler\">Rambler</option>\n" +
            "<option value=\"Mediascope\">Mediascope</option>\n" +
            "</select>\n" +
            "</td>\n" +
            "<td><input type=\"text\" name=\"analytics[" + count_analytics + "][value]\" value=\"\"></td>\n" +
            "<td><input value=\"Удалить\" type=\"button\" onclick=\"deleteAnalytics(" + count_analytics + ");\"></td>\n" +
            "</tr>"
        );

        analytics.val(count_analytics);

        $(btn).fadeTo(0, 1);
    });

});

function deleteBlock(id) {
    $("#block_" + id).remove();
}

function deleteMenu(id) {
    $("#menu_" + id).remove();
}

function deleteFeedback(id) {
    $("#feedback_" + id).remove();
}

function deleteAnalytics(id) {
    $("#analytics_" + id).remove();
}

// свойства элемента
function getProperty(id) {
    var options = "";
    var iblock = $("#selected_iblock_id").val();
    var type_value = $("select[name =\"content_config[" + id + "][block_type]\"]").val();
    var select = $("select[name =\"content_config[" + id + "][block_property]\"]");

    select.html("");

    $.ajax({
        url: "/bitrix/admin/yandex_turbo_item.php",
        type: "POST",
        dataType: "json",
        data: {
            funcName: "iblockType",
            params: {
                iblock: iblock,
                type: type_value
            }
        }
    }).done(function (response) {
        if (response.error) {
            $(".yandex-turbo-response").html(response.error).fadeIn(500);
            $("html, body").animate({ scrollTop: 0 }, 500);
            return;
        }
        if (response.data) {
            select.slideDown(50);
            $.each(response.data, function (key, val) {
                options += "<option value=\"" + key + "\">" + "[" + key + "] " + val + "</option>";
            });

            select.append(
                options
            );
        }
    }).fail(function (data) {
        console.log(data);
        alert("Error. Please, refresh page!");
    });
}

// шаблоны ссылок
function setLinkTemplate(iblock) {
    var is_set_id = $("input[name =\"id\"]");
    var detail = $("input[name =\"detail_page_template\"]");
    var section = $("input[name =\"section_page_template\"]");

    $.ajax({
        url: "/bitrix/admin/yandex_turbo_item.php",
        type: "POST",
        dataType: "json",
        data: {
            funcName: "getIblockTemplateUrl",
            params: {
                iblockId: iblock
            }
        }
    }).done(function (response) {
        if (response.error) {
            $(".yandex-turbo-response").html(response.error).fadeIn(500);
            $("html, body").animate({ scrollTop: 0 }, 500);
            return;
        }
        if (response.data.detail && is_set_id.val().length === 0) {
            detail.val(response.data.detail);
        }
        if (response.data.section && is_set_id.val().length === 0) {
            section.val(response.data.section);
        }
    }).fail(function (data) {
        console.log(data);
        alert("Error. Please, refresh page!");
    });
}
