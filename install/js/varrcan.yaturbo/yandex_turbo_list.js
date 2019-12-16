"use strict";

var feeds = {
    removeSelected: function () {
        var Grid = BX.Main.gridManager.getById("ya_turbo_feeds");
        var Ids = Grid.instance.getRows().getSelectedIds();

        this.deleteSigned(Ids);
    },
    exportSelected: function () {
        var Grid = BX.Main.gridManager.getById("ya_turbo_feeds");
        var Ids = Grid.instance.getRows().getSelectedIds();

        this.exportSigned(Ids);
    },
    deleteSigned: function (signed) {
        $.ajax({
            url: "/bitrix/admin/yandex_turbo_item.php",
            type: "POST",
            dataType: "json",
            data: {
                funcName: "deleteItem",
                params: signed
            }
        }).done(function (response) {
            if (response.message) {
                $(".yandex-turbo-response").html(response.message).fadeIn(500);
                BX.Main.gridManager.reload("ya_turbo_feeds");
            } else {
                alert(response.errors.join(", "));
            }
        }).fail(function (data) {
            console.log(data);
            alert("Error. Please, refresh page!");
        });
    },
    exportSigned: function (signed) {
        $.ajax({
            url: "/bitrix/admin/yandex_turbo_item.php",
            type: "POST",
            dataType: "json",
            data: {
                funcName: "exportItem",
                params: signed
            }
        }).done(function (response) {
            if (response.message) {
                $(".yandex-turbo-response").html(response.message).fadeIn(500);
                BX.Main.gridManager.reload("ya_turbo_feeds");
            } else {
                alert(response.errors.join(", "));
            }
        }).fail(function (data) {
            console.log(data);
            alert("Error. Please, refresh page!");
        });
    },

    agent: function (id, type) {
        $.ajax({
            url: "/bitrix/admin/yandex_turbo_item.php",
            type: "POST",
            dataType: "json",
            data: {
                funcName: "setAgent",
                params: {
                    id: id,
                    type: type
                }
            }
        }).done(function (response) {
            if (response.message) {
                $(".yandex-turbo-response").html(response.message).fadeIn(500);
                BX.Main.gridManager.reload("ya_turbo_feeds");
            } else {
                alert(response.errors.join(", "));
            }
        }).fail(function (data) {
            console.log(data);
            alert("Error. Please, refresh page!");
        });
    },
    generateRss: function (id) {
        $.ajax({
            url: "/bitrix/admin/yandex_turbo_item.php",
            type: "POST",
            dataType: "json",
            data: {
                funcName: "generateRss",
                params: id
            }
        }).done(function (response) {
            if (response.message) {
                $(".yandex-turbo-response").html(response.message).fadeIn(500);
                BX.Main.gridManager.reload("ya_turbo_feeds");
            } else {
                alert(response.errors.join(", "));
            }
        }).fail(function (data) {
            console.log(data);
            alert("Error. Please, refresh page!");
        });
    }
};
