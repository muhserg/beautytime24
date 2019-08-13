var ModalApp = {};
ModalApp.ModalProcess = function (parameters) {
    this.id = "dinamicModal";

    if (parameters["title"] && parameters["body"] && parameters["footer"]) {
        this.title = parameters["title"];
        this.body = parameters["body"];
        this.footer = parameters["footer"];

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-dialog' role='document'>" +
            "<div class='modal-content'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;'><span aria-hidden='true'>&times;</span></button>" +
            "<div class='modal-header' style='padding-left: 15px; padding-top: 10px; padding-bottom: 5px; margin-top: 5px;'>" +
            "<h4 class='modal-title text-center'>" + this.title + "</h4>" +
            "</div>" +
            "<div class='modal-body'>" + this.body + "</div>" +
            "<div class='modal-footer' style='padding-left: 15px; padding-top: 10px; padding-bottom: 10px; margin-top: 5px;'>" + this.footer + "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    else if (parameters["title"] && parameters["body"]) {
        this.title = parameters["title"];
        this.body = parameters["body"];

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-dialog' role='document'>" +
            "<div class='modal-content'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;'><span aria-hidden='true'>&times;</span></button>" +
            "<div class='modal-header' style='padding-left: 15px; padding-top: 10px; padding-bottom: 5px; margin-top: 5px;'>" +
            "<h4 class='modal-title text-center'>" + this.title + "</h4>" +
            "</div>" +
            "<div class='modal-body'>" + this.body + "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    else if (parameters["body"]) {
        this.body = parameters["body"];

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-dialog' role='document'>" +
            "<div class='modal-content'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;'><span aria-hidden='true'>&times;</span></button>" +
            "<div class='modal-body pt-0' style='padding:25px;'>" +
            "<div>" +
            this.body +
            "</div>" +
            "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    else {
        this.title = parameters["title"] || "Modal's title from constructor";
        this.body = parameters["body"] || "Modal's body from constructor";
        this.footer = parameters["footer"] || "<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>";

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-dialog' role='document'>" +
            "<div class='modal-content text-justify' style='margin-top: 300px'>" +
            "<div class='modal-header'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;><span aria-hidden='true'>&times;</span></button>" +
            "<h4 class='modal-title text-center'>" + this.title + "</h4>" +
            "</div>" +
            "<div class='modal-body'>" + this.body + "</div>" +
            "<div class='modal-footer'>" + this.footer + "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
};

ModalApp.BigModalProcess = function (parameters) {
    this.id = "dinamicModal";
    var size = window.screen;

    if (parameters["title"] && parameters["body"] && parameters["footer"]) {
        this.title = parameters["title"];
        this.body = parameters["body"];
        this.footer = parameters["footer"];

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-big-dialog modal-dialog' role='document' style='max-width: 1300px !important;'>" +
            "<div class='modal-content'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;'><span aria-hidden='true'>&times;</span></button>" +
            "<div class='modal-header' style='padding-left: 15px; padding-top: 10px; padding-bottom: 5px; margin-top: 5px;'>" +
            "<h4 class='modal-title text-center'>" + this.title + "</h4>" +
            "</div>" +
            "<div class='modal-body'>" + this.body + "</div>" +
            "<div class='modal-footer' style='padding-left: 15px; padding-top: 10px; padding-bottom: 10px; margin-top: 5px;'>" + this.footer + "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    else if (parameters["title"] && parameters["body"]) {
        this.title = parameters["title"];
        this.body = parameters["body"];

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-big-dialog modal-dialog' role='document' style='max-width: 1300px !important'>" +
            "<div class='modal-content'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;'><span aria-hidden='true'>&times;</span></button>" +
            "<div class='modal-header' style='padding-left: 15px; padding-top: 10px; padding-bottom: 5px; margin-top: 5px;'>" +
            "<h4 class='modal-title text-center'>" + this.title + "</h4>" +
            "</div>" +
            "<div class='modal-body'>" + this.body + "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    else if (parameters["body"]) {
        this.body = parameters["body"];

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-big-dialog modal-dialog' role='document' style='max-width: 1300px !important;'>" +
            "<div class='modal-content'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;'><span aria-hidden='true'>&times;</span></button>" +
            "<div class='modal-body pt-0' style='padding:25px;'>" +
            "<div>" +
            this.body +
            "</div>" +
            "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    else {
        this.title = parameters["title"] || "Modal's title from constructor";
        this.body = parameters["body"] || "Modal's body from constructor";
        this.footer = parameters["footer"] || "<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>";

        this.content =
            "<div id='dinamicModal' class='modal fade' tabindex='-1' style='z-index: 1051 !important;'>" +
            "<div class='modal-big-dialog modal-dialog' role='document' style='max-width: 1300px !important;'>" +
            "<div class='modal-content text-justify' style='margin-top: 300px'>" +
            "<div class='modal-header'>" +
            "<button type='button' class='close text-right' data-dismiss='modal' aria-label='Close' style='position:relative; z-index:9999; cursor:pointer;opacity: 1.0 !important; padding-right: 8px; padding-top: 8px;><span aria-hidden='true'>&times;</span></button>" +
            "<h4 class='modal-title text-center'>" + this.title + "</h4>" +
            "</div>" +
            "<div class='modal-body'>" + this.body + "</div>" +
            "<div class='modal-footer'>" + this.footer + "</div>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
};

ModalApp.CreateModal = function (target) {
    var myDinamicModal;

    if ((target["htmlHeader"] != null) && (target["htmlBody"] != null) && (target["htmlFooter"] != null)) {
        // Создаём модальное окно, вызванное из скрипта, 
        myDinamicModal = new ModalApp.ModalProcess({ title: target["htmlHeader"], body: target["htmlBody"], footer: target["htmlFooter"] });
    }
    else if ((target["htmlHeader"] != null) && (target["htmlBody"] != null)) {
        // Создаём модальное окно, вызванное из скрипта, 
        myDinamicModal = new ModalApp.ModalProcess({ title: target["htmlHeader"], body: target["htmlBody"] });
    }
    else if (target["htmlBody"] != null) {
        // Создаём модальное окно, вызванное из скрипта, 
        myDinamicModal = new ModalApp.ModalProcess({ body: target["htmlBody"] });
    }
    else {
        // Создаём модальное окно, вызванное классом "target-modal-[event]", 
        myDinamicModal = new ModalApp.ModalProcess({ body: $(target).attr("modalHtmlContent") });
    }

    // ... вставляем его в разметку,
    $("body").prepend(myDinamicModal.content)

    // ... и открываем!
    $("#dinamicModal").modal();
};

ModalApp.CreateBigModal = function (target) {
    var myDinamicModal;

    if ((target["htmlHeader"] != null) && (target["htmlBody"] != null) && (target["htmlFooter"] != null)) {
        // Создаём модальное окно, вызванное из скрипта, 
        myDinamicModal = new ModalApp.BigModalProcess({ title: target["htmlHeader"], body: target["htmlBody"], footer: target["htmlFooter"] });
    }
    else if ((target["htmlHeader"] != null) && (target["htmlBody"] != null)) {
        // Создаём модальное окно, вызванное из скрипта, 
        myDinamicModal = new ModalApp.BigModalProcess({ title: target["htmlHeader"], body: target["htmlBody"] });
    }
    else if (target["htmlBody"] != null) {
        // Создаём модальное окно, вызванное из скрипта, 
        myDinamicModal = new ModalApp.BigModalProcess({ body: target["htmlBody"] });
    }
    else {
        // Создаём модальное окно, вызванное классом "target-modal-[event]", 
        myDinamicModal = new ModalApp.BigModalProcess({ body: $(target).attr("modalHtmlContent") });
    }

    // ... вставляем его в разметку,
    $("body").prepend(myDinamicModal.content)

    // ... и открываем!
    $("#dinamicModal").modal();
};

// jQuery функции расширения
jQuery.fn.extend(
    {
        phCallModal: function (htmlBody, htmlHeader, htmlFooter) {
            if (htmlHeader && htmlBody && htmlFooter) {
                // Вызываем функцию создания динамического модального окна
                ModalApp.CreateModal({ htmlHeader: htmlHeader, htmlBody: htmlBody, htmlFooter: htmlFooter });
            }
            else if (htmlBody && htmlHeader) {
                // Вызываем функцию создания динамического модального окна
                ModalApp.CreateModal({ htmlHeader: htmlHeader, htmlBody: htmlBody });
            }
            else if (htmlBody) {
                // Вызываем функцию создания динамического модального окна
                ModalApp.CreateModal({ htmlBody: htmlBody });
            }
        },

        phCallBigModal: function (htmlBody, htmlHeader, htmlFooter) {
            if (htmlHeader && htmlBody && htmlFooter) {
                // Вызываем функцию создания динамического модального окна
                ModalApp.CreateBigModal({ htmlHeader: htmlHeader, htmlBody: htmlBody, htmlFooter: htmlFooter, bigModal: true });
            }
            else if (htmlBody && htmlHeader) {
                // Вызываем функцию создания динамического модального окна
                ModalApp.CreateBigModal({ htmlHeader: htmlHeader, htmlBody: htmlBody, bigModal: true });
            }
            else if (htmlBody) {
                // Вызываем функцию создания динамического модального окна
                ModalApp.CreateBigModal({ htmlBody: htmlBody, bigModal: true });
            }
        }
    });

$(function () {
    // События обработки кликов
    $(".target-modal-click").bind("click", function () {
        // Вызываем функцию создания динамического модального окна
        ModalApp.CreateModal($(this));
    });

    // Событие обработки наведения мышью на элемент
    $(".target-modal-mouseenter").bind("mouseenter", function () {
        // Вызываем функцию создания динамического модального окна
        ModalApp.CreateModal($(this));
    });

    // Событие обработки сокрытия модального окна
    $(document).on("hide.bs.modal", "#dinamicModal", function () {
        // Удаляем модальное окно 
        $("#dinamicModal").remove();
        // ... и его остатки из разметки
        $("body").removeAttr("class");
        $(".modal-backdrop").remove();
    });
});
